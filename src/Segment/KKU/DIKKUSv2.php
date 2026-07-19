<?php
/** @noinspection PhpUnused */

namespace Fhp\Segment\KKU;

use Fhp\Segment\BaseGeschaeftsvorfallparameter;

/**
 * Segment: Kreditkartenumsätze Parameter (Version 2)
 *
 * AqBanking does not define a params segment for DKKKU, so this carries only the base
 * Geschäftsvorfallparameter (maximaleAnzahlAuftraege, anzahlSignaturenMindestens, sicherheitsklasse).
 *
 * TODO(BW-Bank): Verify against the real BPD. If the bank sends additional parameter fields, add a
 * dedicated parameter DEG here; if it uses the FinTS 2.2 format (without sicherheitsklasse), switch
 * the base class to {@link \Fhp\Segment\BaseGeschaeftsvorfallparameterOld}.
 */
class DIKKUSv2 extends BaseGeschaeftsvorfallparameter implements DIKKUS
{
}
