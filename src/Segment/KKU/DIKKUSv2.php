<?php
/** @noinspection PhpUnused */

namespace Fhp\Segment\KKU;

use Fhp\Segment\BaseGeschaeftsvorfallparameter;

/**
 * Segment: Kreditkartenumsätze Parameter (Version 2)
 *
 * AqBanking does not define a params segment for DKKKU. The layout was derived from a real BW-Bank
 * BPD, which sends `DIKKUS:45:2:3+1+1+0+90:N:J`.
 */
class DIKKUSv2 extends BaseGeschaeftsvorfallparameter implements DIKKUS
{
    public ParameterKreditkartenumsaetze $parameter;

    public function getParameter(): ParameterKreditkartenumsaetze
    {
        return $this->parameter;
    }
}
