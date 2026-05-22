<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Infrastructure\Xml\Parser;

use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Parser\NfseXmlParser;
use PHPUnit\Framework\TestCase;

final class NfseXmlParserTest extends TestCase
{
    private NfseXmlParser $parser;

    protected function setUp(): void
    {
        $this->parser = new NfseXmlParser();
    }

    public function test_parse_valid_nfse_xml(): void
    {
        $xml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <CompNFSe xmlns="http://www.sped.fazenda.gov.br/nfse">
                <NFSe>
                    <infNFSe Id="NFSe12345678901234567890123456789012345678901234">
                        <nNFSe>123</nNFSe>
                        <ambGer>Sefin Nacional</ambGer>
                        <cStat>100</cStat>
                        <dhProc>2026-05-15T10:00:00-03:00</dhProc>
                        <emit>
                            <CNPJ>12345678000195</CNPJ>
                            <xNome>Empresa Teste LTDA</xNome>
                        </emit>
                        <valores>
                            <vLiq>950.00</vLiq>
                        </valores>
                        <DPS>
                            <infDPS>
                                <serie>1</serie>
                                <dhEmi>2026-05-15T10:00:00-03:00</dhEmi>
                                <valores>
                                    <vServPrest><vServ>1000.00</vServ></vServPrest>
                                    <trib>
                                        <tribMun><tribISSQN>1</tribISSQN></tribMun>
                                    </trib>
                                </valores>
                            </infDPS>
                        </DPS>
                    </infNFSe>
                </NFSe>
            </CompNFSe>
            XML;

        $result = $this->parser->parse($xml);

        $this->assertCount(1, $result);
        $this->assertSame('123', $result[0]['numero']);
        $this->assertSame('100', $result[0]['codigoStatus']);
        $this->assertSame('2026-05-15T10:00:00-03:00', $result[0]['dataHoraEmissaoNfse']);
        $this->assertSame('12345678000195', $result[0]['emit']['cnpj']);
        $this->assertSame('Empresa Teste LTDA', $result[0]['emit']['xNome']);
        $this->assertSame('950.00', $result[0]['valores']['vLiq']);
        $this->assertSame('1000.00', $result[0]['dps']['valores']['vServPrest']['vServ']);
        $this->assertSame('1', $result[0]['dps']['serie']);
    }

    public function test_parse_empty_xml(): void
    {
        $result = $this->parser->parse('');

        $this->assertSame([], $result);
    }

    public function test_parse_xml_with_errors(): void
    {
        $invalidXml = '<?xml version="1.0"?><invalid><xml>';

        $result = $this->parser->parse($invalidXml);

        $this->assertSame([], $result);
    }
}
