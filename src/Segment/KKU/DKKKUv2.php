<?php
/** @noinspection PhpUnused */

namespace Fhp\Segment\KKU;

use Fhp\Segment\BaseSegment;
use Fhp\Segment\Common\KtvV3;
use Fhp\Segment\Paginateable;

/**
 * Segment: Kreditkartenumsätze anfordern (Version 2)
 *
 * This is a "DK" (Deutsche Kreditwirtschaft) institute-specific business transaction offered by the
 * Sparkassen-Finanzgruppe (incl. BW-Bank/LBBW) to retrieve credit card transactions. Credit card
 * accounts have no IBAN and can therefore not be queried through HKKAZ (see
 * {@link \Fhp\Segment\KAZ\HKKAZv7}) or HKCAZ.
 *
 * There is no publicly available specification for this segment: DKKKU is an association-specific
 * ("verbandsspezifisch") business transaction of the Sparkassen-Finanzgruppe, and its specification
 * is only handed out by SIZ under a non-disclosure agreement. Unlike for the segments defined in the
 * FinTS specification, there is therefore no document to link to here.
 *
 * The field layout is instead derived from AqBanking's declarative definition (SEGdef
 * "GetTransactionsCreditCard", code DKKKU, version 2), the only public implementation-level
 * description we could find, and then corrected against real responses where it was wrong.
 * @link https://github.com/aqbanking/aqbanking/blob/master/src/libs/plugins/backends/aqhbci/ajobs/jobgettransactions.xml
 *
 * NOTE: The last two fields are missing from AqBanking's definition, see the notes on them.
 */
class DKKKUv2 extends BaseSegment implements Paginateable
{
    public KtvV3 $kontoverbindung;
    /**
     * Max length: 30. The account number, repeated outside the Kontoverbindung.
     *
     * Note that this is not necessarily a valid card number, see
     * {@link \Fhp\Model\CreditCardAccount::$accountNumber}.
     */
    public string $kontonummer;
    /** JJJJMMTT gemäß ISO 8601. NB: AqBanking lists toDate before fromDate. */
    public ?string $bisDatum = null;
    /** JJJJMMTT gemäß ISO 8601 */
    public ?string $vonDatum = null;
    /**
     * Only allowed if {@link ParameterKreditkartenumsaetze::$eingabeAnzahlEintraegeErlaubt} says so.
     *
     * AqBanking's definition does not list this field, but the DIKKUS parameters have a flag stating
     * whether it may be used, so the field has to exist. Confirmed by the bank rejecting a request
     * that put the pagination token in this position.
     */
    public ?int $maximaleAnzahlEintraege = null;
    /**
     * Max length: 35. The pagination token, called "Aufsetzpunkt" in the specification.
     *
     * Like {@link $maximaleAnzahlEintraege} this is missing from AqBanking's definition, so the
     * position mirrors HKKAZ, which ends in the same pair of fields.
     *
     * PARTIALLY VERIFIED: the tested bank does paginate (it answered a one year query with
     * "3040 Es liegen weitere Informationen vor" and a token of the form
     * "<card number>,<date>,<index>"), and it rejected a follow-up that omitted
     * {@link $maximaleAnzahlEintraege} with "9110 Ungueltige Auftragsnachricht: Unbekannter Aufbau".
     * That the layout below is accepted could not be confirmed yet, because the bank did not
     * paginate again on any later attempt with the same query.
     */
    public ?string $aufsetzpunkt = null;

    public static function create(KtvV3 $kontoverbindung, string $kontonummer, ?\DateTime $vonDatum, ?\DateTime $bisDatum, ?string $aufsetzpunkt = null): DKKKUv2
    {
        $result = DKKKUv2::createEmpty();
        $result->kontoverbindung = $kontoverbindung;
        $result->kontonummer = $kontonummer;
        $result->vonDatum = $vonDatum?->format('Ymd');
        $result->bisDatum = $bisDatum?->format('Ymd');
        $result->aufsetzpunkt = $aufsetzpunkt;
        return $result;
    }

    public function setPaginationToken(string $paginationToken)
    {
        $this->aufsetzpunkt = $paginationToken;
    }
}
