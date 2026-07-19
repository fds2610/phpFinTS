<?php

namespace Fhp\Segment\KKU;

use Fhp\Segment\SegmentInterface;

/**
 * Segment: Kreditkartenumsätze Parameter
 *
 * BPD parameter segment for the DKKKU business transaction. Its presence in the BPD indicates that
 * the bank supports credit card statement retrieval. No transaction-specific parameters beyond the
 * base {@link \Fhp\Segment\BaseGeschaeftsvorfallparameter} are known.
 */
interface DIKKUS extends SegmentInterface
{
}
