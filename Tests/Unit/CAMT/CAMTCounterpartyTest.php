<?php

namespace Fhp\Tests\Unit\CAMT;

use Fhp\CAMT\CAMT;
use Fhp\MT940\MT940;
use PHPUnit\Framework\TestCase;

class CAMTCounterpartyTest extends TestCase
{
    private const NS_FYRST = 'urn:iso:std:iso:20022:tech:xsd:camt.052.001.08';
    private const NS_COMMERZBANK = 'urn:iso:std:iso:20022:tech:xsd:camt.052.001.08';

    public function testFyrstOutgoingTransactionUsesCreditorAsCounterparty(): void
    {
        $parser = new CAMT();
        $result = $parser->parse([$this->buildCamtXmlFyrst()]);

        $this->assertArrayHasKey('2026-04-13', $result);
        $transaction = $result['2026-04-13']['transactions'][0];

        $this->assertSame(MT940::CD_DEBIT, $transaction['credit_debit']);
        $this->assertSame(3000.0, $transaction['amount']);
        $this->assertSame('Peter Parker', $transaction['description']['name']);
        $this->assertSame('DE17100500000123456789', $transaction['description']['account_number']);
    }

    public function testCommerzbankIncomingTransactionUsesDebtorAsCounterparty(): void
    {
        $parser = new CAMT();
        $result = $parser->parse([$this->buildCamtXmlCommerzbank()]);

        $this->assertArrayHasKey('2026-06-23', $result);
        $transaction = $result['2026-06-23']['transactions'][0];

        $this->assertSame(MT940::CD_CREDIT, $transaction['credit_debit']);
        $this->assertSame(10.0, $transaction['amount']);
        $this->assertSame('Bob Smith', $transaction['description']['name']);
        $this->assertSame('DE17100500000123456789', $transaction['description']['account_number']);
        $this->assertSame('BELADEBEXXX', $transaction['description']['bank_code']);
    }

    private function buildCamtXmlFyrst(): string
    {
        $ns = self::NS_FYRST;

        return <<<XML
<Document xmlns="{$ns}">
  <BkToCstmrAcctRpt>
    <Rpt>
      <Ntry>
        <Amt Ccy="EUR">3000</Amt>
        <CdtDbtInd>DBIT</CdtDbtInd>
        <Sts><Cd>BOOK</Cd></Sts>
        <BookgDt><Dt>2026-04-13</Dt></BookgDt>
        <ValDt><Dt>2026-04-13</Dt></ValDt>
        <NtryDtls>
          <TxDtls>
            <RltdPties>
              <Cdtr><Pty><Nm>Peter Parker</Nm></Pty></Cdtr>
              <CdtrAcct><Id><IBAN>DE17100500000123456789</IBAN></Id></CdtrAcct>
            </RltdPties>
          </TxDtls>
        </NtryDtls>
      </Ntry>
    </Rpt>
  </BkToCstmrAcctRpt>
</Document>
XML;
    }

    private function buildCamtXmlCommerzbank(): string
    {
        $ns = self::NS_COMMERZBANK;

        return <<<XML
<Document xmlns="{$ns}">
  <BkToCstmrAcctRpt>
    <Rpt>
      <Ntry>
        <Amt Ccy="EUR">10.00</Amt>
        <CdtDbtInd>CRDT</CdtDbtInd>
        <Sts><Cd>BOOK</Cd></Sts>
        <BookgDt><Dt>2026-06-23</Dt></BookgDt>
        <ValDt><Dt>2026-06-23</Dt></ValDt>
        <NtryDtls>
          <TxDtls>
            <RltdPties>
              <Dbtr><Pty><Nm>Bob Smith</Nm></Pty></Dbtr>
              <DbtrAcct><Id><IBAN>DE17100500000123456789</IBAN></Id></DbtrAcct>
              <Cdtr><Pty><Nm>Alice Wonderland</Nm></Pty></Cdtr>
              <CdtrAcct><Id><IBAN>DE40120400000123456789</IBAN></Id></CdtrAcct>
            </RltdPties>
            <RltdAgts>
              <DbtrAgt><FinInstnId><BICFI>BELADEBEXXX</BICFI></FinInstnId></DbtrAgt>
              <CdtrAgt><FinInstnId><BICFI>COBADEFFXXX</BICFI></FinInstnId></CdtrAgt>
            </RltdAgts>
          </TxDtls>
        </NtryDtls>
      </Ntry>
    </Rpt>
  </BkToCstmrAcctRpt>
</Document>
XML;
    }
}
