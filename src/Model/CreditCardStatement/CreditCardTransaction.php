<?php
/** @noinspection PhpUnused */

namespace Fhp\Model\CreditCardStatement;

use Fhp\Segment\KKU\Kreditkartenumsatz;

/**
 * A single credit card transaction, as returned by {@link \Fhp\Action\GetCreditCardStatement}.
 *
 * Unlike a regular {@link \Fhp\Model\StatementOfAccount\Transaction} (which stems from MT940), credit
 * card records are flat and carry a distinct set of fields.
 */
class CreditCardTransaction
{
    public const CD_CREDIT = 'credit';
    public const CD_DEBIT = 'debit';

    protected ?string $accountNumber = null;
    protected ?\DateTime $valutaDate = null;
    protected ?\DateTime $bookingDate = null;
    /** Signed amount: negative for debits, positive for credits. */
    protected float $amount = 0.0;
    protected string $creditDebit = self::CD_CREDIT;
    protected string $currency = 'EUR';
    protected string $purpose = '';
    protected ?string $reference = null;

    public function getAccountNumber(): ?string
    {
        return $this->accountNumber;
    }

    public function setAccountNumber(?string $accountNumber): static
    {
        $this->accountNumber = $accountNumber;
        return $this;
    }

    public function getValutaDate(): ?\DateTime
    {
        return $this->valutaDate;
    }

    public function setValutaDate(?\DateTime $valutaDate): static
    {
        $this->valutaDate = $valutaDate;
        return $this;
    }

    public function getBookingDate(): ?\DateTime
    {
        return $this->bookingDate;
    }

    public function setBookingDate(?\DateTime $bookingDate): static
    {
        $this->bookingDate = $bookingDate;
        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function getCreditDebit(): string
    {
        return $this->creditDebit;
    }

    public function setCreditDebit(string $creditDebit): static
    {
        $this->creditDebit = $creditDebit;
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    public function getPurpose(): string
    {
        return $this->purpose;
    }

    public function setPurpose(string $purpose): static
    {
        $this->purpose = $purpose;
        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;
        return $this;
    }

    /**
     * Builds a model transaction from a parsed {@link Kreditkartenumsatz} record.
     */
    public static function fromSegment(Kreditkartenumsatz $umsatz): CreditCardTransaction
    {
        $result = new CreditCardTransaction();
        $result->accountNumber = $umsatz->kontonummer;
        $result->valutaDate = self::parseDate($umsatz->belegdatum);
        $result->bookingDate = self::parseDate($umsatz->buchungsdatum);

        // Always use $betrag (value); $betrag2 (value2) is zero for weekly statements.
        $isDebit = in_array(strtoupper($umsatz->sollHabenKennzeichen), ['S', 'D'], true);
        $result->creditDebit = $isDebit ? self::CD_DEBIT : self::CD_CREDIT;
        $result->amount = ($isDebit ? -1 : 1) * abs($umsatz->betrag->wert);
        $result->currency = $umsatz->betrag->waehrung;

        $result->purpose = trim(implode(' ', $umsatz->getVerwendungszweckLines()));
        $result->reference = $umsatz->referenz;
        return $result;
    }

    private static function parseDate(?string $date): ?\DateTime
    {
        if ($date === null || $date === '') {
            return null;
        }
        $parsed = \DateTime::createFromFormat('Ymd', $date);
        return $parsed === false ? null : $parsed->setTime(0, 0);
    }
}
