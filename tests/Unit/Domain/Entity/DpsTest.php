<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Dps;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Endereco;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Prestador;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Servico;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Tomador;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\FinalidadeNfse;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\RegimeTributario;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoAmbiente;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEmitente;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\VersaoSchema;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cep;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cnpj;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoMunicipio;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cpf;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class DpsTest extends TestCase
{
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

    private function createPrestador(): Prestador
    {
        return new Prestador(
            documento: new Cnpj('11444777000161'),
            inscricaoMunicipal: '123456',
            razaoSocial: 'Prestador Ltda',
            telefone: null,
            email: null,
            endereco: $this->createEndereco(),
            regimeTributario: RegimeTributario::SIMPLES_NACIONAL,
        );
    }

    private function createTomador(): Tomador
    {
        return new Tomador(
            documento: new Cpf('52998224725'),
            razaoSocial: 'Tomador Silva',
            telefone: null,
            email: null,
            endereco: $this->createEndereco(),
        );
    }

    private function createServico(): Servico
    {
        return new Servico(
            discriminacao: 'Servico de teste',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(1000.00),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            totTribTipo: 'indTotTrib',
            indTotTrib: '0',
        );
    }

    private function createDps(): Dps
    {
        return new Dps(
            tipoAmbiente: TipoAmbiente::HOMOLOGACAO,
            dataEmissao: new \DateTimeImmutable('2026-06-15T10:00:00'),
            versaoAplicacao: '1.0.0',
            serie: 1,
            numero: 123,
            dataCompetencia: new \DateTimeImmutable('2026-06-01'),
            tipoEmissao: TipoEmitente::PRESTADOR,
            codigoMunicipioEmissor: new CodigoMunicipio('3550308'),
            prestador: $this->createPrestador(),
            tomador: $this->createTomador(),
            servico: $this->createServico(),
        );
    }

    public function test_create_minimal_dps(): void
    {
        $dps = $this->createDps();

        $this->assertSame(TipoAmbiente::HOMOLOGACAO, $dps->getTipoAmbiente());
        $this->assertSame(1, $dps->getSerie());
        $this->assertSame(123, $dps->getNumero());
        $this->assertSame('1.0.0', $dps->getVersaoAplicacao());
        $this->assertSame(TipoEmitente::PRESTADOR, $dps->getTipoEmitente());
        $this->assertSame(VersaoSchema::V1_01, $dps->getVersao());
        $this->assertNull($dps->getIntermediario());
        $this->assertNull($dps->getSubstituicao());
        $this->assertNull($dps->getIbsCbs());
    }

    public function test_gerar_id_dps_com_cnpj(): void
    {
        $dps = $this->createDps();
        $id = $dps->gerarIdDps();

        // TSIdDPS: "DPS" + município (7) + tipo de inscrição (1) + inscrição (14) + série (5) + número (15).
        $this->assertSame('DPS' . '3550308' . '2' . '11444777000161' . '00001' . '000000000000123', $id);
        $this->assertSame(45, strlen($id));
        $this->assertSame($id, $dps->getIdDps());
    }

    public function test_gerar_id_dps_retorna_o_mesmo_valor_em_chamadas_repetidas(): void
    {
        $dps = $this->createDps();

        $this->assertSame($dps->gerarIdDps(), $dps->gerarIdDps());
    }

    public function test_gerar_id_dps_com_cnpj_alfanumerico(): void
    {
        $dps = $this->createDpsComPrestador(new Cnpj('12ABC34501DE35'));
        $id = $dps->gerarIdDps();

        $this->assertSame('DPS' . '3550308' . '2' . '12ABC34501DE35' . '00001' . '000000000000001', $id);
        $this->assertMatchesRegularExpression('/^DPS[0-9]{7}(1[0-9]{14}|2[0-9A-Z]{14})[0-9]{20}$/', $id);
    }

    public function test_gerar_id_dps_com_cpf_completa_a_inscricao_com_zeros(): void
    {
        $dps = $this->createDpsComPrestador(new Cpf('52998224725'));
        $id = $dps->gerarIdDps();

        // CPF: tipo 1 e inscrição de 14 posições, com 3 zeros à esquerda (TSIdDPS).
        $this->assertSame('DPS' . '3550308' . '1' . '00052998224725' . '00001' . '000000000000001', $id);
        $this->assertSame(45, strlen($id));
        $this->assertMatchesRegularExpression('/^DPS[0-9]{7}(1[0-9]{14}|2[0-9A-Z]{14})[0-9]{20}$/', $id);
    }

    public function test_gerar_id_dps_lanca_excecao_quando_prestador_nao_tem_documento(): void
    {
        $prestador = new Prestador(
            documento: null,
            inscricaoMunicipal: null,
            razaoSocial: 'Prestador NIF',
            telefone: null,
            email: null,
            endereco: $this->createEndereco(),
            regimeTributario: RegimeTributario::SIMPLES_NACIONAL,
            nif: '123456789',
        );

        $dps = $this->novaDps(prestador: $prestador);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Prestador deve ter CNPJ ou CPF');
        $dps->gerarIdDps();
    }

    public function test_finalidade_padrao_e_nfse_regular(): void
    {
        $this->assertSame(FinalidadeNfse::REGULAR, $this->createDps()->getFinNFSe());
    }

    public function test_invalid_serie_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Série da DPS deve ser maior que zero');

        $this->novaDps(serie: 0);
    }

    public function test_serie_acima_da_faixa_do_xsd_lanca_excecao(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Série da DPS deve ser no máximo 89999');

        $this->novaDps(serie: 90000);
    }

    public function test_serie_maxima_do_xsd_e_aceita(): void
    {
        $this->assertSame(89999, $this->novaDps(serie: 89999)->getSerie());
    }

    public function test_invalid_numero_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Número da DPS deve ser maior que zero');

        $this->novaDps(numero: -1);
    }

    public function test_getters(): void
    {
        $dps = $this->createDps();
        $dataEmissao = new \DateTimeImmutable('2026-06-15T10:00:00');
        $dataCompetencia = new \DateTimeImmutable('2026-06-01');

        $this->assertInstanceOf(\DateTimeImmutable::class, $dps->getDataEmissao());
        $this->assertEquals($dataEmissao, $dps->getDataEmissao());
        $this->assertEquals($dataCompetencia, $dps->getDataCompetencia());
        $this->assertInstanceOf(Prestador::class, $dps->getPrestador());
        $this->assertInstanceOf(Tomador::class, $dps->getTomador());
        $this->assertInstanceOf(Servico::class, $dps->getServico());
        $this->assertInstanceOf(CodigoMunicipio::class, $dps->getCodigoMunicipioEmissor());
        $this->assertSame('3550308', $dps->getCodigoMunicipioEmissor()->getCodigo());
    }

    public function test_id_dps_e_nulo_antes_da_geracao(): void
    {
        $this->assertNull($this->createDps()->getIdDps());
    }

    private function createDpsComPrestador(Cnpj|Cpf $documento): Dps
    {
        $prestador = new Prestador(
            documento: $documento,
            inscricaoMunicipal: null,
            razaoSocial: 'Prestador de teste',
            telefone: null,
            email: null,
            endereco: $this->createEndereco(),
            regimeTributario: RegimeTributario::SIMPLES_NACIONAL,
        );

        return $this->novaDps(prestador: $prestador, numero: 1);
    }

    private function novaDps(int $serie = 1, int $numero = 123, ?Prestador $prestador = null): Dps
    {
        return new Dps(
            tipoAmbiente: TipoAmbiente::HOMOLOGACAO,
            dataEmissao: new \DateTimeImmutable('2026-06-15T10:00:00'),
            versaoAplicacao: '1.0.0',
            serie: $serie,
            numero: $numero,
            dataCompetencia: new \DateTimeImmutable('2026-06-01'),
            tipoEmissao: TipoEmitente::PRESTADOR,
            codigoMunicipioEmissor: new CodigoMunicipio('3550308'),
            prestador: $prestador ?? $this->createPrestador(),
            tomador: $this->createTomador(),
            servico: $this->createServico(),
        );
    }
}
