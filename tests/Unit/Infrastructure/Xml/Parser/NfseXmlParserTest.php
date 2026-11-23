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
     * emissão, confirma que o parser serve os dois fluxos com um único caminho,
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

    // ===== NT 009: campos renomeados e novos =====

    public function test_parse_valores_da_nfse_com_vcalc_ajuste_bc_issqn(): void
    {
        $xml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <NFSe xmlns="http://www.sped.fazenda.gov.br/nfse" versao="1.01">
                <infNFSe Id="NFS35503082211444777000161123456789012345678901234567">
                    <nNFSe>7</nNFSe>
                    <valores>
                        <vCalcAjusteBCISSQN>200.00</vCalcAjusteBCISSQN>
                        <vBC>800.00</vBC>
                        <vLiq>1000.00</vLiq>
                    </valores>
                </infNFSe>
            </NFSe>
            XML;

        $result = $this->parser->parse($xml);

        $this->assertSame('200.00', $result[0]['valores']['vCalcAjusteBCISSQN']);
        $this->assertArrayNotHasKey('vCalcDR', $result[0]['valores']);
    }

    public function test_parse_dps_com_fin_nfse_e_vajustebc(): void
    {
        $xml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <NFSe xmlns="http://www.sped.fazenda.gov.br/nfse" versao="1.01">
                <infNFSe Id="NFS35503082211444777000161123456789012345678901234567">
                    <DPS>
                        <infDPS>
                            <finNFSe>0</finNFSe>
                            <valores>
                                <vServPrest><vServ>1000.00</vServ></vServPrest>
                                <vAjusteBC><pAjusteBCISSQN>10.00</pAjusteBCISSQN></vAjusteBC>
                            </valores>
                        </infDPS>
                    </DPS>
                </infNFSe>
            </NFSe>
            XML;

        $dps = $this->parser->parse($xml)[0]['dps'];

        $this->assertSame('0', $dps['finNFSe']);
        $this->assertSame('10.00', $dps['valores']['vAjusteBC']['pAjusteBCISSQN']);
        $this->assertNull($dps['valores']['vAjusteBC']['vAjusteBCISSQN']);
        $this->assertArrayNotHasKey('vDedRed', $dps['valores']);
    }

    public function test_parse_ibscbs_da_nfse_com_ajustes_de_base_de_calculo(): void
    {
        $xml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <NFSe xmlns="http://www.sped.fazenda.gov.br/nfse" versao="1.01">
                <infNFSe Id="NFS35503082211444777000161123456789012345678901234567">
                    <IBSCBS>
                        <cLocalidadeIncid>3550308</cLocalidadeIncid>
                        <xLocalidadeIncid>Sao Paulo</xLocalidadeIncid>
                        <valores>
                            <vBC>900.00</vBC>
                            <vCalcAjusteBCLocImoveis>50.00</vCalcAjusteBCLocImoveis>
                            <vCalcAjusteBCIBSCBS>100.00</vCalcAjusteBCIBSCBS>
                        </valores>
                        <totCIBS><vTotNF>1000.00</vTotNF></totCIBS>
                    </IBSCBS>
                </infNFSe>
            </NFSe>
            XML;

        $valores = $this->parser->parse($xml)[0]['ibscbs']['valores'];

        $this->assertSame('900.00', $valores['vBC']);
        $this->assertSame('50.00', $valores['vCalcAjusteBCLocImoveis']);
        $this->assertSame('100.00', $valores['vCalcAjusteBCIBSCBS']);
        $this->assertArrayNotHasKey('vCalcReeRepRes', $valores);
    }

    public function test_parse_ibscbs_sem_tributacao_tolera_grupos_ausentes(): void
    {
        // CST 400/410/820: a NFS-e pode vir sem cLocalidadeIncid, valores, gIBS e gCBS (todos opcionais no XSD).
        $xml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <NFSe xmlns="http://www.sped.fazenda.gov.br/nfse" versao="1.01">
                <infNFSe Id="NFS35503082211444777000161123456789012345678901234567">
                    <IBSCBS>
                        <totCIBS><vTotNF>1000.00</vTotNF></totCIBS>
                    </IBSCBS>
                </infNFSe>
            </NFSe>
            XML;

        $ibscbs = $this->parser->parse($xml)[0]['ibscbs'];

        $this->assertNull($ibscbs['cLocalidadeIncid']);
        $this->assertNull($ibscbs['valores']['vBC']);
        $this->assertNull($ibscbs['valores']['vCalcAjusteBCIBSCBS']);
        $this->assertNull($ibscbs['totCIBS']['gIBS']);
        $this->assertNull($ibscbs['totCIBS']['gCBS']);
        $this->assertSame('1000.00', $ibscbs['totCIBS']['vTotNF']);
    }

    public function test_parse_nfse_com_cnpj_alfanumerico_no_emitente(): void
    {
        $xml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <NFSe xmlns="http://www.sped.fazenda.gov.br/nfse" versao="1.01">
                <infNFSe Id="NFS35503082212ABC34501DE35123456789012345678901234567">
                    <nNFSe>9</nNFSe>
                    <emit>
                        <CNPJ>12ABC34501DE35</CNPJ>
                        <xNome>Empresa Alfa SA</xNome>
                    </emit>
                </infNFSe>
            </NFSe>
            XML;

        $result = $this->parser->parse($xml)[0];

        $this->assertSame('12ABC34501DE35', $result['emit']['cnpj']);
        $this->assertSame('NFS35503082212ABC34501DE35123456789012345678901234567', $result['id']);
    }
}
