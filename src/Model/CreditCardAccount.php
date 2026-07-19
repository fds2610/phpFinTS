<?php
/** @noinspection PhpUnused */

namespace Fhp\Model;

/**
 * A credit card account, as obtained from the HIUPD segments in the UPD (see
 * {@link \Fhp\Action\GetCreditCardAccounts}).
 *
 * Note: Credit card accounts have no IBAN, so they are NOT part of {@link \Fhp\Action\GetSEPAAccounts}
 * (HKSPA) and cannot be represented as a {@link SEPAAccount}.
 */
class CreditCardAccount
{
    /** The credit card number, taken from the account's Kontonummer. */
    protected ?string $cardNumber = null;
    /** Distinguishes several cards belonging to the same account, if the bank uses it. */
    protected ?string $subAccount = null;
    protected ?string $blz = null;
    /** The account holder name. */
    protected ?string $name = null;
    /** The bank's product name, e.g. "Mastercard Gold". */
    protected ?string $productName = null;
    protected ?string $currency = null;
    /** The FinTS account type; 50-59 denotes a credit card account. Null before HIUPD v6. */
    protected ?int $accountType = null;

    public function getCardNumber(): ?string
    {
        return $this->cardNumber;
    }

    public function setCardNumber(?string $cardNumber): static
    {
        $this->cardNumber = $cardNumber;
        return $this;
    }

    public function getSubAccount(): ?string
    {
        return $this->subAccount;
    }

    public function setSubAccount(?string $subAccount): static
    {
        $this->subAccount = $subAccount;
        return $this;
    }

    public function getBlz(): ?string
    {
        return $this->blz;
    }

    public function setBlz(?string $blz): static
    {
        $this->blz = $blz;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function setProductName(?string $productName): static
    {
        $this->productName = $productName;
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    public function getAccountType(): ?int
    {
        return $this->accountType;
    }

    public function setAccountType(?int $accountType): static
    {
        $this->accountType = $accountType;
        return $this;
    }
}
