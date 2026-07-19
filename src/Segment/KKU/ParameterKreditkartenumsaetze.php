<?php
/** @noinspection PhpUnused */

namespace Fhp\Segment\KKU;

use Fhp\Segment\BaseDeg;

/**
 * Data Element Group: Parameter Kreditkartenumsätze
 *
 * There is no specification document to link to, see {@link DKKKUv2}. The structure was derived from
 * a real BW-Bank BPD, which sends `DIKKUS:45:2:3+1+1+0+90:N:J`, i.e. the same three parameters that
 * the regular account statement transaction uses (see
 * {@link \Fhp\Segment\KAZ\ParameterKontoumsaetzeV2}).
 */
class ParameterKreditkartenumsaetze extends BaseDeg
{
    /** Positive, number of days for which the bank retains the transactions. */
    public int $speicherzeitraum;
    public bool $eingabeAnzahlEintraegeErlaubt;
    public bool $alleKontenErlaubt;
}
