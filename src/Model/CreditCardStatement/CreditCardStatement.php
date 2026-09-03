<?php
/** @noinspection PhpUnused */

namespace Fhp\Model\CreditCardStatement;

use Fhp\Segment\KKU\DIKKU;

/**
 * The result of a {@link \Fhp\Action\GetCreditCardStatement}: a flat list of credit card transactions
 * plus, if the bank reported it, the current balance.
 */
class CreditCardStatement
{
    /** @var CreditCardTransaction[] */
    protected array $transactions = [];
    protected ?string $accountNumber = null;
    protected ?float $balance = null;
    protected ?string $balanceCurrency = null;
    protected ?\DateTime $balanceDate = null;

    /** @return CreditCardTransaction[] */
    public function getTransactions(): array
    {
        return $this->transactions;
    }

    public function addTransaction(CreditCardTransaction $transaction): static
    {
        $this->transactions[] = $transaction;
        return $this;
    }

    public function getAccountNumber(): ?string
    {
        return $this->accountNumber;
    }

    /** @return float|null The booked balance, if the bank reported one. */
    public function getBalance(): ?float
    {
        return $this->balance;
    }

    public function getBalanceCurrency(): ?string
    {
        return $this->balanceCurrency;
    }

    public function getBalanceDate(): ?\DateTime
    {
        return $this->balanceDate;
    }

    /**
     * @param DIKKU[] $segments The DIKKU response segments (one per request segment / page).
     */
    public static function fromSegments(array $segments): CreditCardStatement
    {
        $result = new CreditCardStatement();
        foreach ($segments as $segment) {
            if ($result->accountNumber === null) {
                $result->accountNumber = $segment->getKontonummer();
            }
            $saldo = $segment->getSaldo();
            if ($saldo !== null && $result->balance === null) {
                $result->balance = $saldo->getAmount();
                $result->balanceCurrency = $saldo->getCurrency();
                $result->balanceDate = $saldo->getTimestamp();
            }
            foreach ($segment->getUmsaetze() as $umsatz) {
                $result->addTransaction(CreditCardTransaction::fromSegment($umsatz));
            }
        }
        return $result;
    }
}
