<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Dps;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Endereco;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Prestador;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Servico;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Tomador;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\RegimeTributario;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoAmbiente;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEmitente;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\VersaoSchema;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cep;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\ChaveAcesso;
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

    public function test_gerar_chave_acesso_with_cnpj(): void
    {
        $dps = $this->createDps();
        $chave = $dps->gerarChaveAcesso();

        $this->assertInstanceOf(ChaveAcesso::class, $chave);
        $this->assertSame(50, strlen($chave->getChave()));
        $this->assertSame($chave, $dps->getChaveAcesso());
    }

    public function test_gerar_chave_acesso_returns_same_instance(): void
    {
        $dps = $this->createDps();
        $chave1 = $dps->gerarChaveAcesso();
        $chave2 = $dps->gerarChaveAcesso();

        $this->assertSame($chave1, $chave2);
    }

    public function test_gerar_chave_acesso_with_cpf_produces_shorter_key(): void
    {
        $prestador = new Prestador(
            documento: new Cpf('52998224725'),
            inscricaoMunicipal: null,
            razaoSocial: 'Prestador CPF',
            telefone: null,
            email: null,
            endereco: $this->createEndereco(),
            regimeTributario: RegimeTributario::SIMPLES_NACIONAL,
        );

        $dps = new Dps(
            tipoAmbiente: TipoAmbiente::HOMOLOGACAO,
            dataEmissao: new \DateTimeImmutable('2026-06-15T10:00:00'),
            versaoAplicacao: '1.0.0',
            serie: 1,
            numero: 1,
            dataCompetencia: new \DateTimeImmutable('2026-06-01'),
            tipoEmissao: TipoEmitente::PRESTADOR,
            codigoMunicipioEmissor: new CodigoMunicipio('3550308'),
            prestador: $prestador,
            tomador: $this->createTomador(),
            servico: $this->createServico(),
        );

        $this->expectException(\MarcelaBeh\EmissorNfseNacional\Domain\Exception\InvalidChaveAcessoException::class);
        $dps->gerarChaveAcesso();
    }

    public function test_gerar_chave_acesso_throws_when_prestador_has_no_documento(): void
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

        $dps = new Dps(
            tipoAmbiente: TipoAmbiente::HOMOLOGACAO,
            dataEmissao: new \DateTimeImmutable('2026-06-15T10:00:00'),
            versaoAplicacao: '1.0.0',
            serie: 1,
            numero: 1,
            dataCompetencia: new \DateTimeImmutable('2026-06-01'),
            tipoEmissao: TipoEmitente::PRESTADOR,
            codigoMunicipioEmissor: new CodigoMunicipio('3550308'),
            prestador: $prestador,
            tomador: $this->createTomador(),
            servico: $this->createServico(),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Prestador sem CNPJ/CPF');
        $dps->gerarChaveAcesso();
    }

    public function test_invalid_serie_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Série da DPS deve ser maior que zero');

        new Dps(
            tipoAmbiente: TipoAmbiente::HOMOLOGACAO,
            dataEmissao: new \DateTimeImmutable('2026-06-15T10:00:00'),
            versaoAplicacao: '1.0.0',
            serie: 0,
            numero: 1,
            dataCompetencia: new \DateTimeImmutable('2026-06-01'),
            tipoEmissao: TipoEmitente::PRESTADOR,
            codigoMunicipioEmissor: new CodigoMunicipio('3550308'),
            prestador: $this->createPrestador(),
            tomador: $this->createTomador(),
            servico: $this->createServico(),
        );
    }

    public function test_invalid_numero_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Número da DPS deve ser maior que zero');

        new Dps(
            tipoAmbiente: TipoAmbiente::HOMOLOGACAO,
            dataEmissao: new \DateTimeImmutable('2026-06-15T10:00:00'),
            versaoAplicacao: '1.0.0',
            serie: 1,
            numero: -1,
            dataCompetencia: new \DateTimeImmutable('2026-06-01'),
            tipoEmissao: TipoEmitente::PRESTADOR,
            codigoMunicipioEmissor: new CodigoMunicipio('3550308'),
            prestador: $this->createPrestador(),
            tomador: $this->createTomador(),
            servico: $this->createServico(),
        );
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

    public function test_chave_acesso_is_null_before_generation(): void
    {
        $dps = $this->createDps();
        $this->assertNull($dps->getChaveAcesso());
    }
}
