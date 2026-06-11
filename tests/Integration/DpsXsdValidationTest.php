<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Integration;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\AtvEvento;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\BeneficioMunicipal;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\ComExterior;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\DocDedRed;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Dps;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Endereco;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\ExigSusp;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsDest;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsDiferimento;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsDocumentoReeRepRes;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsEnderecoExterior;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsEnderecoObra;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsFornecedor;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsImovel;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsInfo;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsReeRepRes;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsTribRegular;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\InfoCompl;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Intermediario;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Obra;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Prestador;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Servico;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Substituicao;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Tomador;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\TribFederal;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\FinalidadeNfse;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\IndicadorDestinacao;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\IndicadorFinal;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\MotivoEmissaoTI;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\RegimeTributario;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoAmbiente;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEmitente;
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
            endExt: null,
            xLgr: 'Rua do Imóvel',
            nro: '100',
            xBairro: 'Centro',
            xCpl: 'Bloco B',
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
            endExt: null,
            xLgr: 'Rua da Obra',
            nro: '500',
            xBairro: 'Industrial',
            xCpl: 'Galpão 2',
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

    public function test_dps_g_ree_rep_res_com_fornec_valida_no_xsd(): void
    {
        // Regressão bugs 1-3 (auditoria NT 004): o grupo fornec (com cNaoNIF)
        // deve gerar XML válido contra o XSD — prova no nível do schema, não só do validador.
        $doc = new IbsCbsDocumentoReeRepRes(
            tipo: 'docOutro',
            dtEmiDoc: new \DateTimeImmutable('2026-01-15'),
            dtCompDoc: new \DateTimeImmutable('2026-01-15'),
            tpReeRepRes: TipoReembolsoRepasseRessarcimento::REPASSE_IMOVEIS_CORRETORES,
            vlrReeRepRes: '1500.00',
            nDoc: 'DOC-1',
            xDoc: 'Documento não fiscal',
            fornec: new IbsCbsFornecedor(
                codigoNaoNif: '1',
                xNome: 'Fornecedor Estrangeiro Lda',
            ),
        );
        $reeRepRes = new IbsCbsReeRepRes([$doc]);
        $ibscbs = $this->createIbscbs(reeRepRes: $reeRepRes);
        $dps = $this->createDpsWithIbscbs(ibscbs: $ibscbs);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);
        self::assertStringContainsString('<fornec>', $xml);
        self::assertStringContainsString('<cNaoNIF>1</cNaoNIF>', $xml);
        $this->xsdValidator->validate($xml, 'DPS');
    }

    public function test_dps_imovel_endext_valida_no_xsd(): void
    {
        // Regressão bug 10 (auditoria NT 004): imóvel com endExt gera XML válido no XSD.
        $endExt = new IbsCbsEnderecoExterior(
            cEndPost: '1100-001',
            xCidade: 'Lisboa',
            xEstProvReg: 'Lisboa',
        );
        $imovel = new IbsCbsImovel(
            endereco: new IbsCbsEnderecoObra(
                cep: null,
                endExt: $endExt,
                xLgr: 'Rua Augusta',
                nro: '50',
                xBairro: 'Baixa',
            ),
        );
        $ibscbs = $this->createIbscbs(imovel: $imovel);
        $dps = $this->createDpsWithIbscbs(ibscbs: $ibscbs);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);
        self::assertStringContainsString('<endExt>', $xml);
        $this->xsdValidator->validate($xml, 'DPS');
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

    public function test_dps_with_destinatario_exterior_endext_validates_against_xsd(): void
    {
        // Regressão bug 9 (auditoria NT 004): o ramo endExt do dest era inalcançável.
        // Agora um destinatário no exterior gera <endExt> e valida no XSD.
        $enderecoExterior = new Endereco(
            logradouro: 'Rua Augusta',
            numero: '100',
            complemento: null,
            bairro: 'Baixa',
            codigoMunicipio: new CodigoMunicipio('0000000'),
            uf: '',
            cep: new Cep('00000000'),
            codigoPais: 'PT',
            nomeCidadeExterior: 'Lisboa',
            estadoProvinciaExterior: 'Lisboa',
            codigoPostalExterior: '1100-001',
        );
        $dest = new IbsCbsDest(
            cnpj: new Cnpj('11444777000161'),
            xNome: 'Destinatário Exterior Lda',
            endereco: $enderecoExterior,
        );
        $ibscbs = $this->createIbscbs(
            indDest: IndicadorDestinacao::TERCEIRO,
            dest: $dest,
        );
        $dps = $this->createDpsWithIbscbs(ibscbs: $ibscbs);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);
        self::assertStringContainsString('<endExt>', $xml);
        self::assertStringContainsString('<cPais>PT</cPais>', $xml);
        $this->xsdValidator->validate($xml, 'DPS');
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

    // --- XSD validation tests for new Servico fields ---

    public function test_dps_with_com_exterior_validates_against_xsd(): void
    {
        $comExterior = new ComExterior(
            modoPrestacao: 1,
            vinculoPrestador: 2,
            codigoMoeda: '840',
            valorServicoMoeda: 5000.00,
            mecanismoApoioPrestador: '01',
            mecanismoApoioTomador: '01',
            movimentacaoTemporaria: '0',
            enviarMDIC: '0',
        );
        $servico = new Servico(
            discriminacao: 'Comércio exterior',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(25000.00),
            valorDeducoes: new Money(0),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            codigoNbs: '123456789',
            comExterior: $comExterior,
        );
        $dps = $this->createDps(servico: $servico, ibscbs: $this->createIbscbs());
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);
        $this->xsdValidator->validate($xml, 'DPS');

        $this->expectNotToPerformAssertions();
    }

    public function test_dps_with_atv_evento_validates_against_xsd(): void
    {
        $atvEvento = new AtvEvento(
            descricao: 'Feira Tecnológica',
            dataInicio: new \DateTimeImmutable('2026-06-01'),
            dataFim: new \DateTimeImmutable('2026-06-10'),
            identificacaoEvento: 'EVT-001',
        );
        $servico = new Servico(
            discriminacao: 'Evento',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(5000.00),
            valorDeducoes: new Money(0),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            codigoNbs: '123456789',
            atvEvento: $atvEvento,
        );
        $dps = $this->createDps(servico: $servico, ibscbs: $this->createIbscbs());
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);
        $this->xsdValidator->validate($xml, 'DPS');

        $this->expectNotToPerformAssertions();
    }

    public function test_dps_with_info_compl_validates_against_xsd(): void
    {
        $infoCompl = new InfoCompl(
            idDocTecnico: 'CONTR-001',
            numeroPedido: 'PED-001',
            itensPedido: ['Item A', 'Item B'],
            infoComplementar: 'Obs complementares',
        );
        $servico = new Servico(
            discriminacao: 'test',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(1000.00),
            valorDeducoes: new Money(0),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            codigoNbs: '123456789',
            infoCompl: $infoCompl,
        );
        $dps = $this->createDps(servico: $servico, ibscbs: $this->createIbscbs());
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);
        $this->xsdValidator->validate($xml, 'DPS');

        $this->expectNotToPerformAssertions();
    }

    public function test_dps_with_documentos_deducao_validates_against_xsd(): void
    {
        $doc = new DocDedRed(
            tipoDocumento: 'chNFe',
            dataEmissaoDoc: new \DateTimeImmutable('2026-05-15'),
            chaveNFe: '12345678901234567890123456789012345678901234',
            tipoDeducaoReducao: '1',
            valorDedutivel: '3000.00',
            valorDeducao: '3000.00',
        );
        $servico = new Servico(
            discriminacao: 'test',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(5000.00),
            valorDeducoes: new Money(0),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            codigoNbs: '123456789',
            documentosDeducao: [$doc],
        );
        $dps = $this->createDps(servico: $servico, ibscbs: $this->createIbscbs());
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);
        $this->xsdValidator->validate($xml, 'DPS');

        $this->expectNotToPerformAssertions();
    }

    public function test_dps_with_exig_susp_e_beneficio_municipal_validates_against_xsd(): void
    {
        $exigSusp = new ExigSusp(tipoSuspensao: 1, numeroProcesso: '000000000000000000000000012345');
        $bm = new BeneficioMunicipal(numeroBeneficio: '00000000000001');
        $servico = new Servico(
            discriminacao: 'test',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(1000.00),
            valorDeducoes: new Money(0),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            codigoNbs: '123456789',
            exigSusp: $exigSusp,
            beneficioMunicipal: $bm,
        );
        $dps = $this->createDps(servico: $servico, ibscbs: $this->createIbscbs());
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);
        $this->xsdValidator->validate($xml, 'DPS');

        $this->expectNotToPerformAssertions();
    }

    public function test_pred_bcbm_percentual_usa_duas_casas_e_valida_xsd(): void
    {
        $bm = new BeneficioMunicipal(
            numeroBeneficio: '00000000000001',
            percentualReducaoBC: 12.345,
        );
        $servico = new Servico(
            discriminacao: 'test',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(1000.00),
            valorDeducoes: new Money(0),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            codigoNbs: '123456789',
            beneficioMunicipal: $bm,
        );
        $dps = $this->createDps(servico: $servico, ibscbs: $this->createIbscbs());
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        // Deve sair com 2 casas (arredondado), não com 3.
        $this->assertStringContainsString('<pRedBCBM>12.35</pRedBCBM>', $xml);
        $this->assertStringNotContainsString('<pRedBCBM>12.345</pRedBCBM>', $xml);

        // E deve validar contra o XSD (com 3 casas, falharia aqui).
        $this->xsdValidator->validate($xml, 'DPS');
    }

    public function test_dps_with_trib_federal_validates_against_xsd(): void
    {
        $tribFed = new TribFederal(
            pisCofinsCst: '01',
            pisCofinsTipo: '1',
            pisCofinsAliquotaPis: 1.65,
            pisCofinsAliquotaCofins: 7.60,
            valorRetidoCP: '250.00',
            valorRetidoIRRF: '150.00',
            valorRetidoCSLL: '100.00',
        );
        $servico = new Servico(
            discriminacao: 'test',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(1000.00),
            valorDeducoes: new Money(0),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            codigoNbs: '123456789',
            tribFederal: $tribFed,
        );
        $dps = $this->createDps(servico: $servico, ibscbs: $this->createIbscbs());
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);
        $this->xsdValidator->validate($xml, 'DPS');

        $this->expectNotToPerformAssertions();
    }

    public function test_dps_with_codigo_pais_prestacao_validates_against_xsd(): void
    {
        $servico = new Servico(
            discriminacao: 'Serviço no exterior',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(1000.00),
            valorDeducoes: new Money(0),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            codigoNbs: '123456789',
            codigoPaisPrestacao: 'US',
        );
        $dps = $this->createDps(servico: $servico, ibscbs: $this->createIbscbs());
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);
        $this->xsdValidator->validate($xml, 'DPS');

        $this->expectNotToPerformAssertions();
    }

    public function test_dps_with_all_new_fields_validates_against_xsd(): void
    {
        $comExterior = new ComExterior(
            modoPrestacao: 1,
            vinculoPrestador: 2,
            codigoMoeda: '840',
            valorServicoMoeda: 5000.00,
            mecanismoApoioPrestador: '01',
            mecanismoApoioTomador: '01',
            movimentacaoTemporaria: '0',
            enviarMDIC: '0',
        );
        $atvEvento = new AtvEvento(
            descricao: 'Feira Internacional',
            dataInicio: new \DateTimeImmutable('2026-06-01'),
            dataFim: new \DateTimeImmutable('2026-06-10'),
            identificacaoEvento: 'EVT-001',
        );
        $infoCompl = new InfoCompl(
            idDocTecnico: 'CONTR-001',
            numeroPedido: 'PED-001',
            infoComplementar: 'Observações',
        );
        $doc = new DocDedRed(
            tipoDocumento: 'chNFe',
            dataEmissaoDoc: new \DateTimeImmutable('2026-05-15'),
            chaveNFe: '12345678901234567890123456789012345678901234',
            tipoDeducaoReducao: '1',
            valorDedutivel: '3000.00',
            valorDeducao: '3000.00',
        );
        $exigSusp = new ExigSusp(tipoSuspensao: 1, numeroProcesso: '000000000000000000000000012345');
        $bm = new BeneficioMunicipal(numeroBeneficio: '00000000000001');
        $tribFed = new TribFederal(
            pisCofinsCst: '01',
            pisCofinsTipo: '1',
            pisCofinsAliquotaPis: 1.65,
            pisCofinsAliquotaCofins: 7.60,
        );
        $servico = new Servico(
            discriminacao: 'Todos os campos novos',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(25000.00),
            valorDeducoes: new Money(3000.00),
            descontoIncondicionado: new Money(500.00),
            descontoCondicionado: new Money(200.00),
            aliquotaIss: 5.0,
            codigoNbs: '123456789',
            codigoTributacaoMunicipal: '123',
            codigoInternoContribuinte: 'INT001',
            valorRecebido: 24300.00,
            comExterior: $comExterior,
            atvEvento: $atvEvento,
            infoCompl: $infoCompl,
            documentosDeducao: [$doc],
            exigSusp: $exigSusp,
            beneficioMunicipal: $bm,
            tribFederal: $tribFed,
            totTribTipo: 'pTotTrib',
            pTotTribFed: 10.0,
            pTotTribEst: 5.0,
            pTotTribMun: 3.0,
        );
        $dps = $this->createDps(servico: $servico, ibscbs: $this->createIbscbs());
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);
        $this->xsdValidator->validate($xml, 'DPS');

        $this->expectNotToPerformAssertions();
    }

    public function test_dps_with_intermediario_validates_against_xsd(): void
    {
        $endereco = new Endereco(
            logradouro: 'Rua Intermediario',
            numero: '456',
            complemento: null,
            bairro: 'Centro',
            codigoMunicipio: new CodigoMunicipio('3550308'),
            uf: 'SP',
            cep: new Cep('01001001'),
        );
        $intermediario = new Intermediario(
            documento: new Cnpj('11444777000161'),
            razaoSocial: 'Intermediario Ltda',
            inscricaoMunicipal: '78901',
            telefone: null,
            email: null,
            endereco: $endereco,
        );
        $dps = $this->createDps(ibscbs: $this->createIbscbs(), intermediario: $intermediario);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);
        $this->xsdValidator->validate($xml, 'DPS');

        $this->expectNotToPerformAssertions();
    }

    public function test_dps_with_substituicao_validates_against_xsd(): void
    {
        $chave = new ChaveAcesso('12345678901234567890123456789012345678901234567890');
        $substituicao = new Substituicao(
            chaveSubstituida: $chave,
            codigoMotivo: '01',
            descricaoMotivo: 'Cancelamento da NFSe anterior',
        );
        $dps = $this->createDps(ibscbs: $this->createIbscbs(), substituicao: $substituicao);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);
        $this->xsdValidator->validate($xml, 'DPS');

        $this->expectNotToPerformAssertions();
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
        ?Intermediario $intermediario = null,
        ?Substituicao $substituicao = null,
        ?MotivoEmissaoTI $cMotivoEmisTI = null,
        ?ChaveAcesso $chNFSeRej = null,
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
            tipoEmissao: TipoEmitente::PRESTADOR,
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
            intermediario: $intermediario,
            substituicao: $substituicao,
            cMotivoEmisTI: $cMotivoEmisTI,
            chNFSeRej: $chNFSeRej,
        );
    }

    private function createDpsWithIbscbs(?IbsCbsInfo $ibscbs = null): Dps
    {
        return $this->createDps(ibscbs: $ibscbs ?? $this->createIbscbs());
    }
}
