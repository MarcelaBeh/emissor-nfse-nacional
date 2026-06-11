<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Infrastructure\Xml\Builder;

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
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsEnderecoObra;
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
use PHPUnit\Framework\TestCase;

final class DpsXmlBuilderIbscbsTest extends TestCase
{
    private DpsXmlBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new DpsXmlBuilder();
    }

    public function test_build_ibscbs_xml_contains_all_required_fields(): void
    {
        $ibscbs = $this->createIbscbs();
        $dps = $this->createDpsWithIbscbs(ibscbs: $ibscbs);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertXmlContains($xml, 'IBSCBS');
        $this->assertXmlContains($xml, 'finNFSe');
        $this->assertXmlContains($xml, 'cIndOp');
        $this->assertXmlContains($xml, 'indDest');
        $this->assertXmlContains($xml, 'CST');
        $this->assertXmlContains($xml, 'cClassTrib');
    }

    public function test_build_ibscbs_xml_without_ibscbs_does_not_contain_ibscbs(): void
    {
        $dps = $this->createDpsWithoutIbscbs();
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertStringNotContainsString('IBSCBS', $xml);
    }

    public function test_build_ibscbs_xml_with_dest(): void
    {
        $endereco = new Endereco(
            logradouro: 'Rua do Destinatário',
            numero: '456',
            complemento: null,
            bairro: 'Jardim',
            codigoMunicipio: new CodigoMunicipio('3550308'),
            uf: 'SP',
            cep: new Cep('01001001'),
        );
        $dest = new IbsCbsDest(
            cnpj: new Cnpj('11444777000161'),
            xNome: 'Destinatário Teste',
            endereco: $endereco,
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

        $this->assertXmlContains($xml, 'dest');
        $this->assertXmlContains($xml, 'Destinatário Teste');
        $this->assertXmlContains($xml, '11444777000161');
        $this->assertXmlContains($xml, '11999999999');
        $this->assertXmlContains($xml, 'dest@teste.com');
    }

    public function test_build_ibscbs_xml_with_trib_regular(): void
    {
        $tribRegular = new IbsCbsTribRegular(
            cstReg: new CodigoSituacaoTributaria('200'),
            cClassTribReg: new CodigoClassificacaoTributaria('200456'),
        );
        $ibscbs = $this->createIbscbs(tribRegular: $tribRegular);
        $dps = $this->createDpsWithIbscbs(ibscbs: $ibscbs);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertXmlContains($xml, 'gTribRegular');
        $this->assertXmlContains($xml, 'CSTReg');
        $this->assertXmlContains($xml, 'cClassTribReg');
        $this->assertXmlContains($xml, '200');
        $this->assertXmlContains($xml, '200456');
    }

    public function test_build_ibscbs_xml_with_diferimento(): void
    {
        $diferimento = new IbsCbsDiferimento(
            pDifUF: 10.5,
            pDifMun: 5.2,
            pDifCBS: 8.7,
        );
        $ibscbs = $this->createIbscbs(diferimento: $diferimento);
        $dps = $this->createDpsWithIbscbs(ibscbs: $ibscbs);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertXmlContains($xml, 'gDif');
        $this->assertXmlContains($xml, 'pDifUF');
        $this->assertXmlContains($xml, 'pDifMun');
        $this->assertXmlContains($xml, 'pDifCBS');
    }

    public function test_build_ibscbs_xml_with_indfinal(): void
    {
        $ibscbs = $this->createIbscbs(indFinal: IndicadorFinal::SIM);
        $dps = $this->createDpsWithIbscbs(ibscbs: $ibscbs);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertXmlContains($xml, 'indFinal');
    }

    public function test_build_ibscbs_xml_without_indfinal_omits_tag(): void
    {
        $ibscbs = $this->createIbscbs(indFinal: null);
        $dps = $this->createDpsWithIbscbs(ibscbs: $ibscbs);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertStringNotContainsString('indFinal', $xml);
    }

    public function test_build_ibscbs_xml_with_tp_oper(): void
    {
        $ibscbs = $this->createIbscbs(tpOper: TipoOperacao::FORNECIMENTO_POSTERIOR);
        $dps = $this->createDpsWithIbscbs(ibscbs: $ibscbs);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertXmlContains($xml, 'tpOper');
    }

    public function test_build_ibscbs_xml_with_tp_ente_gov(): void
    {
        $ibscbs = $this->createIbscbs(tpEnteGov: TipoEnteGovernamental::MUNICIPIO);
        $dps = $this->createDpsWithIbscbs(ibscbs: $ibscbs);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertXmlContains($xml, 'tpEnteGov');
    }

    public function test_build_ibscbs_xml_with_g_ref_nfse(): void
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

        $this->assertXmlContains($xml, 'gRefNFSe');
        $this->assertXmlContains($xml, 'refNFSe');
        $this->assertXmlContains($xml, '12345678901234567890123456789012345678901234567890');
        $this->assertXmlContains($xml, '22345678901234567890123456789012345678901234567890');
    }

    public function test_build_ibscbs_xml_with_ccredpres(): void
    {
        $ibscbs = $this->createIbscbs(
            cCredPres: new CodigoCreditoPresumido('01'),
        );
        $dps = $this->createDpsWithIbscbs(ibscbs: $ibscbs);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertXmlContains($xml, 'cCredPres');
        $this->assertXmlContains($xml, '01');
    }

    public function test_build_ibscbs_xml_with_all_optional_fields(): void
    {
        $endereco = new Endereco(
            logradouro: 'Rua Completa',
            numero: '789',
            complemento: 'Sala 10',
            bairro: 'Centro',
            codigoMunicipio: new CodigoMunicipio('3550308'),
            uf: 'SP',
            cep: new Cep('01001001'),
        );
        $dest = new IbsCbsDest(
            cnpj: new Cnpj('11444777000161'),
            xNome: 'Destinatário Completo',
            endereco: $endereco,
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
        );
        $dps = $this->createDpsWithIbscbs(ibscbs: $ibscbs);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertXmlContains($xml, 'IBSCBS');
        $this->assertXmlContains($xml, 'finNFSe');
        $this->assertXmlContains($xml, 'indFinal');
        $this->assertXmlContains($xml, 'cIndOp');
        $this->assertXmlContains($xml, 'tpOper');
        $this->assertXmlContains($xml, 'tpEnteGov');
        $this->assertXmlContains($xml, 'indDest');
        $this->assertXmlContains($xml, 'dest');
        $this->assertXmlContains($xml, 'CST');
        $this->assertXmlContains($xml, 'cClassTrib');
        $this->assertXmlContains($xml, 'cCredPres');
        $this->assertXmlContains($xml, 'gTribRegular');
        $this->assertXmlContains($xml, 'gDif');
        $this->assertXmlContains($xml, 'pDifUF');
        $this->assertXmlContains($xml, 'pDifMun');
        $this->assertXmlContains($xml, 'pDifCBS');
    }

    public function test_build_ibscbs_xml_validates_against_xsd_structure(): void
    {
        $ibscbs = $this->createIbscbs();
        $dps = $this->createDpsWithIbscbs(ibscbs: $ibscbs);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $dom = new \DOMDocument();
        $dom->loadXML($xml);

        $ibscbsNodes = $dom->getElementsByTagName('IBSCBS');
        $this->assertSame(1, $ibscbsNodes->length);

        $ibscbsEl = $ibscbsNodes->item(0);
        $this->assertNotNull($ibscbsEl);

        $children = [];
        foreach ($ibscbsEl->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $children[] = $child->localName;
            }
        }

        $expectedOrder = ['finNFSe', 'cIndOp', 'indDest', 'valores'];
        $this->assertSame($expectedOrder, $children);
    }

    public function test_build_ibscbs_xml_with_imovel_cib(): void
    {
        $imovel = new IbsCbsImovel(
            inscImobFisc: '12345',
            cCIB: new CodigoCIB('12345678'),
        );
        $ibscbs = $this->createIbscbs(imovel: $imovel);
        $dps = $this->createDpsWithIbscbs(ibscbs: $ibscbs);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertXmlContains($xml, 'imovel');
        $this->assertXmlContains($xml, 'inscImobFisc');
        $this->assertXmlContains($xml, '12345');
        $this->assertXmlContains($xml, 'cCIB');
        $this->assertXmlContains($xml, '12345678');
    }

    public function test_build_ibscbs_xml_with_imovel_endereco(): void
    {
        $endereco = new IbsCbsEnderecoObra(
            cep: '01001001',
            endExt: null,
            xLgr: 'Rua do Imóvel',
            nro: '100',
            xBairro: 'Centro',
            xCpl: 'Bloco B',
        );
        $imovel = new IbsCbsImovel(endereco: $endereco);
        $ibscbs = $this->createIbscbs(imovel: $imovel);
        $dps = $this->createDpsWithIbscbs(ibscbs: $ibscbs);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertXmlContains($xml, 'imovel');
        $this->assertXmlContains($xml, 'Rua do Imóvel');
        $this->assertXmlContains($xml, '100');
        $this->assertXmlContains($xml, 'Bloco B');
        $this->assertXmlContains($xml, 'Centro');
    }

    public function test_build_ibscbs_xml_with_g_ree_rep_res(): void
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

        $this->assertXmlContains($xml, 'gReeRepRes');
        $this->assertXmlContains($xml, 'documentos');
        $this->assertXmlContains($xml, 'dFeNacional');
        $this->assertXmlContains($xml, 'tipoChaveDFe');
        $this->assertXmlContains($xml, 'chaveDFe');
        $this->assertXmlContains($xml, 'dtEmiDoc');
        $this->assertXmlContains($xml, 'dtCompDoc');
        $this->assertXmlContains($xml, 'tpReeRepRes');
        $this->assertXmlContains($xml, 'vlrReeRepRes');
        $this->assertXmlContains($xml, '1500.00');
        $this->assertXmlContains($xml, '2026-01-15');
        $this->assertXmlContains($xml, '01');
    }

    public function test_build_ibscbs_xml_with_imovel_in_correct_position(): void
    {
        $imovel = new IbsCbsImovel(
            cCIB: new CodigoCIB('12345678'),
        );
        $dest = new IbsCbsDest(xNome: 'Dest');
        $ibscbs = $this->createIbscbs(
            indDest: IndicadorDestinacao::TERCEIRO,
            dest: $dest,
            imovel: $imovel,
        );
        $dps = $this->createDpsWithIbscbs(ibscbs: $ibscbs);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        $ibscbsEl = $dom->getElementsByTagName('IBSCBS')->item(0);

        $children = [];
        foreach ($ibscbsEl->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $children[] = $child->localName;
            }
        }

        $destIdx = array_search('dest', $children, true);
        $imovelIdx = array_search('imovel', $children, true);
        $valoresIdx = array_search('valores', $children, true);

        $this->assertNotFalse($destIdx, 'dest deve estar presente');
        $this->assertNotFalse($imovelIdx, 'imovel deve estar presente');
        $this->assertNotFalse($valoresIdx, 'valores deve estar presente');
        $this->assertLessThan($valoresIdx, $imovelIdx, 'imovel deve vir antes de valores');
        $this->assertGreaterThan($destIdx, $imovelIdx, 'imovel deve vir depois de dest');
    }

    public function test_build_xml_with_obra(): void
    {
        $obra = new Obra(
            cObra: 'CNO123456789',
            inscImobFisc: '99999',
        );
        $endereco = new Endereco(
            logradouro: 'Rua Teste',
            numero: '123',
            complemento: null,
            bairro: 'Centro',
            codigoMunicipio: new CodigoMunicipio('3550308'),
            uf: 'SP',
            cep: new Cep('01001001'),
        );
        $servico = new Servico(
            discriminacao: 'Serviço de obra',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(5000.00),
            valorDeducoes: new Money(0),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            codigoNbs: '12345678',
            obra: $obra,
        );
        $dps = new Dps(
            tipoAmbiente: TipoAmbiente::HOMOLOGACAO,
            dataEmissao: new \DateTimeImmutable('2026-06-15T10:00:00'),
            versaoAplicacao: '1.0.0',
            serie: 1,
            numero: 123,
            dataCompetencia: new \DateTimeImmutable('2026-06-01'),
            tipoEmissao: TipoEmitente::PRESTADOR,
            codigoMunicipioEmissor: new CodigoMunicipio('3550308'),
            prestador: new Prestador(
                documento: new Cnpj('11444777000161'),
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
            servico: $servico,
        );
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertXmlContains($xml, 'obra');
        $this->assertXmlContains($xml, 'cObra');
        $this->assertXmlContains($xml, 'CNO123456789');
        $this->assertXmlContains($xml, 'inscImobFisc');
        $this->assertXmlContains($xml, '99999');
    }

    public function test_build_xml_with_obra_endereco(): void
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
        $endereco = new Endereco(
            logradouro: 'Rua Teste',
            numero: '123',
            complemento: null,
            bairro: 'Centro',
            codigoMunicipio: new CodigoMunicipio('3550308'),
            uf: 'SP',
            cep: new Cep('01001001'),
        );
        $servico = new Servico(
            discriminacao: 'Serviço de obra',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(5000.00),
            valorDeducoes: new Money(0),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            codigoNbs: '12345678',
            obra: $obra,
        );
        $dps = new Dps(
            tipoAmbiente: TipoAmbiente::HOMOLOGACAO,
            dataEmissao: new \DateTimeImmutable('2026-06-15T10:00:00'),
            versaoAplicacao: '1.0.0',
            serie: 1,
            numero: 123,
            dataCompetencia: new \DateTimeImmutable('2026-06-01'),
            tipoEmissao: TipoEmitente::PRESTADOR,
            codigoMunicipioEmissor: new CodigoMunicipio('3550308'),
            prestador: new Prestador(
                documento: new Cnpj('11444777000161'),
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
            servico: $servico,
        );
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertXmlContains($xml, 'obra');
        $this->assertXmlContains($xml, 'end');
        $this->assertXmlContains($xml, 'Rua da Obra');
        $this->assertXmlContains($xml, '500');
        $this->assertXmlContains($xml, 'Galpão 2');
        $this->assertXmlContains($xml, 'Industrial');
    }

    // --- Novos campos Servico/valores builder tests ---

    public function test_build_xml_with_codigo_pais_prestacao(): void
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
            codigoNbs: '12345678',
            codigoPaisPrestacao: 'US',
        );
        $dps = $this->createDpsWithIbscbs(servico: $servico);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertXmlContains($xml, 'cPaisPrestacao');
        $this->assertXmlContains($xml, 'US');
    }

    public function test_build_xml_with_codigo_tributacao_municipal(): void
    {
        $servico = new Servico(
            discriminacao: 'test',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(1000.00),
            valorDeducoes: new Money(0),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            codigoNbs: '12345678',
            codigoTributacaoMunicipal: '123',
        );
        $dps = $this->createDpsWithIbscbs(servico: $servico);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertXmlContains($xml, 'cTribMun');
        $this->assertXmlContains($xml, '123');
    }

    public function test_build_xml_with_codigo_interno_contribuinte(): void
    {
        $servico = new Servico(
            discriminacao: 'test',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(1000.00),
            valorDeducoes: new Money(0),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            codigoNbs: '12345678',
            codigoInternoContribuinte: 'INT001',
        );
        $dps = $this->createDpsWithIbscbs(servico: $servico);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertXmlContains($xml, 'cIntContrib');
        $this->assertXmlContains($xml, 'INT001');
    }

    public function test_build_xml_with_valor_recebido(): void
    {
        $servico = new Servico(
            discriminacao: 'test',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(1000.00),
            valorDeducoes: new Money(0),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            codigoNbs: '12345678',
            valorRecebido: 800.00,
        );
        $dps = $this->createDpsWithIbscbs(servico: $servico);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertXmlContains($xml, 'vReceb');
        $this->assertXmlContains($xml, '800.00');
    }

    public function test_build_xml_with_com_exterior(): void
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
            numeroDeclaracaoImportacao: '25DI1234567',
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
            codigoNbs: '12345678',
            comExterior: $comExterior,
        );
        $dps = $this->createDpsWithIbscbs(servico: $servico);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertXmlContains($xml, 'comExt');
        $this->assertXmlContains($xml, 'mdPrestacao');
        $this->assertXmlContains($xml, 'vincPrest');
        $this->assertXmlContains($xml, 'tpMoeda');
        $this->assertXmlContains($xml, '840');
        $this->assertXmlContains($xml, 'vServMoeda');
        $this->assertXmlContains($xml, 'nDI');
        $this->assertXmlContains($xml, '25DI1234567');
    }

    public function test_build_xml_with_atv_evento(): void
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
            codigoNbs: '12345678',
            atvEvento: $atvEvento,
        );
        $dps = $this->createDpsWithIbscbs(servico: $servico);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertXmlContains($xml, 'atvEvento');
        $this->assertXmlContains($xml, 'Feira Tecnológica');
        $this->assertXmlContains($xml, '2026-06-01');
        $this->assertXmlContains($xml, '2026-06-10');
        $this->assertXmlContains($xml, 'EVT-001');
    }

    public function test_build_xml_with_info_compl(): void
    {
        $infoCompl = new InfoCompl(
            idDocTecnico: 'CONTR-001',
            docReferencia: 'PROP-001',
            numeroPedido: 'PED-001',
            itensPedido: ['Item 1', 'Item 2'],
            infoComplementar: 'Observações complementares',
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
            codigoNbs: '12345678',
            infoCompl: $infoCompl,
        );
        $dps = $this->createDpsWithIbscbs(servico: $servico);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertXmlContains($xml, 'infoCompl');
        $this->assertXmlContains($xml, 'idDocTec');
        $this->assertXmlContains($xml, 'CONTR-001');
        $this->assertXmlContains($xml, 'docRef');
        $this->assertXmlContains($xml, 'xPed');
        $this->assertXmlContains($xml, 'PED-001');
        $this->assertXmlContains($xml, 'gItemPed');
        $this->assertXmlContains($xml, 'xInfComp');
        $this->assertXmlContains($xml, 'Observações complementares');
    }

    public function test_build_xml_with_documentos_deducao(): void
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
            codigoNbs: '12345678',
            documentosDeducao: [$doc],
        );
        $dps = $this->createDpsWithIbscbs(servico: $servico);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertXmlContains($xml, 'vDedRed');
        $this->assertXmlContains($xml, 'docDedRed');
        $this->assertXmlContains($xml, 'chNFe');
        $this->assertXmlContains($xml, 'tpDedRed');
        $this->assertXmlContains($xml, 'dtEmiDoc');
        $this->assertXmlContains($xml, 'vDedutivelRedutivel');
        $this->assertXmlContains($xml, 'vDeducaoReducao');
    }

    public function test_build_xml_with_exig_susp(): void
    {
        $exigSusp = new ExigSusp(
            tipoSuspensao: 1,
            numeroProcesso: 'PROC-12345',
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
            codigoNbs: '12345678',
            exigSusp: $exigSusp,
        );
        $dps = $this->createDpsWithIbscbs(servico: $servico);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertXmlContains($xml, 'exigSusp');
        $this->assertXmlContains($xml, 'tpSusp');
        $this->assertXmlContains($xml, 'nProcesso');
        $this->assertXmlContains($xml, 'PROC-12345');
    }

    public function test_build_xml_with_beneficio_municipal(): void
    {
        $bm = new BeneficioMunicipal(numeroBeneficio: 'BM-001');
        $servico = new Servico(
            discriminacao: 'test',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(1000.00),
            valorDeducoes: new Money(0),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            codigoNbs: '12345678',
            beneficioMunicipal: $bm,
        );
        $dps = $this->createDpsWithIbscbs(servico: $servico);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertXmlContains($xml, 'BM');
        $this->assertXmlContains($xml, 'nBM');
        $this->assertXmlContains($xml, 'BM-001');
    }

    public function test_build_xml_with_trib_federal(): void
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
            codigoNbs: '12345678',
            tribFederal: $tribFed,
        );
        $dps = $this->createDpsWithIbscbs(servico: $servico);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertXmlContains($xml, 'tribFed');
        $this->assertXmlContains($xml, 'piscofins');
        $this->assertXmlContains($xml, 'CST');
        $this->assertXmlContains($xml, 'pAliqPis');
        $this->assertXmlContains($xml, 'pAliqCofins');
        $this->assertXmlContains($xml, 'vRetCP');
        $this->assertXmlContains($xml, 'vRetIRRF');
        $this->assertXmlContains($xml, 'vRetCSLL');
    }

    public function test_build_xml_with_tot_trib_v_tot_trib(): void
    {
        $servico = new Servico(
            discriminacao: 'test',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(1000.00),
            valorDeducoes: new Money(0),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            codigoNbs: '12345678',
            totTribTipo: 'vTotTrib',
        );
        $dps = $this->createDpsWithIbscbs(servico: $servico);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertXmlContains($xml, 'totTrib');
        $this->assertXmlContains($xml, 'vTotTrib');
    }

    public function test_build_xml_with_tot_trib_p_tot_trib(): void
    {
        $servico = new Servico(
            discriminacao: 'test',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(1000.00),
            valorDeducoes: new Money(0),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            codigoNbs: '12345678',
            totTribTipo: 'pTotTrib',
            pTotTribFed: 10.0,
            pTotTribEst: 5.0,
            pTotTribMun: 3.0,
        );
        $dps = $this->createDpsWithIbscbs(servico: $servico);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertXmlContains($xml, 'totTrib');
        $this->assertXmlContains($xml, 'pTotTrib');
        $this->assertXmlContains($xml, 'pTotTribFed');
        $this->assertXmlContains($xml, 'pTotTribEst');
        $this->assertXmlContains($xml, 'pTotTribMun');
    }

    public function test_build_xml_with_tot_trib_ind_tot_trib(): void
    {
        $servico = new Servico(
            discriminacao: 'test',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(1000.00),
            valorDeducoes: new Money(0),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            codigoNbs: '12345678',
            totTribTipo: 'indTotTrib',
            indTotTrib: '0',
        );
        $dps = $this->createDpsWithIbscbs(servico: $servico);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertXmlContains($xml, 'totTrib');
        $this->assertXmlContains($xml, 'indTotTrib');
    }

    public function test_build_xml_with_tot_trib_p_tot_trib_sn(): void
    {
        $servico = new Servico(
            discriminacao: 'test',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(1000.00),
            valorDeducoes: new Money(0),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            codigoNbs: '12345678',
            totTribTipo: 'pTotTribSN',
            pTotTribSN: 15.0,
        );
        $dps = $this->createDpsWithIbscbs(servico: $servico);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertXmlContains($xml, 'totTrib');
        $this->assertXmlContains($xml, 'pTotTribSN');
    }

    /** @param ChaveAcesso[]|null $refNFSeList */
    private function createIbscbs(
        ?IndicadorFinal $indFinal = null,
        ?TipoOperacao $tpOper = null,
        ?TipoEnteGovernamental $tpEnteGov = null,
        ?CodigoCreditoPresumido $cCredPres = null,
        IndicadorDestinacao $indDest = IndicadorDestinacao::TOMADOR,
        ?IbsCbsDest $dest = null,
        ?IbsCbsTribRegular $tribRegular = null,
        ?IbsCbsDiferimento $diferimento = null,
        ?array $refNFSeList = null,
        ?IbsCbsImovel $imovel = null,
        ?IbsCbsReeRepRes $reeRepRes = null,
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

    public function test_build_xml_with_intermediario(): void
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
        $ibscbs = $this->createIbscbs();
        $dps = $this->createDpsWithIbscbs(ibscbs: $ibscbs, intermediario: $intermediario);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertXmlContains($xml, '<interm>');
        $this->assertXmlContains($xml, 'Intermediario Ltda');
        $this->assertXmlContains($xml, '<IM>78901</IM>');
    }

    public function test_build_xml_with_substituicao(): void
    {
        $chave = new ChaveAcesso('12345678901234567890123456789012345678901234567890');
        $substituicao = new Substituicao(
            chaveSubstituida: $chave,
            codigoMotivo: '02',
        );
        $ibscbs = $this->createIbscbs();
        $dps = $this->createDpsWithIbscbs(ibscbs: $ibscbs, substituicao: $substituicao);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertXmlContains($xml, '<subst>');
        $this->assertXmlContains($xml, '<cMotivo>02</cMotivo>');
        $this->assertXmlContains($xml, '<chSubstda');
    }

    public function test_build_xml_without_intermediario(): void
    {
        $dps = $this->createDpsWithIbscbs();
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->assertXmlNotContains($xml, '<interm>');
    }

    private function createDpsWithIbscbs(?IbsCbsInfo $ibscbs = null, ?Servico $servico = null, ?Intermediario $intermediario = null, ?Substituicao $substituicao = null, ?MotivoEmissaoTI $cMotivoEmisTI = null, ?ChaveAcesso $chNFSeRej = null): Dps
    {
        $cnpj = new Cnpj('11444777000161');
        $endereco = new Endereco(
            logradouro: 'Rua Teste',
            numero: '123',
            complemento: null,
            bairro: 'Centro',
            codigoMunicipio: new CodigoMunicipio('3550308'),
            uf: 'SP',
            cep: new Cep('01001001'),
        );

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
                codigoNbs: '12345678',
            ),
            ibscbs: $ibscbs,
            intermediario: $intermediario,
            substituicao: $substituicao,
            cMotivoEmisTI: $cMotivoEmisTI,
            chNFSeRej: $chNFSeRej,
        );
    }

    private function createDpsWithoutIbscbs(): Dps
    {
        $endereco = new Endereco(
            logradouro: 'Rua Teste',
            numero: '123',
            complemento: null,
            bairro: 'Centro',
            codigoMunicipio: new CodigoMunicipio('3550308'),
            uf: 'SP',
            cep: new Cep('01001001'),
        );

        return new Dps(
            tipoAmbiente: TipoAmbiente::HOMOLOGACAO,
            dataEmissao: new \DateTimeImmutable('2025-01-01'),
            versaoAplicacao: '1.0.0',
            serie: 1,
            numero: 123,
            dataCompetencia: new \DateTimeImmutable('2025-01-01'),
            tipoEmissao: TipoEmitente::PRESTADOR,
            codigoMunicipioEmissor: new CodigoMunicipio('3550308'),
            prestador: new Prestador(
                documento: new Cnpj('11444777000161'),
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
            servico: new Servico(
                discriminacao: 'Serviço de teste',
                codigoTributacao: '010101',
                localPrestacao: new CodigoMunicipio('3550308'),
                valorServicos: new Money(1000.00),
                valorDeducoes: new Money(0),
                descontoIncondicionado: new Money(0),
                descontoCondicionado: new Money(0),
                aliquotaIss: 5.0,
            ),
        );
    }

    private function assertXmlContains(string $xml, string $expected): void
    {
        $this->assertStringContainsString(
            $expected,
            $xml,
            "XML deve conter '{$expected}'"
        );
    }

    private function assertXmlNotContains(string $xml, string $expected): void
    {
        $this->assertStringNotContainsString(
            $expected,
            $xml,
            "XML não deve conter '{$expected}'"
        );
    }
}
