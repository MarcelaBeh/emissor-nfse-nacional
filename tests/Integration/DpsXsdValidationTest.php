<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Integration;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\AtvEvento;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\BeneficioMunicipal;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\ComExterior;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\DocAjusteBC;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Dps;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Endereco;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\ExigSusp;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsAjusteBCLocImoveis;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsBemMovel;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsDest;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsDiferimento;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsEnderecoImovel;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsEnderecoObra;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsFornecedor;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsImovel;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsInfo;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsLocacaoImovel;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsTribRegular;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsUnidadeImobiliaria;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\InfoCompl;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Intermediario;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Obra;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Prestador;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Servico;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Substituicao;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Tomador;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\TribFederal;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\IndicadorDestinacao;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\IndicadorFinal;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\MotivoEmissaoTI;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\RegimeTributario;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoAjusteBC;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoAjusteBCLocImoveis;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoAmbiente;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEmitente;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEnteGovernamental;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoOperacao;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cep;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\ChaveAcesso;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cnpj;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoCIB;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoClassificacaoTributaria;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoCreditoPresumido;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoIndicadorOperacao;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoMunicipio;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoSituacaoTributaria;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cpf;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Money;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Builder\DpsXmlBuilder;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Validator\XsdValidator;
use PHPUnit\Framework\Attributes\DataProvider;
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
        $dps->gerarIdDps();

        $xml = $this->builder->build($dps);
        $this->xsdValidator->validate($xml, 'DPS');

        $this->expectNotToPerformAssertions();
    }

    public function test_dps_with_imovel_cib_validates_against_xsd(): void
    {
        $imovel = new IbsCbsImovel(
            cMun: new CodigoMunicipio('3550308'),
            unidades: [new IbsCbsUnidadeImobiliaria(inscImobFisc: '12345', cCIB: new CodigoCIB('12345678'))],
        );
        $ibscbs = $this->createIbscbs(imovel: $imovel);
        $dps = $this->createDpsWithIbscbs(ibscbs: $ibscbs);
        $dps->gerarIdDps();

        $xml = $this->builder->build($dps);
        $this->xsdValidator->validate($xml, 'DPS');

        $this->expectNotToPerformAssertions();
    }

    public function test_dps_with_imovel_endereco_validates_against_xsd(): void
    {
        $enderecoImovel = new IbsCbsEnderecoImovel(
            cep: '01001001',
            xLgr: 'Rua do Imóvel',
            nro: '100',
            xCpl: 'Bloco B',
            xBairro: 'Centro',
        );
        $imovel = new IbsCbsImovel(
            cMun: new CodigoMunicipio('3550308'),
            unidades: [new IbsCbsUnidadeImobiliaria(endereco: $enderecoImovel)],
        );
        $ibscbs = $this->createIbscbs(imovel: $imovel);
        $dps = $this->createDpsWithIbscbs(ibscbs: $ibscbs);
        $dps->gerarIdDps();

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
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            totTribTipo: 'indTotTrib',
            indTotTrib: '0',
            codigoNbs: '123456789',
            obra: $obra,
        );
        $dps = $this->createDps(
            servico: $servico,
            ibscbs: $this->createIbscbs(),
        );
        $dps->gerarIdDps();

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
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            totTribTipo: 'indTotTrib',
            indTotTrib: '0',
            codigoNbs: '123456789',
            obra: $obra,
        );
        $dps = $this->createDps(
            servico: $servico,
            ibscbs: $this->createIbscbs(),
        );
        $dps->gerarIdDps();

        $xml = $this->builder->build($dps);
        $this->xsdValidator->validate($xml, 'DPS');

        $this->expectNotToPerformAssertions();
    }

    public function test_dps_with_documento_de_ajuste_dfe_nacional_validates_against_xsd(): void
    {
        // tpAjusteBC 101 (reembolso/repasse que repercute no IBS/CBS) com a chave de uma NFS-e.
        $doc = new DocAjusteBC(
            tpAjusteBC: TipoAjusteBC::REPASSE_INTERMEDIACAO_IMOVEIS,
            vTotDoc: '1500.00',
            vAjusteAplic: '1200.00',
            tipoDocumento: DocAjusteBC::DOC_DFE_NACIONAL,
            dtEmiDoc: new \DateTimeImmutable('2026-01-15'),
            dtCompDoc: new \DateTimeImmutable('2026-01-10'),
            tipoChaveDFe: '1',
            chaveDFe: '35503082211444777000161123456789012345678901234567',
        );

        $this->assertDpsValida($this->createDps(servico: $this->servicoComAjuste(docs: [$doc]), ibscbs: $this->createIbscbs()));
    }

    public function test_dps_with_documento_de_ajuste_com_fornec_cnaonif_validates_against_xsd(): void
    {
        // Regressão (auditoria NT 004): fornec com cNaoNIF gera XML válido, agora dentro de docAjusteBC.
        $doc = new DocAjusteBC(
            tpAjusteBC: TipoAjusteBC::REPASSE_INTERMEDIACAO_IMOVEIS,
            vTotDoc: '1500.00',
            vAjusteAplic: '1500.00',
            tipoDocumento: DocAjusteBC::DOC_OUTRO,
            fornec: new IbsCbsFornecedor(codigoNaoNif: '1', xNome: 'Fornecedor Estrangeiro Lda'),
            nDoc: 'DOC-1',
            xDoc: 'Documento não fiscal',
        );
        $dps = $this->createDps(servico: $this->servicoComAjuste(docs: [$doc]), ibscbs: $this->createIbscbs());
        $dps->gerarIdDps();

        $xml = $this->builder->build($dps);
        self::assertStringContainsString('<fornec>', $xml);
        self::assertStringContainsString('<cNaoNIF>1</cNaoNIF>', $xml);
        $this->xsdValidator->validate($xml, 'DPS');
    }

    public function test_dps_with_documento_doc_fiscal_outro_validates_against_xsd(): void
    {
        $doc = new DocAjusteBC(
            tpAjusteBC: TipoAjusteBC::OUTROS_REEMBOLSOS,
            vTotDoc: '500.00',
            vAjusteAplic: '500.00',
            tipoDocumento: DocAjusteBC::DOC_FISCAL_OUTRO,
            xTpAjusteBC: 'Reembolso de despesas diversas',
            cMunDocFiscal: '3550308',
            nDocFiscal: '123',
            xDocFiscal: 'NFS municipal',
        );

        $this->assertDpsValida($this->createDps(servico: $this->servicoComAjuste(docs: [$doc]), ibscbs: $this->createIbscbs()));
    }

    public function test_dps_with_ajuste_percentual_e_documento_validates_against_xsd(): void
    {
        $doc = new DocAjusteBC(
            tpAjusteBC: TipoAjusteBC::GLOSA_SERVICOS_SAUDE,
            vTotDoc: '800.00',
            vAjusteAplic: '800.00',
            tipoDocumento: DocAjusteBC::DOC_DFE_NACIONAL,
            tipoChaveDFe: '9',
            xTipoChaveDFe: 'Documento eletrônico de glosa',
            chaveDFe: 'GLOSA-2026-0001',
        );

        $this->assertDpsValida($this->createDps(servico: $this->servicoComAjuste(docs: [$doc], pct: 10.0), ibscbs: $this->createIbscbs()));
    }

    public function test_dps_with_ajuste_por_valor_padrao_validates_against_xsd(): void
    {
        $this->assertDpsValida($this->createDps(servico: $this->servicoComAjuste(valor: 250.0), ibscbs: $this->createIbscbs()));
    }

    public function test_dps_locacao_com_ajustes_validates_against_xsd(): void
    {
        $imovel = new IbsCbsImovel(
            cMun: new CodigoMunicipio('3550308'),
            locacao: new IbsCbsLocacaoImovel(
                pCopropriedade: '50.00',
                vTotOper: '2000.00',
                vDescIncondTot: '100.00',
                vDescCondTot: '20.00',
                dVencOrig: new \DateTimeImmutable('2026-12-10'),
            ),
            unidades: [
                new IbsCbsUnidadeImobiliaria(
                    inscImobFisc: '12345',
                    cCIB: new CodigoCIB('12345678'),
                    ajustes: [
                        new IbsCbsAjusteBCLocImoveis(TipoAjusteBCLocImoveis::CONDOMINIO_INCLUSO, '300.00'),
                        new IbsCbsAjusteBCLocImoveis(TipoAjusteBCLocImoveis::OUTRAS_PARCELAS, '50.00', 'Taxa de limpeza'),
                    ],
                ),
                new IbsCbsUnidadeImobiliaria(
                    endereco: new IbsCbsEnderecoImovel(cep: '01001001', xLgr: 'Rua B', nro: '20'),
                ),
            ],
        );

        $this->assertDpsValida($this->createDpsWithIbscbs(ibscbs: $this->createIbscbs(imovel: $imovel)));
    }

    public function test_dps_bens_moveis_validates_against_xsd(): void
    {
        $bens = [
            new IbsCbsBemMovel('84713012', 'Notebook', '2'),
            new IbsCbsBemMovel('87032100', 'Veículo', '1'),
        ];

        $this->assertDpsValida($this->createDpsWithIbscbs(ibscbs: $this->createIbscbs(bensMoveis: $bens)));
    }

    #[DataProvider('cstSemTributacao')]
    public function test_dps_cst_sem_grupo_de_tributacao_validates_against_xsd(string $cst): void
    {
        // CST 400/410/820: sem cIndOp e sem gIBSCBS (NT 009, item 2.16).
        $ibscbs = new IbsCbsInfo(
            indDest: IndicadorDestinacao::TOMADOR,
            cst: new CodigoSituacaoTributaria($cst),
            cClassTrib: new CodigoClassificacaoTributaria($cst . '001'),
        );

        $this->assertDpsValida($this->createDpsWithIbscbs(ibscbs: $ibscbs));
    }

    /** @return array<string, array{string}> */
    public static function cstSemTributacao(): array
    {
        return ['isencao' => ['400'], 'imunidade' => ['410'], 'documento especifico' => ['820']];
    }

    public function test_dps_gibscbs_vazio_validates_against_xsd(): void
    {
        // ind_gIBSCBS = 1 sem detalhes: o grupo <gIBSCBS/> vazio é válido no XSD.
        $xml = $this->assertDpsValida($this->createDpsWithIbscbs());

        self::assertStringContainsString('<gIBSCBS/>', $xml);
    }

    public function test_dps_fin_nfse_em_infdps_sem_ibscbs_validates_against_xsd(): void
    {
        $xml = $this->assertDpsValida($this->createDps(ibscbs: null));

        self::assertStringContainsString('<finNFSe>0</finNFSe>', $xml);
        self::assertStringNotContainsString('IBSCBS', $xml);
    }

    public function test_dps_prestador_e_tomador_com_cnpj_alfanumerico_validates_against_xsd(): void
    {
        // TSCNPJ [0-9A-Z]{14} e TSIdDPS com inscrição alfanumérica (NT 009, item 2.1).
        $dps = $this->createDpsComDocumentos(new Cnpj('12ABC34501DE35'), new Cnpj('11444777000161'));

        $xml = $this->assertDpsValida($dps);

        self::assertStringContainsString('<CNPJ>12ABC34501DE35</CNPJ>', $xml);
        self::assertMatchesRegularExpression('/Id="DPS3550308212ABC34501DE35[0-9]{20}"/', $xml);
    }

    public function test_dps_prestador_cpf_gera_id_valido_no_xsd(): void
    {
        // Regressão: antes o prestador CPF não conseguia gerar a chave (47 posições) e a emissão falhava.
        $dps = $this->createDpsComDocumentos(new Cpf('52998224725'), new Cnpj('11444777000161'));

        $xml = $this->assertDpsValida($dps);

        self::assertMatchesRegularExpression('/Id="DPS35503081' . '00052998224725' . '[0-9]{20}"/', $xml);
    }

    public function test_dps_descricao_de_2000_caracteres_acentuados_validates_against_xsd(): void
    {
        // TSDesc2000 limita em caracteres (xs:maxLength). 2000 "ç" ocupam 4000 bytes em UTF-8 e o
        // XSD aceita; a biblioteca recusava por medir bytes com strlen().
        $descricao = str_repeat('ç', 2000);
        $servico = new Servico(
            discriminacao: $descricao,
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(1000.00),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 2.0,
            totTribTipo: 'indTotTrib',
            indTotTrib: '0',
        );

        $xml = $this->assertDpsValida($this->createDps(servico: $servico, ibscbs: null));

        self::assertStringContainsString("<xDescServ>{$descricao}</xDescServ>", $xml);
    }

    public function test_dps_descricao_de_2001_caracteres_e_rejeitada_pelo_xsd(): void
    {
        // Contraprova: o limite do XSD é 2000 caracteres, nem um a mais. Constrói o XML sem passar
        // pela validação da entidade para exercitar somente o esquema.
        $xml = $this->xmlComDescricao(str_repeat('ç', 2000));
        $this->xsdValidator->validate($xml, 'DPS');

        $this->expectException(\MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Exception\XmlValidationException::class);
        $this->xsdValidator->validate($this->xmlComDescricao(str_repeat('ç', 2001)), 'DPS');
    }

    private function xmlComDescricao(string $descricao): string
    {
        $servico = new Servico(
            discriminacao: 'x',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(1000.00),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 2.0,
            totTribTipo: 'indTotTrib',
            indTotTrib: '0',
        );
        $dps = $this->createDps(servico: $servico, ibscbs: null);
        $dps->gerarIdDps();

        // Troca o texto direto no XML: só o esquema decide aqui.
        return str_replace('<xDescServ>x</xDescServ>', "<xDescServ>{$descricao}</xDescServ>", $this->builder->build($dps));
    }

    public function test_dps_serie_de_5_digitos_validates_against_xsd(): void
    {
        $dps = $this->createDps(ibscbs: null, serie: 89999);

        $xml = $this->assertDpsValida($dps);

        self::assertStringContainsString('<serie>89999</serie>', $xml);
    }

    public function test_dps_vserv_bruto_com_desconto_validates_against_xsd(): void
    {
        $servico = new Servico(
            discriminacao: 'Serviço com desconto',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(1000.00),
            descontoIncondicionado: new Money(100.00),
            descontoCondicionado: new Money(0),
            aliquotaIss: 2.0,
            totTribTipo: 'indTotTrib',
            indTotTrib: '0',
        );

        $xml = $this->assertDpsValida($this->createDps(servico: $servico, ibscbs: null));

        self::assertStringContainsString('<vServ>1000.00</vServ>', $xml);
        self::assertStringContainsString('<vDescIncond>100.00</vDescIncond>', $xml);
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
        $dps->gerarIdDps();

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
        $dps->gerarIdDps();

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
        $dps->gerarIdDps();

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
        $dps->gerarIdDps();

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
            cMun: new CodigoMunicipio('3550308'),
            unidades: [new IbsCbsUnidadeImobiliaria(inscImobFisc: '12345', cCIB: new CodigoCIB('87654321'))],
        );
        $refs = [
            new ChaveAcesso('12345678901234567890123456789012345678901234567890'),
        ];
        $ibscbs = new IbsCbsInfo(
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
        );
        $dps = $this->createDpsWithIbscbs(ibscbs: $ibscbs);
        $dps->gerarIdDps();

        $xml = $this->builder->build($dps);
        $this->xsdValidator->validate($xml, 'DPS');

        $this->expectNotToPerformAssertions();
    }

    public function test_dps_without_ibscbs_validates_against_xsd(): void
    {
        $dps = $this->createDps();
        $dps->gerarIdDps();

        $xml = $this->builder->build($dps);
        $this->xsdValidator->validate($xml, 'DPS');

        $this->expectNotToPerformAssertions();
    }

    public function test_dps_xml_element_order_matches_xsd(): void
    {
        $dps = $this->createDpsWithIbscbs();
        $dps->gerarIdDps();

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

        $expectedOrder = ['tpAmb', 'dhEmi', 'verAplic', 'serie', 'nDPS', 'dCompet', 'tpEmit', 'finNFSe', 'cLocEmi'];
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
        $dps->gerarIdDps();

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
        $dps->gerarIdDps();

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
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            totTribTipo: 'indTotTrib',
            indTotTrib: '0',
            codigoNbs: '123456789',
            comExterior: $comExterior,
        );
        $dps = $this->createDps(servico: $servico, ibscbs: $this->createIbscbs());
        $dps->gerarIdDps();

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
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            totTribTipo: 'indTotTrib',
            indTotTrib: '0',
            codigoNbs: '123456789',
            atvEvento: $atvEvento,
        );
        $dps = $this->createDps(servico: $servico, ibscbs: $this->createIbscbs());
        $dps->gerarIdDps();

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
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            totTribTipo: 'indTotTrib',
            indTotTrib: '0',
            codigoNbs: '123456789',
            infoCompl: $infoCompl,
        );
        $dps = $this->createDps(servico: $servico, ibscbs: $this->createIbscbs());
        $dps->gerarIdDps();

        $xml = $this->builder->build($dps);
        $this->xsdValidator->validate($xml, 'DPS');

        $this->expectNotToPerformAssertions();
    }

    public function test_dps_with_documentos_ajuste_validates_against_xsd(): void
    {
        $doc = new DocAjusteBC(
            tpAjusteBC: TipoAjusteBC::MATERIAIS,
            vTotDoc: '3000.00',
            vAjusteAplic: '3000.00',
            tipoDocumento: DocAjusteBC::DOC_DFE_NACIONAL,
            dtEmiDoc: new \DateTimeImmutable('2026-05-15'),
            tipoChaveDFe: '2',
            chaveDFe: '352605' . '11444777000161' . str_repeat('1', 24),
        );
        $servico = new Servico(
            discriminacao: 'test',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(5000.00),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            totTribTipo: 'indTotTrib',
            indTotTrib: '0',
            codigoNbs: '123456789',
            documentosAjusteBc: [$doc],
        );
        $dps = $this->createDps(servico: $servico, ibscbs: $this->createIbscbs());
        $dps->gerarIdDps();

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
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            totTribTipo: 'indTotTrib',
            indTotTrib: '0',
            codigoNbs: '123456789',
            exigSusp: $exigSusp,
            beneficioMunicipal: $bm,
        );
        $dps = $this->createDps(servico: $servico, ibscbs: $this->createIbscbs());
        $dps->gerarIdDps();

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
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            totTribTipo: 'indTotTrib',
            indTotTrib: '0',
            codigoNbs: '123456789',
            beneficioMunicipal: $bm,
        );
        $dps = $this->createDps(servico: $servico, ibscbs: $this->createIbscbs());
        $dps->gerarIdDps();

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
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            totTribTipo: 'indTotTrib',
            indTotTrib: '0',
            codigoNbs: '123456789',
            tribFederal: $tribFed,
        );
        $dps = $this->createDps(servico: $servico, ibscbs: $this->createIbscbs());
        $dps->gerarIdDps();

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
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            totTribTipo: 'indTotTrib',
            indTotTrib: '0',
            codigoNbs: '123456789',
            codigoPaisPrestacao: 'US',
        );
        $dps = $this->createDps(servico: $servico, ibscbs: $this->createIbscbs());
        $dps->gerarIdDps();

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
        $doc = new DocAjusteBC(
            tpAjusteBC: TipoAjusteBC::MATERIAIS,
            vTotDoc: '3000.00',
            vAjusteAplic: '3000.00',
            tipoDocumento: DocAjusteBC::DOC_DFE_NACIONAL,
            dtEmiDoc: new \DateTimeImmutable('2026-05-15'),
            tipoChaveDFe: '2',
            chaveDFe: '352605' . '11444777000161' . str_repeat('1', 24),
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
            documentosAjusteBc: [$doc],
            exigSusp: $exigSusp,
            beneficioMunicipal: $bm,
            tribFederal: $tribFed,
            totTribTipo: 'pTotTrib',
            pTotTribFed: 10.0,
            pTotTribEst: 5.0,
            pTotTribMun: 3.0,
        );
        $dps = $this->createDps(servico: $servico, ibscbs: $this->createIbscbs());
        $dps->gerarIdDps();

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
        $dps->gerarIdDps();

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
        $dps->gerarIdDps();

        $xml = $this->builder->build($dps);
        $this->xsdValidator->validate($xml, 'DPS');

        $this->expectNotToPerformAssertions();
    }

    private function createIbscbs(
        ?IbsCbsImovel $imovel = null,
        ?array $bensMoveis = null,
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
            bensMoveis: $bensMoveis,
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
        int $serie = 1,
        Cnpj|Cpf|null $prestadorDocumento = null,
        Cnpj|Cpf|null $tomadorDocumento = null,
    ): Dps {
        $endereco = $this->createEndereco();
        $cnpj = new Cnpj('11444777000161');

        return new Dps(
            tipoAmbiente: TipoAmbiente::HOMOLOGACAO,
            dataEmissao: new \DateTimeImmutable('2026-06-15T10:00:00'),
            versaoAplicacao: '1.0.0',
            serie: $serie,
            numero: 123,
            dataCompetencia: new \DateTimeImmutable('2026-06-01'),
            tipoEmissao: TipoEmitente::PRESTADOR,
            codigoMunicipioEmissor: new CodigoMunicipio('3550308'),
            prestador: new Prestador(
                documento: $prestadorDocumento ?? $cnpj,
                inscricaoMunicipal: '123456',
                razaoSocial: 'Prestador Ltda',
                telefone: null,
                email: null,
                endereco: $endereco,
                regimeTributario: RegimeTributario::SIMPLES_NACIONAL,
            ),
            tomador: new Tomador(
                documento: $tomadorDocumento ?? new Cnpj('33444555000181'),
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
                descontoIncondicionado: new Money(0),
                descontoCondicionado: new Money(0),
                aliquotaIss: 5.0,
                totTribTipo: 'indTotTrib',
                indTotTrib: '0',
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
    private function assertDpsValida(Dps $dps): string
    {
        $dps->gerarIdDps();
        $xml = $this->builder->build($dps);
        $this->xsdValidator->validate($xml, 'DPS');
        $this->addToAssertionCount(1);

        return $xml;
    }

    private function createDpsComDocumentos(Cnpj|Cpf $prestador, Cnpj|Cpf $tomador): Dps
    {
        return $this->createDps(prestadorDocumento: $prestador, tomadorDocumento: $tomador);
    }

    /** @param DocAjusteBC[]|null $docs */
    private function servicoComAjuste(?array $docs = null, ?float $pct = null, ?float $valor = null): Servico
    {
        return new Servico(
            discriminacao: 'Serviço com ajuste de base de cálculo',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(5000.00),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            totTribTipo: 'indTotTrib',
            indTotTrib: '0',
            codigoNbs: '123456789',
            documentosAjusteBc: $docs,
            percentualAjusteBcIssqn: $pct,
            valorAjusteBcIssqn: $valor,
        );
    }
}
