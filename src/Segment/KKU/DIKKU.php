<?php

namespace Fhp\Segment\KKU;

use Fhp\Segment\Common\Sdo;
use Fhp\Segment\SegmentInterface;

/**
 * Segment: Kreditkartenumsätze rückmelden
 *
 * Common interface across all versions of the DIKKU response segment.
 */
interface DIKKU extends SegmentInterface
{
    /** @return string The credit card / account number this statement belongs to. */
    public function getKontonummer(): string;

    /** @return Sdo|null The booked balance, if the bank reported one. */
    public function getSaldo(): ?Sdo;

    /** @return Kreditkartenumsatz[] The transaction records (may be empty). */
    public function getUmsaetze(): array;
}
