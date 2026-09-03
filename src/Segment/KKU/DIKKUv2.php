<?php
/** @noinspection PhpUnused */

namespace Fhp\Segment\KKU;

use Fhp\Segment\BaseSegment;
use Fhp\Segment\Common\Sdo;

/**
 * Segment: Kreditkartenumsätze rückmelden (Version 2)
 *
 * Response to {@link DKKKUv2}. There will be one segment instance per request segment.
 *
 * There is no official specification. The field layout is derived from AqBanking's declarative
 * definition (SEGdef "TransactionsCreditCard", version 2).
 * @link https://github.com/aqbanking/aqbanking/blob/master/src/libs/plugins/backends/aqhbci/ajobs/jobgettransactions.xml
 *
 * TODO(BW-Bank): Verify against a real response. The three "unbekannt*" fields and the exact
 * structure of the (optional) Saldo are guesses; a misaligned Saldo would shift all following fields.
 */
class DIKKUv2 extends BaseSegment implements DIKKU
{
    /** Max length: 30 */
    public string $kontonummer;
    /** Semantics unknown (AqBanking: "unknown1"). Max length: 30 */
    public ?string $unbekannt1 = null;
    /** The booked balance ("booked"), optional. */
    public ?Sdo $saldo = null;
    /** Semantics unknown (AqBanking: "unknown2"). Max length: 30 */
    public ?string $unbekannt2 = null;
    /** Semantics unknown (AqBanking: "unknown3"). Max length: 30 */
    public ?string $unbekannt3 = null;
    /** @var Kreditkartenumsatz[]|null @Max(999) */
    public ?array $umsaetze = null;

    public function getKontonummer(): string
    {
        return $this->kontonummer;
    }

    public function getSaldo(): ?Sdo
    {
        return $this->saldo;
    }

    public function getUmsaetze(): array
    {
        return $this->umsaetze ?? [];
    }
}
