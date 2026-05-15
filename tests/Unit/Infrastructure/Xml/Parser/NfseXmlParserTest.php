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
                    <infNFSe>
                        <chNFSe>12345678901234567890123456789012345678901234567890</chNFSe>
                        <nNFSe>123</nNFSe>
                        <cVerif>1234</cVerif>
                        <serie>1</serie>
                        <dhEmi>2026-05-15T10:00:00-03:00</dhEmi>
                        <CNPJ>12345678000195</CNPJ>
                        <xNome>Empresa Teste LTDA</xNome>
                        <vServ>1000.00</vServ>
                        <vISS>50.00</vISS>
                    </infNFSe>
                </NFSe>
            </CompNFSe>
            XML;

        $result = $this->parser->parse($xml);

        $this->assertCount(1, $result);
        $this->assertSame('12345678901234567890123456789012345678901234567890', $result[0]['chaveAcesso']);
        $this->assertSame('123', $result[0]['numero']);
        $this->assertSame('1234', $result[0]['codigoVerificacao']);
        $this->assertSame('1', $result[0]['serie']);
        $this->assertSame('2026-05-15T10:00:00-03:00', $result[0]['dataEmissao']);
        $this->assertSame('12345678000195', $result[0]['prestador']['cnpj']);
        $this->assertSame('Empresa Teste LTDA', $result[0]['prestador']['nome']);
        $this->assertSame('1000.00', $result[0]['valorServicos']);
        $this->assertSame('50.00', $result[0]['valorIss']);
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
