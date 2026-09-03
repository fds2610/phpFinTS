<?php

namespace Fhp\Tests\Unit\Segment;

use Fhp\Segment\BME\HIBMESv1;
use Fhp\Segment\DME\HIDMESv1;
use Fhp\Segment\DSE\MinimaleVorlaufzeitSEPALastschrift;

class MinimaleVorlaufzeitSEPALastschriftTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Real response from Postbank, as also used by the Postbank integration tests. Both segments
     * are only offered in version 1, which is what the parameters below describe: one day of lead
     * time for FNAL/RCUR and for FRST/OOFF, at most 30 days.
     */
    public const REAL_POSTBANK_HIDMES = "HIDMES:36:1:3+1+1+0+1:30:1:30:1000:J:J'";
    public const REAL_POSTBANK_HIBMES = "HIBMES:37:1:3+1+1+0+1:30:1:30:1000:J:J'";

    public function testGetMinimalLeadTimeFromVersion1()
    {
        $parameter = HIDMESv1::parse(static::REAL_POSTBANK_HIDMES)->getParameter();

        $recurring = $parameter->getMinimalLeadTime('RCUR');
        $this->assertInstanceOf(MinimaleVorlaufzeitSEPALastschrift::class, $recurring);
        $this->assertEquals(1, $recurring->minimaleSEPAVorlaufzeit);

        // Version 1 states the lead time per sequence type and carries neither of the two codes
        // that version 2 encodes, so they stay unset.
        $this->assertNull($recurring->unterstuetzteSEPALastschriftartenCodiert);
        $this->assertNull($recurring->sequenceTypeCodiert);

        $this->assertEquals(1, $parameter->getMinimalLeadTime('FRST')->minimaleSEPAVorlaufzeit);
    }

    public function testGetMinimalLeadTimeFromVersion1B2B()
    {
        $parameter = HIBMESv1::parse(static::REAL_POSTBANK_HIBMES)->getParameter();

        $this->assertEquals(1, $parameter->getMinimalLeadTime('RCUR')->minimaleSEPAVorlaufzeit);
        $this->assertEquals(1, $parameter->getMinimalLeadTime('OOFF')->minimaleSEPAVorlaufzeit);
    }

    /**
     * The B2B variant of the coded format has no field for the direct debit type, so the parser
     * passes none.
     */
    public function testParseCodedB2B()
    {
        $parsed = MinimaleVorlaufzeitSEPALastschrift::parseCodedB2B('1;2;120000');

        $this->assertEquals(2, $parsed['B2B']['RCUR']->minimaleSEPAVorlaufzeit);
        $this->assertEquals('120000', $parsed['B2B']['FNAL']->cutOffZeit);
        $this->assertNull($parsed['B2B']['RCUR']->unterstuetzteSEPALastschriftartenCodiert);
        $this->assertEquals(1, $parsed['B2B']['RCUR']->sequenceTypeCodiert);
    }

    public function testParseCoded()
    {
        $parsed = MinimaleVorlaufzeitSEPALastschrift::parseCoded('0;1;2;120000');

        $this->assertEquals(2, $parsed['CORE']['RCUR']->minimaleSEPAVorlaufzeit);
        $this->assertEquals(0, $parsed['CORE']['RCUR']->unterstuetzteSEPALastschriftartenCodiert);
        $this->assertEquals(1, $parsed['CORE']['RCUR']->sequenceTypeCodiert);
    }
}
