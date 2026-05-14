<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Integration;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Dps;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Endereco;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsDest;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsDiferimento;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsDocumentoReeRepRes;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsEnderecoObra;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsImovel;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsInfo;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsReeRepRes;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsTribRegular;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Obra;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Prestador;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Servico;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Tomador;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\FinalidadeNfse;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\IndicadorDestinacao;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\IndicadorFinal;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\RegimeTributario;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoAmbiente;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEmissao;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEnteGovernamental;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoOperacao;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoReembolsoRepasseRessarcimento;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cep;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\ChaveAcesso;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cnpj;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoCIB;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoClassificacaoTributaria;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoCreditoPresumido;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoIndicadorOperacao;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoMunicipio;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoSituacaoTributaria;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Money;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Builder\DpsXmlBuilder;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Validator\XsdValidator;
use PHPUnit\Framework\TestCase;

final class DpsXsdValidationTest extends TestCase
{
    private DpsXmlBuilder $builder;
    private XsdValidator $xsdValidator;

    protected function setUp(): void
    {
        $this->builder = new DpsXmlBuilder();
        $this->xsdValidator = new XsdValidator();
    }

    public function test_dps_basic_with_ibscbs_validates_against_xsd(): void
    {
        $dps = $this->createDpsWithIbscbs();
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);
        $this->xsdValidator->validate($xml, 'DPS');

