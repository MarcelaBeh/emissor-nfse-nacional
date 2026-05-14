<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Service;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Dps;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Endereco;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Prestador;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Servico;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Tomador;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\RegimeTributario;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoAmbiente;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEmissao;
use MarcelaBeh\EmissorNfseNacional\Domain\Service\DpsIdService;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cep;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cnpj;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoMunicipio;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cpf;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class DpsIdServiceTest extends TestCase
{
    private DpsIdService $service;

    protected function setUp(): void
    {
        $this->service = new DpsIdService();
    }

    public function test_generate_with_cnpj(): void
    {
        $dps = $this->createDpsWithCnpj('11444777000161', 1, 123);

        $id = $this->service->generate($dps);

        $this->assertSame(45, strlen($id));
        $this->assertStringStartsWith('DPS', $id);
        $this->assertSame('DPS355030821144477700016100001000000000000123', $id);
    }

    public function test_generate_with_cpf(): void
    {
        $dps = $this->createDpsWithCpf('52998224725', 1, 1);

        $id = $this->service->generate($dps);

        $this->assertSame(45, strlen($id));
        $this->assertStringStartsWith('DPS', $id);
        $this->assertSame('DPS355030810005299822472500001000000000000001', $id);
    }

    public function test_generate_large_numbers(): void
    {
        $dps = $this->createDpsWithCnpj('11444777000161', 99999, 999999999999999);

        $id = $this->service->generate($dps);

        $this->assertSame(45, strlen($id));
        $this->assertStringContainsString('99999', $id);
        $this->assertStringContainsString('999999999999999', $id);
    }

    public function test_generatePrefixedEvento(): void
    {
        $id = $this->service->generatePrefixedEvento(
            '35503080012345678901234567890123456789012345',
            '1',
            1
        );

        $this->assertSame(
            'PRE355030800123456789012345678901234567890123451001',
            $id
        );
    }

    private function createDpsWithCnpj(string $cnpj, int $serie, int $numero): Dps
    {
        return new Dps(
            tipoAmbiente: TipoAmbiente::HOMOLOGACAO,
            dataEmissao: new \DateTimeImmutable('2025-01-01'),
            versaoAplicacao: '1.0.0',
            serie: $serie,
            numero: $numero,
            dataCompetencia: new \DateTimeImmutable('2025-01-01'),
            tipoEmissao: TipoEmissao::PRESTADOR,
            codigoMunicipioEmissor: new CodigoMunicipio('3550308'),
            prestador: $this->createPrestadorWithCnpj($cnpj),
            tomador: $this->createTomador(),
            servico: $this->createServico(),
        );
    }

    private function createDpsWithCpf(string $cpf, int $serie, int $numero): Dps
    {
        return new Dps(
            tipoAmbiente: TipoAmbiente::HOMOLOGACAO,
            dataEmissao: new \DateTimeImmutable('2025-01-01'),
            versaoAplicacao: '1.0.0',
            serie: $serie,
            numero: $numero,
            dataCompetencia: new \DateTimeImmutable('2025-01-01'),
            tipoEmissao: TipoEmissao::PRESTADOR,
            codigoMunicipioEmissor: new CodigoMunicipio('3550308'),
            prestador: $this->createPrestadorWithCpf($cpf),
            tomador: $this->createTomador(),
            servico: $this->createServico(),
        );
    }

    private function createPrestadorWithCnpj(string $cnpj): Prestador
    {
        return new Prestador(
            documento: new Cnpj($cnpj),
            inscricaoMunicipal: '123456',
            razaoSocial: 'Empresa Ltda',
            telefone: null,
            email: null,
            endereco: $this->createEndereco(),
            regimeTributario: RegimeTributario::SIMPLES_NACIONAL,
        );
    }

    private function createPrestadorWithCpf(string $cpf): Prestador
    {
        return new Prestador(
            documento: new Cpf($cpf),
            inscricaoMunicipal: null,
            razaoSocial: 'Profissional Autônomo',
            telefone: null,
            email: null,
            endereco: $this->createEndereco(),
            regimeTributario: RegimeTributario::SIMPLES_NACIONAL,
        );
    }

    private function createTomador(): Tomador
    {
        return new Tomador(
            documento: new Cnpj('33444555000181'),
            razaoSocial: 'Tomador Ltda',
            nomeFantasia: null,
            telefone: null,
            email: null,
            endereco: $this->createEndereco(),
        );
    }

    private function createServico(): Servico
    {
        return new Servico(
            discriminacao: 'Serviço de teste',
            codigoTributacao: '123456',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(100.00),
            valorDeducoes: new Money(0),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
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
}
