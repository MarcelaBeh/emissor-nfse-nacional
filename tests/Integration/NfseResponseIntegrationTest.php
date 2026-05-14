<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Integration;

use MarcelaBeh\EmissorNfseNacional\Application\Exception\ValidationException;
use MarcelaBeh\EmissorNfseNacional\Application\Validator\IbscbsResponseValidator;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Repository\InMemoryCstClassTribRepository;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Parser\NfseXmlParser;
use PHPUnit\Framework\TestCase;

final class NfseResponseIntegrationTest extends TestCase
{
    private NfseXmlParser $parser;
    private IbscbsResponseValidator $validator;
    private IbscbsResponseValidator $validatorWithRepo;

    protected function setUp(): void
    {
        $this->parser = new NfseXmlParser();
        $this->validator = new IbscbsResponseValidator();
        $this->validatorWithRepo = new IbscbsResponseValidator(
            new InMemoryCstClassTribRepository(),
        );
    }

    public function test_parse_and_validate_complete_nfse_response(): void
    {
        $xml = $this->createCompleteNfseResponseXml();
        $parsed = $this->parser->parse($xml);

        $this->assertCount(1, $parsed);
        $this->assertArrayHasKey('ibscbs', $parsed[0]);
        $this->assertArrayHasKey('valores', $parsed[0]['ibscbs']);
        $this->assertArrayHasKey('totCIBS', $parsed[0]['ibscbs']);

        $ibsData = [
            'tpEnteGov' => null,
            'cClassTrib' => '100123',
            'cCredPres' => null,
            'vServ' => '1000.00',
            'diferimento' => [],
        ];

        $this->validatorWithRepo->validate($ibsData, $parsed[0]['ibscbs']);
    }

    public function test_parse_and_validate_nfse_with_credito_presumido(): void
    {
        $xml = $this->createNfseResponseWithCreditoPresumido();
        $parsed = $this->parser->parse($xml);

        $this->assertCount(1, $parsed);
        $ibscbs = $parsed[0]['ibscbs'];

        $this->assertNotNull($ibscbs['totCIBS']['gIBS']['gIBSCredPres']);
        $this->assertNotNull($ibscbs['totCIBS']['gCBS']['gCBSCredPres']);

        $ibsData = [
            'tpEnteGov' => null,
            'cClassTrib' => '100123',
            'cCredPres' => '01',
            'vServ' => '1000.00',
            'diferimento' => [],
        ];

        $this->validatorWithRepo->validate($ibsData, $ibscbs);
    }

    public function test_parse_and_validate_nfse_with_diferimento(): void
    {
        $xml = $this->createNfseResponseWithDiferimento();
        $parsed = $this->parser->parse($xml);

        $this->assertCount(1, $parsed);
        $ibscbs = $parsed[0]['ibscbs'];

        $ibsData = [
            'tpEnteGov' => null,
            'cClassTrib' => '100123',
            'cCredPres' => null,
            'vServ' => '1000.00',
            'diferimento' => [
                'pDifUF' => 10.0,
                'pDifMun' => 5.0,
                'pDifCBS' => 8.0,
            ],
        ];

        $this->validatorWithRepo->validate($ibsData, $ibscbs);
    }

    public function test_parse_and_validate_nfse_with_v_calc_ree_rep_res(): void
    {
        $xml = $this->createNfseResponseWithReeRepRes();
        $parsed = $this->parser->parse($xml);

        $this->assertCount(1, $parsed);
        $ibscbs = $parsed[0]['ibscbs'];

        $this->assertSame('500.00', $ibscbs['valores']['vCalcReeRepRes']);

        $ibsData = [
            'tpEnteGov' => null,
            'cClassTrib' => '100123',
            'cCredPres' => null,
            'vServ' => '1000.00',
            'refNFSeList' => ['12345678901234567890123456789012345678901234567890'],
        ];

        $this->validatorWithRepo->validate($ibsData, $ibscbs);
    }

