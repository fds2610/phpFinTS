<?php

namespace Fhp\Action;

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
 * The card number is user input; it does NOT come from {@link GetSEPAAccounts}.
 */
class GetCreditCardStatement extends PaginateableAction
{
    // Request (if you add a field here, update __serialize() and __unserialize() as well).
    /** @var string */
    private $cardNumber;
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
     * @param string $cardNumber The credit card number to get the transactions for (user input).
     * @param \DateTime|null $from If set, only transactions after this date (inclusive) are returned.
     * @param \DateTime|null $to If set, only transactions before this date (inclusive) are returned.
     * @return GetCreditCardStatement A new action instance.
     */
    public static function create(string $cardNumber, ?\DateTime $from = null, ?\DateTime $to = null): GetCreditCardStatement
    {
        if ($from !== null && $to !== null && $from > $to) {
            throw new \InvalidArgumentException('From-date must be before to-date');
        }

        $result = new GetCreditCardStatement();
        $result->cardNumber = $cardNumber;
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
            $this->cardNumber, $this->from, $this->to,
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
            $this->cardNumber, $this->from, $this->to,
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
        switch ($dikkus->getVersion()) {
            case 2:
                // TODO(BW-Bank): Verify the account identification. AqBanking sends a Kontoverbindung
                // (ktv) plus a standalone card number. We build the ktv from the bank code and the
                // card number; confirm against a real request that this is what the bank expects.
                $kontoverbindung = KtvV3::create($this->cardNumber, null, Kik::create($bpd->getBankCode()));
                return DKKKUv2::create($kontoverbindung, $this->cardNumber, $this->from, $this->to);
            default:
                throw new UnsupportedException('Unsupported DKKKU version: ' . $dikkus->getVersion());
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
