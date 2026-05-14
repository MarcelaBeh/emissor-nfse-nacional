<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Infrastructure\Xml\Builder;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Dps;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Endereco;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsDest;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsDiferimento;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsInfo;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsTribRegular;
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
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cep;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cnpj;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoClassificacaoTributaria;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoCreditoPresumido;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoIndicadorOperacao;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoMunicipio;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoSituacaoTributaria;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cpf;
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

    private function createIbscbs(
        ?IndicadorFinal $indFinal = null,
        ?TipoOperacao $tpOper = null,
        ?TipoEnteGovernamental $tpEnteGov = null,
        ?CodigoCreditoPresumido $cCredPres = null,
        IndicadorDestinacao $indDest = IndicadorDestinacao::TOMADOR,
        ?IbsCbsDest $dest = null,
        ?IbsCbsTribRegular $tribRegular = null,
        ?IbsCbsDiferimento $diferimento = null,
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
        );
    }

    private function createDpsWithIbscbs(?IbsCbsInfo $ibscbs = null): Dps
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
            tipoEmissao: TipoEmissao::PRESTADOR,
            codigoMunicipioEmissor: new CodigoMunicipio('3550308'),
            prestador: new Prestador(
                documento: $cnpj,
                inscricaoMunicipal: '123456',
                razaoSocial: 'Prestador Ltda',
                nomeFantasia: 'Prestador',
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
            servico: new Servico(
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
            tipoEmissao: TipoEmissao::PRESTADOR,
            codigoMunicipioEmissor: new CodigoMunicipio('3550308'),
            prestador: new Prestador(
                documento: new Cnpj('11444777000161'),
                inscricaoMunicipal: '123456',
                razaoSocial: 'Prestador Ltda',
                nomeFantasia: null,
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
}
