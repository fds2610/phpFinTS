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
 * There is no official specification for this segment. The field layout is derived from AqBanking's
 * declarative definition (SEGdef "GetTransactionsCreditCard", code DKKKU, version 2).
 * @link https://github.com/aqbanking/aqbanking/blob/master/src/libs/plugins/backends/aqhbci/ajobs/jobgettransactions.xml
 *
 * TODO(BW-Bank): Verify the trailing Aufsetzpunkt field, which AqBanking does not list explicitly
 * (it handles pagination generically for jobs marked attachable="1").
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
    /** Max length: 35 */
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