        $this->expectNotToPerformAssertions();
    }

    public function test_dps_with_imovel_cib_validates_against_xsd(): void
    {
        $imovel = new IbsCbsImovel(
            inscImobFisc: '12345',
            cCIB: new CodigoCIB('12345678'),
        );
        $ibscbs = $this->createIbscbs(imovel: $imovel);
        $dps = $this->createDpsWithIbscbs(ibscbs: $ibscbs);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);
        $this->xsdValidator->validate($xml, 'DPS');

        $this->expectNotToPerformAssertions();
    }

    public function test_dps_with_imovel_endereco_validates_against_xsd(): void
    {
        $enderecoImovel = new IbsCbsEnderecoObra(
            cep: '01001001',
            xLgr: 'Rua do Imóvel',
            nro: '100',
            xCpl: 'Bloco B',
            xBairro: 'Centro',
        );
        $imovel = new IbsCbsImovel(endereco: $enderecoImovel);
        $ibscbs = $this->createIbscbs(imovel: $imovel);
        $dps = $this->createDpsWithIbscbs(ibscbs: $ibscbs);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);
        $this->xsdValidator->validate($xml, 'DPS');

        $this->expectNotToPerformAssertions();
    }

    public function test_dps_with_obra_validates_against_xsd(): void
    {
        $obra = new Obra(
            cObra: 'CNO123456789',
            inscImobFisc: '99999',
        );
        $endereco = $this->createEndereco();
        $servico = new Servico(
            discriminacao: 'Serviço de obra',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(5000.00),
            valorDeducoes: new Money(0),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            codigoNbs: '123456789',
            obra: $obra,
        );
        $dps = $this->createDps(
            servico: $servico,
            ibscbs: $this->createIbscbs(),
        );
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);
        $this->xsdValidator->validate($xml, 'DPS');

        $this->expectNotToPerformAssertions();
    }

    public function test_dps_with_obra_endereco_validates_against_xsd(): void
    {
        $endObra = new IbsCbsEnderecoObra(
            cep: '01001001',
            xLgr: 'Rua da Obra',
            nro: '500',
            xCpl: 'Galpão 2',
            xBairro: 'Industrial',
        );
        $obra = new Obra(endereco: $endObra);
        $servico = new Servico(
            discriminacao: 'Serviço de obra',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(5000.00),
            valorDeducoes: new Money(0),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            codigoNbs: '123456789',
            obra: $obra,
        );
        $dps = $this->createDps(
            servico: $servico,
            ibscbs: $this->createIbscbs(),
        );
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);
        $this->xsdValidator->validate($xml, 'DPS');

        $this->expectNotToPerformAssertions();
    }

    public function test_dps_with_g_ree_rep_res_validates_against_xsd(): void
    {
        $doc = new IbsCbsDocumentoReeRepRes(
            tipo: 'dFeNacional',
            dtEmiDoc: new \DateTimeImmutable('2026-01-15'),
            dtCompDoc: new \DateTimeImmutable('2026-01-15'),
            tpReeRepRes: TipoReembolsoRepasseRessarcimento::REPASSE_IMOVEIS_CORRETORES,
            vlrReeRepRes: '1500.00',
            tipoChaveDFe: '1',
            chaveDFe: '12345678901234567890123456789012345678901234567890',
        );
        $reeRepRes = new IbsCbsReeRepRes([$doc]);
        $ibscbs = $this->createIbscbs(reeRepRes: $reeRepRes);
        $dps = $this->createDpsWithIbscbs(ibscbs: $ibscbs);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);
        $this->xsdValidator->validate($xml, 'DPS');

        $this->expectNotToPerformAssertions();
    }

    public function test_dps_with_destinatario_terceiro_validates_against_xsd(): void
    {
        $enderecoDest = $this->createEndereco();
        $dest = new IbsCbsDest(
            cnpj: new Cnpj('11444777000161'),
            xNome: 'Destinatário Terceiro Ltda',
            endereco: $enderecoDest,
            fone: '11999999999',
            email: 'dest@teste.com',
        );
        $ibscbs = $this->createIbscbs(
            indDest: IndicadorDestinacao::TERCEIRO,
            dest: $dest,
        );
        $dps = $this->createDpsWithIbscbs(ibscbs: $ibscbs);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);
        $this->xsdValidator->validate($xml, 'DPS');

        $this->expectNotToPerformAssertions();
    }

    public function test_dps_with_trib_regular_and_diferimento_validates_against_xsd(): void
    {
        $tribRegular = new IbsCbsTribRegular(
            cstReg: new CodigoSituacaoTributaria('200'),
            cClassTribReg: new CodigoClassificacaoTributaria('200456'),
        );
        $diferimento = new IbsCbsDiferimento(
            pDifUF: 10.5,
            pDifMun: 5.2,
            pDifCBS: 8.7,
        );
        $ibscbs = $this->createIbscbs(
            tribRegular: $tribRegular,
            diferimento: $diferimento,
        );
        $dps = $this->createDpsWithIbscbs(ibscbs: $ibscbs);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);
        $this->xsdValidator->validate($xml, 'DPS');

        $this->expectNotToPerformAssertions();
    }

    public function test_dps_with_g_ref_nfse_validates_against_xsd(): void
    {
        $refs = [
            new ChaveAcesso('12345678901234567890123456789012345678901234567890'),
            new ChaveAcesso('22345678901234567890123456789012345678901234567890'),
        ];
        $ibscbs = $this->createIbscbs(
            tpOper: TipoOperacao::RECEBIMENTO_FORNECIMENTO_REALIZADO,
            refNFSeList: $refs,
        );
        $dps = $this->createDpsWithIbscbs(ibscbs: $ibscbs);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);
        $this->xsdValidator->validate($xml, 'DPS');

        $this->expectNotToPerformAssertions();
    }

    public function test_dps_with_all_optional_ibscbs_fields_validates_against_xsd(): void
    {
        $dest = new IbsCbsDest(
            cnpj: new Cnpj('11444777000161'),
            xNome: 'Destinatário Completo',
            endereco: $this->createEndereco(),
            fone: '11988888888',
            email: 'completo@teste.com',
        );
        $tribRegular = new IbsCbsTribRegular(
            cstReg: new CodigoSituacaoTributaria('300'),
            cClassTribReg: new CodigoClassificacaoTributaria('300789'),
        );
        $diferimento = new IbsCbsDiferimento(
            pDifUF: 10.0,
            pDifMun: 5.0,
            pDifCBS: 8.0,
        );
        $imovel = new IbsCbsImovel(
            inscImobFisc: '12345',
            cCIB: new CodigoCIB('87654321'),
        );
        $doc = new IbsCbsDocumentoReeRepRes(
            tipo: 'dFeNacional',
            dtEmiDoc: new \DateTimeImmutable('2026-01-15'),
            dtCompDoc: new \DateTimeImmutable('2026-01-15'),
            tpReeRepRes: TipoReembolsoRepasseRessarcimento::REPASSE_IMOVEIS_CORRETORES,
            vlrReeRepRes: '1500.00',
            tipoChaveDFe: '1',
            chaveDFe: '12345678901234567890123456789012345678901234567890',
        );
        $reeRepRes = new IbsCbsReeRepRes([$doc]);
        $refs = [
            new ChaveAcesso('12345678901234567890123456789012345678901234567890'),
        ];
        $ibscbs = new IbsCbsInfo(
            finNFSe: FinalidadeNfse::REGULAR,
            cIndOp: new CodigoIndicadorOperacao('100001'),
            indDest: IndicadorDestinacao::TERCEIRO,
            cst: new CodigoSituacaoTributaria('100'),
            cClassTrib: new CodigoClassificacaoTributaria('100123'),
            indFinal: IndicadorFinal::SIM,
            tpOper: TipoOperacao::FORNECIMENTO_POSTERIOR,
            tpEnteGov: TipoEnteGovernamental::MUNICIPIO,
            cCredPres: new CodigoCreditoPresumido('01'),
            dest: $dest,
            tribRegular: $tribRegular,
            diferimento: $diferimento,
            refNFSeList: $refs,
            imovel: $imovel,
            reeRepRes: $reeRepRes,
        );
        $dps = $this->createDpsWithIbscbs(ibscbs: $ibscbs);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);
        $this->xsdValidator->validate($xml, 'DPS');

        $this->expectNotToPerformAssertions();
    }

    public function test_dps_without_ibscbs_validates_against_xsd(): void
    {
        $dps = $this->createDps();
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);
        $this->xsdValidator->validate($xml, 'DPS');

        $this->expectNotToPerformAssertions();
    }

    public function test_dps_xml_element_order_matches_xsd(): void
    {
        $dps = $this->createDpsWithIbscbs();
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        $infDps = $dom->getElementsByTagName('infDPS')->item(0);

        $children = [];
        foreach ($infDps->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $children[] = $child->localName;
            }
        }

        $expectedOrder = ['tpAmb', 'dhEmi', 'verAplic', 'serie', 'nDPS', 'dCompet', 'tpEmit', 'cLocEmi'];
        foreach ($expectedOrder as $i => $tag) {
            $this->assertSame($tag, $children[$i] ?? null, "Position $i should be $tag");
        }

        $this->assertContains('prest', $children, 'prest must be present');
        $this->assertContains('toma', $children, 'toma must be present');
        $this->assertContains('serv', $children, 'serv must be present');
        $this->assertContains('valores', $children, 'valores must be present');
        $this->assertContains('IBSCBS', $children, 'IBSCBS must be present');

        $servIdx = array_search('serv', $children, true);
        $valIdx = array_search('valores', $children, true);
        $ibscbsIdx = array_search('IBSCBS', $children, true);

        $this->assertLessThan($valIdx, $servIdx, 'serv must come before valores');
        $this->assertLessThan($ibscbsIdx, $valIdx, 'valores must come before IBSCBS');
    }

    public function test_dps_xml_element_order_without_ibscbs_matches_xsd(): void
    {
        $dps = $this->createDps();
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        $infDps = $dom->getElementsByTagName('infDPS')->item(0);

        $children = [];
        foreach ($infDps->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $children[] = $child->localName;
            }
        }

        $this->assertContains('serv', $children);
        $this->assertContains('valores', $children);
        $this->assertNotContains('IBSCBS', $children);

        $servIdx = array_search('serv', $children, true);
        $valIdx = array_search('valores', $children, true);
        $this->assertLessThan($valIdx, $servIdx, 'serv must come before valores');
    }

    public function test_dps_xml_has_correct_namespace(): void
    {
        $dps = $this->createDpsWithIbscbs();
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        $dpsEl = $dom->documentElement;

        $this->assertSame('DPS', $dpsEl->localName);
        $this->assertSame('http://www.sped.fazenda.gov.br/nfse', $dpsEl->namespaceURI);
    }

    private function createIbscbs(
        ?IbsCbsImovel $imovel = null,
        ?IbsCbsReeRepRes $reeRepRes = null,
        ?IbsCbsTribRegular $tribRegular = null,
        ?IbsCbsDiferimento $diferimento = null,
        ?IndicadorFinal $indFinal = null,
        ?TipoOperacao $tpOper = null,
        ?TipoEnteGovernamental $tpEnteGov = null,
        ?CodigoCreditoPresumido $cCredPres = null,
        IndicadorDestinacao $indDest = IndicadorDestinacao::TOMADOR,
        ?IbsCbsDest $dest = null,
        ?array $refNFSeList = null,
    ): IbsCbsInfo {
        return new IbsCbsInfo(
            finNFSe: FinalidadeNfse::REGULAR,
            cIndOp: new CodigoIndicadorOperacao('100001'),
            indDest: $indDest,
            cst: new CodigoSituacaoTributaria('100'),
            cClassTrib: new CodigoClassificacaoTributaria('100123'),
            indFinal: $indFinal,
            tpOper: $tpOper,
            tpEnteGov: $tpEnteGov,
            cCredPres: $cCredPres,
            dest: $dest,
            tribRegular: $tribRegular,
            diferimento: $diferimento,
            refNFSeList: $refNFSeList,
            imovel: $imovel,
            reeRepRes: $reeRepRes,
        );
    }

    private function createEndereco(): Endereco
    {
        return new Endereco(
            logradouro: 'Rua Teste',
            numero: '123',
            complemento: null,
            bairro: 'Centro',
            codigoMunicipio: new CodigoMunicipio('3550308'),
            uf: 'SP',
            cep: new Cep('01001001'),
        );
    }

    private function createDps(
        ?Servico $servico = null,
        ?IbsCbsInfo $ibscbs = null,
    ): Dps {
        $endereco = $this->createEndereco();
        $cnpj = new Cnpj('11444777000161');

        return new Dps(
            tipoAmbiente: TipoAmbiente::HOMOLOGACAO,
            dataEmissao: new \DateTimeImmutable('2026-06-15T10:00:00'),
            versaoAplicacao: '1.0.0',
            serie: 1,
            numero: 123,
            dataCompetencia: new \DateTimeImmutable('2026-06-01'),
            tipoEmissao: TipoEmissao::PRESTADOR,
            codigoMunicipioEmissor: new CodigoMunicipio('3550308'),
            prestador: new Prestador(
                documento: $cnpj,
                inscricaoMunicipal: '123456',
                razaoSocial: 'Prestador Ltda',
                telefone: null,
                email: null,
                endereco: $endereco,
                regimeTributario: RegimeTributario::SIMPLES_NACIONAL,
            ),
            tomador: new Tomador(
                documento: new Cnpj('33444555000181'),
                razaoSocial: 'Tomador Ltda',
                nomeFantasia: null,
                telefone: null,
                email: null,
                endereco: $endereco,
            ),
            servico: $servico ?? new Servico(
                discriminacao: 'Serviço de teste',
                codigoTributacao: '010101',
                localPrestacao: new CodigoMunicipio('3550308'),
                valorServicos: new Money(1000.00),
                valorDeducoes: new Money(0),
                descontoIncondicionado: new Money(0),
                descontoCondicionado: new Money(0),
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
            ),
            ibscbs: $ibscbs,
        );
    }

    private function createDpsWithIbscbs(?IbsCbsInfo $ibscbs = null): Dps
    {
        return $this->createDps(ibscbs: $ibscbs ?? $this->createIbscbs());
    }
}
