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
 * There is no official specification. The field layout is derived from AqBanking's declarative
 * definition (GROUPdef "creditcardtransaction", version 1).
 * @link https://github.com/aqbanking/aqbanking/blob/master/src/libs/plugins/backends/aqhbci/ajobs/jobgettransactions.xml
 *
 * NOTE: The nine "Verwendungszweck" fields are modelled as discrete elements (rather than a repeated
 * array) because they sit in the middle of the record, followed by further fixed fields.
 *
 * TODO(BW-Bank): Verify against a real record. Open questions: the allowed values of the
 * Soll/Haben-Kennzeichen ('S'/'H' vs 'D'/'C'), whether the amount groups carry a currency, and the
 * exact number of Verwendungszweck fields actually sent.
 */
class Kreditkartenumsatz extends BaseDeg
{
    /** Max length: 30 */
    public string $kontonummer;
    /** JJJJMMTT gemäß ISO 8601 (AqBanking: "valutaDate") */
    public ?string $belegdatum = null;
    /** JJJJMMTT gemäß ISO 8601 (AqBanking: "date") */
    public ?string $buchungsdatum = null;
    /** Always empty (AqBanking: "unknown1", maxsize 0). */
    public ?string $unbekannt1 = null;
    /**
     * Appears identical to {@link $betrag}, but is zero for weekly statements. Do NOT use it for the
     * amount; the correct value is always carried by {@link $betrag}. (Quirk documented in AqBanking.)
     */
    public Btg $betrag2;
    /** Soll/Haben-Kennzeichen belonging to {@link $betrag2}. See the note on {@link $betrag2}. */
    public string $sollHabenKennzeichen2;
    /** Semantics unknown (AqBanking: "unknown3", always "1,"). Max length: 30 */
    public ?string $unbekannt3 = null;
    /** The transaction amount (AqBanking: "value"). */
    public Btg $betrag;
    /** Soll/Haben-Kennzeichen belonging to {@link $betrag} ('S' = Soll/debit, 'H' = Haben/credit). */
    public string $sollHabenKennzeichen;
    /** Max length: 50 */
    public ?string $verwendungszweck1 = null;
    /** Max length: 50 */
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
    /** Semantics unknown (AqBanking: "yesno1", always "Y"). Max length: 1 */
    public ?string $unbekannt4 = null;
    /** Max length: 30. 16-digit reference number ("Referenz" in the bank's web UI). */
    public ?string $referenz = null;

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
