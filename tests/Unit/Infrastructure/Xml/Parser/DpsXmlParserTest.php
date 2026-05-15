<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Infrastructure\Xml\Parser;

use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Parser\DpsXmlParser;
use PHPUnit\Framework\TestCase;

final class DpsXmlParserTest extends TestCase
{
    private DpsXmlParser $parser;

    protected function setUp(): void
    {
        $this->parser = new DpsXmlParser();
    }

    public function test_parse_valid_dps_xml(): void
    {
        $chave = '12345678901234567890123456789012345678901234567890';
        $xml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <DPS xmlns="http://www.sped.fazenda.gov.br/nfse">
                <infDPS Id="{$chave}">
                    <tpAmb>2</tpAmb>
                    <dhEmi>2026-05-15T10:00:00-03:00</dhEmi>
                    <serie>1</serie>
                    <nDPS>123</nDPS>
                    <dCompet>2026-05-01</dCompet>
                    <tpEmit>1</tpEmit>
                    <cLocEmi>3550308</cLocEmi>
                </infDPS>
            </DPS>
            XML;

        $result = $this->parser->parse($xml);

        $this->assertIsArray($result);
        $this->assertSame($chave, $result['chaveAcesso']);
        $this->assertSame('2', $result['tipoAmbiente']);
        $this->assertSame('2026-05-15T10:00:00-03:00', $result['dataEmissao']);
        $this->assertSame('1', $result['serie']);
        $this->assertSame('123', $result['numero']);
        $this->assertSame('2026-05-01', $result['dataCompetencia']);
        $this->assertSame('1', $result['tipoEmissao']);
        $this->assertSame('3550308', $result['codigoMunicipio']);
    }
}