    public function test_parse_throws_when_v_calc_ree_rep_res_exceeds_v_serv(): void
    {
        $xml = $this->createNfseResponseWithReeRepRes(vCalcReeRepRes: '1000.00');
        $parsed = $this->parser->parse($xml);

        $ibsData = [
            'tpEnteGov' => null,
            'cClassTrib' => '100123',
            'cCredPres' => null,
            'vServ' => '1000.00',
            'refNFSeList' => ['12345678901234567890123456789012345678901234567890'],
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1534');
        $this->validatorWithRepo->validate($ibsData, $parsed[0]['ibscbs']);
    }

    public function test_parse_throws_when_p_red_aliq_uf_missing_with_reducao(): void
    {
        $xml = $this->createNfseResponseCustom(
            pRedAliqUF: null,
            pRedAliqMun: '50.00',
            pRedAliqCBS: '50.00',
        );
        $parsed = $this->parser->parse($xml);

        $ibsData = [
            'tpEnteGov' => '1',
            'cClassTrib' => '200001',
            'cCredPres' => null,
            'vServ' => '1000.00',
            'diferimento' => [],
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1541');
        $this->validatorWithRepo->validate($ibsData, $parsed[0]['ibscbs']);
    }

    public function test_parse_single_nfse_response(): void
    {
        $xml = $this->createCompleteNfseResponseXml();
        $parsed = $this->parser->parse($xml);

        $this->assertCount(1, $parsed);
        $this->assertSame('1000.00', $parsed[0]['valorServicos']);
    }

    public function test_parse_invalid_xml_returns_empty_array(): void
    {
        $parsed = $this->parser->parse('not xml');
        $this->assertSame([], $parsed);
    }

    public function test_parse_empty_xml_returns_empty_array(): void
    {
        $parsed = $this->parser->parse('');
        $this->assertSame([], $parsed);
    }

    public function test_parse_whitespace_xml_returns_empty_array(): void
    {
        $parsed = $this->parser->parse('   ');
        $this->assertSame([], $parsed);
    }

    public function test_parse_nfse_without_ibscbs_returns_null(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<CompNFSe xmlns="http://www.sped.fazenda.gov.br/nfse">'
            . '<NFSe><infNFSe Id="NFSe12345678901234567890123456789012345678901234">'
            . '<chNFSe>12345678901234567890123456789012345678901234</chNFSe>'
            . '<nNFSe>123</nNFSe>'
            . '<cVerif>ABC123</cVerif>'
            . '<serie>1</serie>'
            . '<dhEmi>2026-06-15T10:00:00-03:00</dhEmi>'
            . '<CNPJ>11444777000161</CNPJ>'
            . '<xNome>Prestador Ltda</xNome>'
            . '<vServ>1000.00</vServ>'
            . '<vISS>50.00</vISS>'
            . '</infNFSe></NFSe></CompNFSe>';

        $parsed = $this->parser->parse($xml);

        $this->assertCount(1, $parsed);
        $this->assertArrayNotHasKey('ibscbs', $parsed[0]);
    }

    public function test_parse_extracts_basic_fields_correctly(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<CompNFSe xmlns="http://www.sped.fazenda.gov.br/nfse">'
            . '<NFSe><infNFSe Id="NFSe12345678901234567890123456789012345678901234">'
            . '<chNFSe>12345678901234567890123456789012345678901234</chNFSe>'
            . '<nNFSe>456</nNFSe>'
            . '<cVerif>XYZ789</cVerif>'
            . '<serie>2</serie>'
            . '<dhEmi>2026-06-15T14:30:00-03:00</dhEmi>'
            . '<CNPJ>11444777000161</CNPJ>'
            . '<xNome>Prestador Ltda</xNome>'
            . '<vServ>2500.00</vServ>'
            . '<vISS>125.00</vISS>'
            . $this->buildIbscbsXml(null, null, null, null, null, null, null, null)
            . '</infNFSe></NFSe></CompNFSe>';

        $parsed = $this->parser->parse($xml);
        $this->assertCount(1, $parsed);

        $nfse = $parsed[0];
        $this->assertSame('12345678901234567890123456789012345678901234', $nfse['chaveAcesso']);
        $this->assertSame('456', $nfse['numero']);
        $this->assertSame('XYZ789', $nfse['codigoVerificacao']);
        $this->assertSame('2', $nfse['serie']);
        $this->assertSame('2500.00', $nfse['valorServicos']);
        $this->assertSame('125.00', $nfse['valorIss']);
    }

    // ─── Helper methods ────────────────────────────────────────────────

    private function createCompleteNfseResponseXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<CompNFSe xmlns="http://www.sped.fazenda.gov.br/nfse">'
            . $this->buildInfNfse('1', '1000.00')
            . '</CompNFSe>';
    }

    private function createNfseResponseWithCreditoPresumido(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<CompNFSe xmlns="http://www.sped.fazenda.gov.br/nfse">'
            . '<NFSe><infNFSe Id="NFSe12345678901234567890123456789012345678901234">'
            . '<chNFSe>12345678901234567890123456789012345678901234</chNFSe>'
            . '<nNFSe>123</nNFSe><cVerif>ABC123</cVerif><serie>1</serie>'
            . '<dhEmi>2026-06-15T10:00:00-03:00</dhEmi>'
            . '<CNPJ>11444777000161</CNPJ><xNome>Prestador Ltda</xNome>'
            . '<vServ>1000.00</vServ><vISS>50.00</vISS>'
            . $this->buildIbscbsXml(
                pRedutor: null,
                gIbsCredPres: ['pCredPresIBS' => '10.00', 'vCredPresIBS' => '100.00'],
                gCbsCredPres: ['pCredPresCBS' => '5.00', 'vCredPresCBS' => '50.00'],
            )
            . '</infNFSe></NFSe></CompNFSe>';
    }

    private function createNfseResponseWithDiferimento(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<CompNFSe xmlns="http://www.sped.fazenda.gov.br/nfse">'
            . '<NFSe><infNFSe Id="NFSe12345678901234567890123456789012345678901234">'
            . '<chNFSe>12345678901234567890123456789012345678901234</chNFSe>'
            . '<nNFSe>123</nNFSe><cVerif>ABC123</cVerif><serie>1</serie>'
            . '<dhEmi>2026-06-15T10:00:00-03:00</dhEmi>'
            . '<CNPJ>11444777000161</CNPJ><xNome>Prestador Ltda</xNome>'
            . '<vServ>1000.00</vServ><vISS>50.00</vISS>'
            . $this->buildIbscbsXml(
                pRedutor: null,
                vDifUF: '50.00',
                vDifMun: '25.00',
                vDifCBS: '40.00',
            )
            . '</infNFSe></NFSe></CompNFSe>';
    }

    private function createNfseResponseWithReeRepRes(string $vCalcReeRepRes = '500.00'): string
    {
        $ufXml = '<uf><pIBSUF>10.00</pIBSUF><pAliqEfetUF>10.00</pAliqEfetUF></uf>';
        $munXml = '<mun><pIBSMun>5.00</pIBSMun><pAliqEfetMun>5.00</pAliqEfetMun></mun>';
        $fedXml = '<fed><pCBS>8.00</pCBS><pAliqEfetCBS>8.00</pAliqEfetCBS></fed>';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<CompNFSe xmlns="http://www.sped.fazenda.gov.br/nfse">'
            . '<NFSe><infNFSe Id="NFSe12345678901234567890123456789012345678901234">'
            . '<chNFSe>12345678901234567890123456789012345678901234</chNFSe>'
            . '<nNFSe>123</nNFSe><cVerif>ABC123</cVerif><serie>1</serie>'
            . '<dhEmi>2026-06-15T10:00:00-03:00</dhEmi>'
            . '<CNPJ>11444777000161</CNPJ><xNome>Prestador Ltda</xNome>'
            . '<vServ>1000.00</vServ><vISS>50.00</vISS>'
            . '<IBSCBS>'
            . '<cLocalidadeIncid>3550308</cLocalidadeIncid>'
            . '<xLocalidadeIncid>Sao Paulo</xLocalidadeIncid>'
            . '<valores><vBC>1000.00</vBC>'
            . "<vCalcReeRepRes>{$vCalcReeRepRes}</vCalcReeRepRes>"
            . $ufXml . $munXml . $fedXml
            . '</valores>'
            . '<totCIBS>'
            . '<vTotNF>1500.00</vTotNF>'
            . '<gIBS><vIBSTot>500.00</vIBSTot>'
            . '<gIBSUFTot><vIBSUF>300.00</vIBSUF></gIBSUFTot>'
            . '<gIBSMunTot><vIBSMun>200.00</vIBSMun></gIBSMunTot>'
            . '</gIBS>'
            . '<gCBS><vCBS>100.00</vCBS></gCBS>'
            . '</totCIBS>'
            . '</IBSCBS>'
            . '</infNFSe></NFSe></CompNFSe>';
    }

    private function createNfseResponseCustom(
        ?string $pRedAliqUF,
        ?string $pRedAliqMun,
        ?string $pRedAliqCBS,
    ): string {
        $ufXml = '<uf><pIBSUF>10.00</pIBSUF>'
            . ($pRedAliqUF !== null ? "<pRedAliqUF>{$pRedAliqUF}</pRedAliqUF>" : '')
            . '<pAliqEfetUF>10.00</pAliqEfetUF></uf>';
        $munXml = '<mun><pIBSMun>5.00</pIBSMun>'
            . ($pRedAliqMun !== null ? "<pRedAliqMun>{$pRedAliqMun}</pRedAliqMun>" : '')
            . '<pAliqEfetMun>5.00</pAliqEfetMun></mun>';
        $fedXml = '<fed><pCBS>8.00</pCBS>'
            . ($pRedAliqCBS !== null ? "<pRedAliqCBS>{$pRedAliqCBS}</pRedAliqCBS>" : '')
            . '<pAliqEfetCBS>8.00</pAliqEfetCBS></fed>';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<CompNFSe xmlns="http://www.sped.fazenda.gov.br/nfse">'
            . '<NFSe><infNFSe Id="NFSe12345678901234567890123456789012345678901234">'
            . '<chNFSe>12345678901234567890123456789012345678901234</chNFSe>'
            . '<nNFSe>123</nNFSe><cVerif>ABC123</cVerif><serie>1</serie>'
            . '<dhEmi>2026-06-15T10:00:00-03:00</dhEmi>'
            . '<CNPJ>11444777000161</CNPJ><xNome>Prestador Ltda</xNome>'
            . '<vServ>1000.00</vServ><vISS>50.00</vISS>'
            . '<IBSCBS>'
            . '<cLocalidadeIncid>3550308</cLocalidadeIncid>'
            . '<xLocalidadeIncid>Sao Paulo</xLocalidadeIncid>'
            . '<valores><vBC>1000.00</vBC>'
            . $ufXml . $munXml . $fedXml
            . '</valores>'
            . '<totCIBS>'
            . '<vTotNF>1500.00</vTotNF>'
            . '<gIBS><vIBSTot>500.00</vIBSTot>'
            . '<gIBSUFTot><vIBSUF>300.00</vIBSUF></gIBSUFTot>'
            . '<gIBSMunTot><vIBSMun>200.00</vIBSMun></gIBSMunTot>'
            . '</gIBS>'
            . '<gCBS><vCBS>100.00</vCBS></gCBS>'
            . '</totCIBS>'
            . '</IBSCBS>'
            . '</infNFSe></NFSe></CompNFSe>';
    }

    private function buildInfNfse(string $serie, string $vServ): string
    {
        $ufXml = '<uf><pIBSUF>10.00</pIBSUF><pAliqEfetUF>10.00</pAliqEfetUF></uf>';
        $munXml = '<mun><pIBSMun>5.00</pIBSMun><pAliqEfetMun>5.00</pAliqEfetMun></mun>';
        $fedXml = '<fed><pCBS>8.00</pCBS><pAliqEfetCBS>8.00</pAliqEfetCBS></fed>';

        return '<NFSe><infNFSe Id="NFSe' . $serie . '1234567890123456789012345678901234567890123">'
            . '<chNFSe>' . $serie . '1234567890123456789012345678901234567890123</chNFSe>'
            . '<nNFSe>12' . $serie . '</nNFSe>'
            . '<cVerif>ABC' . $serie . '23</cVerif>'
            . '<serie>' . $serie . '</serie>'
            . '<dhEmi>2026-06-15T10:00:00-03:00</dhEmi>'
            . '<CNPJ>11444777000161</CNPJ><xNome>Prestador Ltda</xNome>'
            . '<vServ>' . $vServ . '</vServ><vISS>50.00</vISS>'
            . $this->buildIbscbsXml()
            . '</infNFSe></NFSe>';
    }

    private function buildIbscbsXml(
        ?string $pRedutor = null,
        ?array $gIbsCredPres = null,
        ?array $gCbsCredPres = null,
        ?string $vDifUF = null,
        ?string $vDifMun = null,
        ?string $vDifCBS = null,
        ?array $gTribCompraGov = null,
        ?string $vCalcReeRepRes = null,
    ): string {
        $gIbsCredPresXml = '';
        if ($gIbsCredPres !== null) {
            $gIbsCredPresXml = '<gIBSCredPres>'
                . "<pCredPresIBS>{$gIbsCredPres['pCredPresIBS']}</pCredPresIBS>"
                . "<vCredPresIBS>{$gIbsCredPres['vCredPresIBS']}</vCredPresIBS>"
                . '</gIBSCredPres>';
        }

        $gCbsCredPresXml = '';
        if ($gCbsCredPres !== null) {
            $gCbsCredPresXml = '<gCBSCredPres>'
                . "<pCredPresCBS>{$gCbsCredPres['pCredPresCBS']}</pCredPresCBS>"
                . "<vCredPresCBS>{$gCbsCredPres['vCredPresCBS']}</vCredPresCBS>"
                . '</gCBSCredPres>';
        }

        $gTribCompraGovXml = '';
        if ($gTribCompraGov !== null) {
            $gTribCompraGovXml = '<gTribCompraGov>'
                . "<pIBSUF>{$gTribCompraGov['pIBSUF']}</pIBSUF>"
                . (isset($gTribCompraGov['vIBSUF']) ? "<vIBSUF>{$gTribCompraGov['vIBSUF']}</vIBSUF>" : '')
                . '</gTribCompraGov>';
        }

        $reeRepResXml = '';
        if ($vCalcReeRepRes !== null) {
            $reeRepResXml = "<vCalcReeRepRes>{$vCalcReeRepRes}</vCalcReeRepRes>";
        }

        $vDifUFXml = $vDifUF !== null ? "<vDifUF>{$vDifUF}</vDifUF>" : '';
        $vDifMunXml = $vDifMun !== null ? "<vDifMun>{$vDifMun}</vDifMun>" : '';
        $vDifCBSXml = $vDifCBS !== null ? "<vDifCBS>{$vDifCBS}</vDifCBS>" : '';

        return '<IBSCBS>'
            . '<cLocalidadeIncid>3550308</cLocalidadeIncid>'
            . '<xLocalidadeIncid>Sao Paulo</xLocalidadeIncid>'
            . ($pRedutor !== null ? "<pRedutor>{$pRedutor}</pRedutor>" : '')
            . '<valores>'
            . '<vBC>1000.00</vBC>'
            . $reeRepResXml
            . '<uf><pIBSUF>10.00</pIBSUF><pAliqEfetUF>10.00</pAliqEfetUF></uf>'
            . '<mun><pIBSMun>5.00</pIBSMun><pAliqEfetMun>5.00</pAliqEfetMun></mun>'
            . '<fed><pCBS>8.00</pCBS><pAliqEfetCBS>8.00</pAliqEfetCBS></fed>'
            . '</valores>'
            . '<totCIBS>'
            . '<vTotNF>1500.00</vTotNF>'
            . '<gIBS>'
            . '<vIBSTot>500.00</vIBSTot>'
            . $gIbsCredPresXml
            . '<gIBSUFTot>'
            . $vDifUFXml
            . '<vIBSUF>300.00</vIBSUF>'
            . '</gIBSUFTot>'
            . '<gIBSMunTot>'
            . $vDifMunXml
            . '<vIBSMun>200.00</vIBSMun>'
            . '</gIBSMunTot>'
            . '</gIBS>'
            . '<gCBS>'
            . $gCbsCredPresXml
            . $vDifCBSXml
            . '<vCBS>100.00</vCBS>'
            . '</gCBS>'
            . $gTribCompraGovXml
            . '</totCIBS>'
            . '</IBSCBS>';
    }
}
