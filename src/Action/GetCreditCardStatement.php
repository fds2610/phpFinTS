<?php

namespace Fhp\Action;

use Fhp\Model\CreditCardAccount;
use Fhp\Model\CreditCardStatement\CreditCardStatement;
use Fhp\PaginateableAction;
use Fhp\Protocol\BPD;
use Fhp\Protocol\Message;
use Fhp\Protocol\UPD;
use Fhp\Segment\Common\Kik;
use Fhp\Segment\Common\KtvV3;
use Fhp\Segment\HIRMS\Rueckmeldungscode;
use Fhp\Segment\KKU\DIKKU;
use Fhp\Segment\KKU\DIKKUS;
use Fhp\Segment\KKU\DKKKUv2;
use Fhp\UnsupportedException;

/**
 * Retrieves credit card transactions (Kreditkartenumsätze) for a single credit card. This uses the
 * "DK" business transaction DKKKU, which is offered by the Sparkassen-Finanzgruppe (incl. BW-Bank/
 * LBBW). Credit card accounts have no IBAN and can therefore not be queried through
 * {@link GetStatementOfAccount} (HKKAZ) or {@link GetStatementOfAccountXML} (HKCAZ).
 *
 * The account to query is obtained from {@link GetCreditCardAccounts}, which reads it from the UPD.
 * Credit card accounts are NOT part of {@link GetSEPAAccounts}, because they have no IBAN.
 *
 * Note that DKKKU has no publicly available specification, see {@link DKKKUv2} for details and for
 * the reference this implementation is based on instead.
 */
class GetCreditCardStatement extends PaginateableAction
{
    // Request (if you add a field here, update __serialize() and __unserialize() as well).
    /** @var CreditCardAccount */
    private $account;
    /** @var \DateTime|null */
    private $from;
    /** @var \DateTime|null */
    private $to;

    // Response
    /** @var DIKKU[] */
    private $responseSegments = [];
    /** @var CreditCardStatement */
    private $statement;

    /**
     * @param CreditCardAccount $account The credit card account to get the transactions for, as
     *     obtained from {@link GetCreditCardAccounts}.
     * @param \DateTime|null $from If set, only transactions after this date (inclusive) are returned.
     * @param \DateTime|null $to If set, only transactions before this date (inclusive) are returned.
     * @return GetCreditCardStatement A new action instance.
     */
    public static function create(CreditCardAccount $account, ?\DateTime $from = null, ?\DateTime $to = null): GetCreditCardStatement
    {
        if ($account->getAccountNumber() === null) {
            throw new \InvalidArgumentException('The credit card account must have an account number');
        }
        if ($from !== null && $to !== null && $from > $to) {
            throw new \InvalidArgumentException('From-date must be before to-date');
        }

        $result = new GetCreditCardStatement();
        $result->account = $account;
        $result->from = $from;
        $result->to = $to;
        return $result;
    }

    /**
     * @deprecated Beginning from PHP7.4 __unserialize is used for new generated strings, then this method is only used for previously generated strings - remove after May 2023
     */
    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    public function __serialize(): array
    {
        return [
            parent::__serialize(),
            $this->account, $this->from, $this->to,
        ];
    }

    /**
     * @deprecated Beginning from PHP7.4 __unserialize is used for new generated strings, then this method is only used for previously generated strings - remove after May 2023
     *
     * @param string $serialized
     * @return void
     */
    public function unserialize($serialized)
    {
        self::__unserialize(unserialize($serialized));
    }

    public function __unserialize(array $serialized): void
    {
        list(
            $parentSerialized,
            $this->account, $this->from, $this->to,
        ) = $serialized;

        is_array($parentSerialized) ?
            parent::__unserialize($parentSerialized) :
            parent::unserialize($parentSerialized);
    }

    public function getStatement(): CreditCardStatement
    {
        $this->ensureDone();
        return $this->statement;
    }

    protected function createRequest(BPD $bpd, ?UPD $upd)
    {
        /** @var DIKKUS $dikkus */
        $dikkus = $bpd->requireLatestSupportedParameters('DIKKUS');
        $this->validateDateRange($dikkus);
        switch ($dikkus->getVersion()) {
            case 2:
                // AqBanking sends a Kontoverbindung (ktv) plus a standalone card number. The
                // Kontoverbindung is taken verbatim from the UPD (via GetCreditCardAccounts), so it
                // matches exactly what the bank told us about this account.
                $kontoverbindung = KtvV3::create(
                    $this->account->getAccountNumber(),
                    $this->account->getSubAccount(),
                    Kik::create($this->account->getBlz() ?? $bpd->getBankCode())
                );
                return DKKKUv2::create($kontoverbindung, $this->account->getAccountNumber(), $this->from, $this->to);
            default:
                throw new UnsupportedException('Unsupported DKKKU version: ' . $dikkus->getVersion());
        }
    }

    /**
     * Limits how much a single request may ask for, using the period the bank announces in the
     * DIKKUS parameters.
     *
     * That period is specified as the time for which the bank keeps transactions, but the tested
     * bank served data well beyond it, so it is not a hard boundary there. What did break was asking
     * for too much at once: a range twice the announced period was served in full on one attempt and
     * answered with an empty page plus a pagination token on the next, and a range eight times as
     * long came back silently truncated. Ranges within the announced period were always answered
     * consistently. The limit is therefore applied to the span of the request rather than to how far
     * back it reaches, which also leaves callers the option of walking through older data window by
     * window.
     *
     * @throws \InvalidArgumentException If the requested range spans more than the announced period.
     */
    private function validateDateRange(DIKKUS $dikkus): void
    {
        $speicherzeitraum = $dikkus->getParameter()->speicherzeitraum;
        if ($this->from === null || $speicherzeitraum <= 0) {
            return; // Without a from-date the bank decides how much to return.
        }
        $to = $this->to ?? new \DateTime();
        $requestedDays = (int) $this->from->diff($to)->days;
        if ($requestedDays > $speicherzeitraum) {
            throw new \InvalidArgumentException(sprintf(
                'The bank answers requests spanning more than %d days inconsistently, but the requested range spans %d days (%s to %s). Fetch older transactions in separate requests of at most %d days each.',
                $speicherzeitraum,
                $requestedDays,
                $this->from->format('Y-m-d'),
                $to->format('Y-m-d'),
                $speicherzeitraum
            ));
        }
    }

    public function processResponse(Message $response)
    {
        parent::processResponse($response);

        // Banks send just 3010 and no DIKKU in case there are no transactions.
        $isUnavailable = $response->findRueckmeldung(Rueckmeldungscode::NICHT_VERFUEGBAR) !== null;
        if (!$isUnavailable) {
            /** @var DIKKU $segment */
            foreach ($response->findSegments(DIKKU::class) as $segment) {
                $this->responseSegments[] = $segment;
            }
        }

        // Note: Pagination boundaries may cut between records, so only build the result once all pages
        // have been received.
        if (!$this->hasMorePages()) {
            $this->statement = CreditCardStatement::fromSegments($this->responseSegments);
        }
    }
}
