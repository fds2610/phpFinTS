<?php
/** @noinspection PhpUnused */

namespace Fhp\Segment\KKU;

use Fhp\Segment\BaseDeg;
use Fhp\Segment\Common\Btg;

/**
 * Data Element Group: Kreditkartenumsatz (Version 1)
 *
 * One credit card transaction record, as contained in {@link DIKKUv2::$umsaetze}.
 *
 * There is no official specification. The layout was derived from AqBanking's declarative definition
 * (GROUPdef "creditcardtransaction") and then corrected against real BW-Bank/LBBW responses.
 * @link https://github.com/aqbanking/aqbanking/blob/master/src/libs/plugins/backends/aqhbci/ajobs/jobgettransactions.xml
 *
 * Deviations from AqBanking's definition, all confirmed against real data:
 *  - AqBanking describes $ursprungsbetrag ("value2") as a duplicate of $betrag that is zero for
 *    weekly statements. In fact it carries the amount in the *original* currency, while $betrag
 *    carries the amount billed in the account currency. For domestic transactions both are equal,
 *    which is presumably why they looked like duplicates.
 *  - AqBanking describes $umrechnungskurs ("unknown3") as always "1,". It is the exchange rate, and
 *    is indeed 1 whenever no conversion takes place.
 *  - AqBanking's definition ends after the reference. Real responses append the merchant category
 *    code (see $branchenschluessel). It is absent for non-merchant bookings such as the monthly
 *    settlement debit, hence optional.
 *
 * The nine Verwendungszweck fields are modelled as discrete elements rather than a repeated array,
 * because they sit in the middle of the record and are followed by further fixed fields.
 */
class Kreditkartenumsatz extends BaseDeg
{
    /**
     * Max length: 30. Depending on the bank's vintage this is either the credit card account number
     * or the number of the individual card that was used.
     */
    public string $kontonummer;
    /** JJJJMMTT gemäß ISO 8601. The date the transaction was made (AqBanking: "valutaDate"). */
    public ?string $belegdatum = null;
    /** JJJJMMTT gemäß ISO 8601. The date it was booked (AqBanking: "date"). */
    public ?string $buchungsdatum = null;
    /** Always empty (AqBanking: "unknown1", maxsize 0). */
    public ?string $unbekannt1 = null;
    /**
     * The amount in the currency the merchant charged. Equal to {@link $betrag} for domestic
     * transactions. For foreign currency transactions this is the original amount, e.g. 34.99 USD.
     */
    public Btg $ursprungsbetrag;
    /** Soll/Haben-Kennzeichen for {@link $ursprungsbetrag} ('D' = Soll/debit, 'C' = Haben/credit). */
    public string $ursprungsSollHabenKennzeichen;
    /**
     * The exchange rate applied, i.e. $ursprungsbetrag * $umrechnungskurs == $betrag. Exactly 1 when
     * no conversion took place.
     */
    public ?float $umrechnungskurs = null;
    /** The amount billed to the account, in the account's currency. */
    public Btg $betrag;
    /** Soll/Haben-Kennzeichen for {@link $betrag} ('D' = Soll/debit, 'C' = Haben/credit). */
    public string $sollHabenKennzeichen;
    /** Max length: 50. Usually the merchant name. */
    public ?string $verwendungszweck1 = null;
    /** Max length: 50. Usually the merchant location followed by the masked card number that was used. */
    public ?string $verwendungszweck2 = null;
    /** Max length: 50 */
    public ?string $verwendungszweck3 = null;
    /** Max length: 50 */
    public ?string $verwendungszweck4 = null;
    /** Max length: 50 */
    public ?string $verwendungszweck5 = null;
    /** Max length: 50 */
    public ?string $verwendungszweck6 = null;
    /** Max length: 50 */
    public ?string $verwendungszweck7 = null;
    /** Max length: 50 */
    public ?string $verwendungszweck8 = null;
    /** Max length: 50 */
    public ?string $verwendungszweck9 = null;
    /** Semantics unknown (AqBanking: "yesno1"). Observed values: "J". Max length: 1 */
    public ?string $unbekannt4 = null;
    /** Max length: 30. The reference number ("Referenz" in the bank's web UI). */
    public ?string $referenz = null;
    /**
     * The ISO 18245 merchant category code (MCC) of the merchant, e.g. 5411 for grocery stores.
     * Absent for bookings that have no merchant, such as the monthly settlement debit.
     * Max length: 4
     */
    public ?string $branchenschluessel = null;

    /** @return string[] The non-empty Verwendungszweck lines, in order. */
    public function getVerwendungszweckLines(): array
    {
        $lines = [
            $this->verwendungszweck1, $this->verwendungszweck2, $this->verwendungszweck3,
            $this->verwendungszweck4, $this->verwendungszweck5, $this->verwendungszweck6,
            $this->verwendungszweck7, $this->verwendungszweck8, $this->verwendungszweck9,
        ];
        return array_values(array_filter($lines, fn ($line) => $line !== null && $line !== ''));
    }
}
