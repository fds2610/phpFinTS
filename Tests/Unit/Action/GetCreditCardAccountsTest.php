<?php

namespace Fhp\Tests\Unit\Action;

use Fhp\Action\GetCreditCardAccounts;
use Fhp\Protocol\BPD;
use Fhp\Protocol\UPD;
use Fhp\Segment\BaseSegment;

/**
 * Tests that credit card accounts are recognized in the UPD.
 *
 * The HIUPD segments below mirror the structure of a real BW-Bank/LBBW response, with all account
 * numbers and names replaced. Note that the credit card account has no IBAN, which is why such
 * accounts are absent from HKSPA and have to be found here instead.
 */
class GetCreditCardAccountsTest extends \PHPUnit\Framework\TestCase
{
    /** Account type 50 (credit card), no IBAN, DKKKU among the permitted business transactions. */
    private const HIUPD_CREDIT_CARD =
        'HIUPD:12:6:4+5555000011112222::280:60050101++9999999999+50+EUR+Mustermann+Max+Example Goldcard Set++HKSAK:1+DKKKS:1+DKKKU:1+HKTAN:1\'';

    /** An ordinary current account: has an IBAN, permits HKKAZ but not DKKKU. */
    private const HIUPD_CURRENT_ACCOUNT =
        'HIUPD:10:6:4+1234567890::280:60050101+DE02600501011234567890+9999999999+1+EUR+Mustermann+Max+Example Giro++HKSAK:1+HKKAZ:1+HKSAL:1\'';

    /** A savings account, likewise without DKKKU. */
    private const HIUPD_SAVINGS_ACCOUNT =
        'HIUPD:11:6:4+2000130538::280:60050101+DE33600501012000130538+9999999999+10+EUR+Mustermann+Max+Example Sparkonto++HKSPA:1+HKKAZ:1\'';

    /** @param string[] $rawHiupds */
    private static function runAction(array $rawHiupds): GetCreditCardAccounts
    {
        $upd = new UPD();
        $upd->hiupd = array_map(function (string $raw) {
            return BaseSegment::parse($raw);
        }, $rawHiupds);

        $action = GetCreditCardAccounts::create();
        $action->getNextRequest(new BPD(), $upd);
        return $action;
    }

    public function testFindsOnlyTheCreditCardAccount()
    {
        $action = self::runAction([
            self::HIUPD_CURRENT_ACCOUNT,
            self::HIUPD_CREDIT_CARD,
            self::HIUPD_SAVINGS_ACCOUNT,
        ]);

        $accounts = $action->getAccounts();
        $this->assertCount(1, $accounts);

        $account = $accounts[0];
        $this->assertEquals('5555000011112222', $account->getAccountNumber());
        $this->assertEquals('60050101', $account->getBlz());
        $this->assertEquals(50, $account->getAccountType());
        $this->assertEquals('Example Goldcard Set', $account->getProductName());
        $this->assertEquals('EUR', $account->getCurrency());
        // Banks split the holder name across two fields.
        $this->assertEquals('Mustermann Max', $account->getName());
    }

    /** The data comes from the UPD, so no request to the bank is necessary. */
    public function testNeedsNoRequest()
    {
        $upd = new UPD();
        $upd->hiupd = [BaseSegment::parse(self::HIUPD_CREDIT_CARD)];

        $action = GetCreditCardAccounts::create();
        $requestSegments = $action->getNextRequest(new BPD(), $upd);

        $this->assertEmpty($requestSegments);
        $this->assertTrue($action->isDone());
        $this->assertCount(1, $action->getAccounts());
    }

    public function testReturnsNothingWithoutCreditCardAccounts()
    {
        $action = self::runAction([self::HIUPD_CURRENT_ACCOUNT, self::HIUPD_SAVINGS_ACCOUNT]);
        $this->assertCount(0, $action->getAccounts());
    }

    /**
     * HIUPD v4 does not report an account type at all, so the list of permitted business transactions
     * is the only criterion that works across versions.
     */
    public function testFindsAccountInOlderSegmentVersion()
    {
        $action = self::runAction([
            'HIUPD:9:4:4+5555000011112244::280:60050101+9999999999+EUR+Mustermann+Erika+Legacy Card++DKKKU:1\'',
        ]);

        $accounts = $action->getAccounts();
        $this->assertCount(1, $accounts);
        $this->assertEquals('5555000011112244', $accounts[0]->getAccountNumber());
        $this->assertNull($accounts[0]->getAccountType());
    }

    /** If the bank sends no list of permitted transactions, fall back to the account type. */
    public function testFallsBackToAccountType()
    {
        $action = self::runAction([
            'HIUPD:12:6:4+5555000011112255::280:60050101++9999999999+55+EUR+Mustermann+Max+Example Card\'',
        ]);

        $accounts = $action->getAccounts();
        $this->assertCount(1, $accounts);
        $this->assertEquals(55, $accounts[0]->getAccountType());
    }

    /** An explicit list that lacks DKKKU wins over the account type. */
    public function testAccountTypeDoesNotOverrideAnExplicitList()
    {
        $action = self::runAction([
            'HIUPD:12:6:4+5555000011112266::280:60050101++9999999999+50+EUR+Mustermann+Max+Example Card++HKSAK:1+HKTAN:1\'',
        ]);

        $this->assertCount(0, $action->getAccounts());
    }

    public function testRequiresUpd()
    {
        $this->expectException(\InvalidArgumentException::class);
        GetCreditCardAccounts::create()->getNextRequest(new BPD(), null);
    }
}
