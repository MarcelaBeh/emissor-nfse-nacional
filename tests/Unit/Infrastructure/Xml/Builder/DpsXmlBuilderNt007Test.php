<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Infrastructure\Xml\Builder;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Dps;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Endereco;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Prestador;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Servico;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Tomador;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\TribFederal;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\RegimeTributario;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoAmbiente;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEmitente;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cep;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cnpj;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoMunicipio;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cpf;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Money;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Builder\DpsXmlBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Cobertura das mudanças de leiaute/NT-007 portadas para a lib:
 * LIB-F (vBCPisCofins/vPis/vCofins), LIB-G (vTotTrib monetário) e
 * LIB-H (endereço opcional de tomador).
 */
final class DpsXmlBuilderNt007Test extends TestCase
{
    private DpsXmlBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new DpsXmlBuilder();
    }

    public function test_piscofins_emite_vbc_vpis_vcofins_na_ordem_do_xsd(): void
    {
        $tribFederal = new TribFederal(
            pisCofinsCst: '01',
            pisCofinsTipo: '0',
            pisCofinsAliquotaPis: 1.65,
            pisCofinsAliquotaCofins: 7.60,
            pisCofinsBaseCalculo: '1000.00',
            valorPis: '16.50',
            valorCofins: '76.00',
        );

        $xml = $this->builder->build($this->createDps(tribFederal: $tribFederal));

        $this->assertStringContainsString('<vBCPisCofins>1000.00</vBCPisCofins>', $xml);
        $this->assertStringContainsString('<vPis>16.50</vPis>', $xml);
        $this->assertStringContainsString('<vCofins>76.00</vCofins>', $xml);

        // Ordem do XSD: CST < vBCPisCofins < pAliqPis < pAliqCofins < vPis < vCofins < tpRetPisCofins.
        $posCst = strpos($xml, '<CST>01</CST>');
        $posBc = strpos($xml, '<vBCPisCofins>');
        $posAliqPis = strpos($xml, '<pAliqPis>');
        $posVPis = strpos($xml, '<vPis>');
        $posVCofins = strpos($xml, '<vCofins>');
        $posTpRet = strpos($xml, '<tpRetPisCofins>');

        $this->assertNotFalse($posCst);
        $this->assertLessThan($posBc, $posCst);
        $this->assertLessThan($posAliqPis, $posBc);
        $this->assertLessThan($posVPis, $posAliqPis);
        $this->assertLessThan($posVCofins, $posVPis);
        $this->assertLessThan($posTpRet, $posVCofins);
    }

    public function test_vtottrib_monetario_usa_valores_informados(): void
    {
        $servico = $this->createServico(
            totTribTipo: 'vTotTrib',
            vTotTribFed: 120.50,
            vTotTribEst: 30.00,
            vTotTribMun: 50.00,
        );

        $xml = $this->builder->build($this->createDps(servico: $servico));

        $this->assertStringContainsString('<vTotTribFed>120.50</vTotTribFed>', $xml);
        $this->assertStringContainsString('<vTotTribEst>30.00</vTotTribEst>', $xml);
        $this->assertStringContainsString('<vTotTribMun>50.00</vTotTribMun>', $xml);
    }

    public function test_vtottrib_municipal_recai_no_iss_quando_nao_informado(): void
    {
        // aliquotaIss 5% sobre 1000 = 50.00; sem valores informados o municipal vem do ISS.
        $servico = $this->createServico(totTribTipo: 'vTotTrib', aliquotaIss: 5.0);

        $xml = $this->builder->build($this->createDps(servico: $servico));

        $this->assertStringContainsString('<vTotTribFed>0.00</vTotTribFed>', $xml);
        $this->assertStringContainsString('<vTotTribEst>0.00</vTotTribEst>', $xml);
        $this->assertStringContainsString('<vTotTribMun>50.00</vTotTribMun>', $xml);
    }

    public function test_tomador_sem_endereco_omite_grupo_end(): void
    {
        $tomador = new Tomador(
            documento: new Cpf('52998224725'),
            razaoSocial: 'Consumidor Final',
            telefone: null,
            email: null,
            endereco: null,
        );

        $xml = $this->builder->build($this->createDps(tomador: $tomador));

        // O grupo <toma> sai, mas sem <end> dentro dele.
        $this->assertStringContainsString('<toma>', $xml);
        $posToma = strpos($xml, '<toma>');
        $posFimToma = strpos($xml, '</toma>');
        $trechoToma = substr($xml, $posToma, $posFimToma - $posToma);
        $this->assertStringNotContainsString('<end>', $trechoToma);
    }

    private function createDps(
        ?Servico $servico = null,
        ?Tomador $tomador = null,
        ?TribFederal $tribFederal = null,
    ): Dps {
        $endereco = new Endereco(
            logradouro: 'Rua Teste',
            numero: '123',
            complemento: null,
            bairro: 'Centro',
            codigoMunicipio: new CodigoMunicipio('3550308'),
            uf: 'SP',
            cep: new Cep('01001001'),
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
            tomador: $tomador ?? new Tomador(
                documento: new Cnpj('33444555000181'),
                razaoSocial: 'Tomador Ltda',
                telefone: null,
                email: null,
                endereco: $endereco,
            ),
            servico: $servico ?? $this->createServico($tribFederal !== null ? 'vTotTrib' : null, tribFederal: $tribFederal),
        );
        $dps->gerarChaveAcesso();

        return $dps;
    }

    private function createServico(
        ?string $totTribTipo = null,
        ?float $vTotTribFed = null,
        ?float $vTotTribEst = null,
        ?float $vTotTribMun = null,
        float $aliquotaIss = 5.0,
        ?TribFederal $tribFederal = null,
    ): Servico {
        return new Servico(
            discriminacao: 'Serviço de teste',
            codigoTributacao: '010101',
            valorServicos: new Money(1000.00),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: $aliquotaIss,
            localPrestacao: new CodigoMunicipio('3550308'),
            codigoNbs: '12345678',
            tribFederal: $tribFederal,
            totTribTipo: $totTribTipo,
            vTotTribFed: $vTotTribFed,
            vTotTribEst: $vTotTribEst,
            vTotTribMun: $vTotTribMun,
        );
    }
}
