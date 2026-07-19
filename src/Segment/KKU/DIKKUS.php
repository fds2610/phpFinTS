<?php

namespace Fhp\Segment\KKU;

use Fhp\Segment\SegmentInterface;

/**
 * Segment: Kreditkartenumsätze Parameter
 *
 * BPD parameter segment for the DKKKU business transaction. Its presence in the BPD indicates that
 * the bank supports credit card statement retrieval.
 */
interface DIKKUS extends SegmentInterface
{
    public function getParameter(): ParameterKreditkartenumsaetze;
}
