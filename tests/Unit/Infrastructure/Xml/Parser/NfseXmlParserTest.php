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

    /**
     * Formato canônico da Sefin Nacional: <NFSe> na raiz (TCNFSe = infNFSe +
     * Signature), com a DPS embutida. Mesma raiz na emissão e na consulta.
     */
    public function test_parse_valid_nfse_xml(): void
    {
        $xml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <NFSe xmlns="http://www.sped.fazenda.gov.br/nfse" versao="1.01">
                <infNFSe Id="NFS12345678901234567890123456789012345678901234567890">
                    <nNFSe>123</nNFSe>
                    <ambGer>2</ambGer>
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
            XML;

        $result = $this->parser->parse($xml);

        $this->assertCount(1, $result);
        $this->assertSame('1.01', $result[0]['versao']);
        $this->assertSame('123', $result[0]['numero']);
        $this->assertSame('100', $result[0]['codigoStatus']);
        $this->assertSame('2026-05-15T10:00:00-03:00', $result[0]['dataHoraEmissaoNfse']);
        $this->assertSame('12345678000195', $result[0]['emit']['cnpj']);
        $this->assertSame('Empresa Teste LTDA', $result[0]['emit']['xNome']);
        $this->assertSame('950.00', $result[0]['valores']['vLiq']);
        $this->assertSame('1000.00', $result[0]['dps']['valores']['vServPrest']['vServ']);
        $this->assertSame('1', $result[0]['dps']['serie']);
    }

    /**
     * Resposta real da EMISSÃO (<NFSe> na raiz). XML reduzido de um retorno real
     * da SEFIN (SefinNacional_1.6.0), preservando o Id e o nNFSe reais.
     */
    public function test_parse_nfse_emissao_real(): void
    {
        $xml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <NFSe xmlns="http://www.sped.fazenda.gov.br/nfse" versao="1.01">
                <infNFSe Id="NFS13026032218586475000177000000000019926060970939220">
                    <xLocEmi>Manaus</xLocEmi>
                    <nNFSe>199</nNFSe>
                    <verAplic>SefinNacional_1.6.0</verAplic>
                    <ambGer>2</ambGer>
                    <cStat>100</cStat>
                    <dhProc>2026-06-11T14:20:09-03:00</dhProc>
                    <nDFSe>14616</nDFSe>
                    <emit>
                        <CNPJ>18586475000177</CNPJ>
                        <xNome>ATX COMERCIO E SERVICOS DE INFORMATICA LTDA</xNome>
                    </emit>
                    <valores>
                        <vLiq>0.01</vLiq>
                    </valores>
                </infNFSe>
            </NFSe>
            XML;

        $result = $this->parser->parse($xml);

        $this->assertCount(1, $result);
        $this->assertSame('1.01', $result[0]['versao']);
        $this->assertSame('NFS13026032218586475000177000000000019926060970939220', $result[0]['id']);
        $this->assertSame('199', $result[0]['numero']);
        $this->assertSame('100', $result[0]['codigoStatus']);
        $this->assertSame('18586475000177', $result[0]['emit']['cnpj']);
        $this->assertSame('0.01', $result[0]['valores']['vLiq']);
    }

    /**
     * Resposta real da CONSULTA (consultarPorChave). Tem a MESMA raiz <NFSe> da
     * emissão — confirma que o parser serve os dois fluxos com um único caminho,
     * sem nenhum envelope <CompNFSe> (inexistente nesta API).
     */
    public function test_parse_nfse_consulta_real_mesma_raiz_da_emissao(): void
    {
        $xml = <<<XML
            <?xml version="1.0" encoding="utf-8"?>
            <NFSe versao="1.01" xmlns="http://www.sped.fazenda.gov.br/nfse">
                <infNFSe Id="NFS13026032218586475000177000000000019926060970939220">
                    <nNFSe>199</nNFSe>
                    <cStat>100</cStat>
                </infNFSe>
            </NFSe>
            XML;

        $result = $this->parser->parse($xml);

        $this->assertCount(1, $result);
        $this->assertSame('1.01', $result[0]['versao']);
        $this->assertSame('NFS13026032218586475000177000000000019926060970939220', $result[0]['id']);
        $this->assertSame('199', $result[0]['numero']);
        $this->assertSame('100', $result[0]['codigoStatus']);
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
