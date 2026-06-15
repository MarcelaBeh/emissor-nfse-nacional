<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Endereco;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Prestador;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\RegimeTributario;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cep;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cnpj;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoMunicipio;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cpf;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Email;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Telefone;
use PHPUnit\Framework\TestCase;

final class PrestadorTest extends TestCase
{
    private function createEndereco(): Endereco
    {
        return new Endereco(
            logradouro: 'Rua do Prestador',
            numero: '100',
            complemento: null,
            bairro: 'Centro',
            codigoMunicipio: new CodigoMunicipio('3550308'),
            cep: new Cep('01001001'),
        );
    }

    public function test_create_with_cnpj(): void
    {
        $cnpj = new Cnpj('11444777000161');
        $end = $this->createEndereco();
        $prestador = new Prestador(
            documento: $cnpj,
            inscricaoMunicipal: '12345',
            razaoSocial: 'Prestador Ltda',
            telefone: null,
            email: null,
            endereco: $end,
            regimeTributario: RegimeTributario::SIMPLES_NACIONAL,
        );

        $this->assertSame($cnpj, $prestador->getDocumento());
        $this->assertTrue($prestador->isCnpj());
        $this->assertSame($cnpj, $prestador->getCnpj());
        $this->assertNull($prestador->getCpf());
        $this->assertSame('12345', $prestador->getInscricaoMunicipal());
        $this->assertSame('Prestador Ltda', $prestador->getRazaoSocial());
        $this->assertNull($prestador->getTelefone());
        $this->assertNull($prestador->getEmail());
        $this->assertSame($end, $prestador->getEndereco());
        $this->assertSame(RegimeTributario::SIMPLES_NACIONAL, $prestador->getRegimeTributario());
        $this->assertNull($prestador->getNif());
        $this->assertNull($prestador->getCaepf());
        $this->assertNull($prestador->getCodigoNaoNif());
    }

    public function test_create_with_cpf(): void
    {
        $cpf = new Cpf('52998224725');
        $end = $this->createEndereco();
        $prestador = new Prestador(
            documento: $cpf,
            inscricaoMunicipal: null,
            razaoSocial: 'Fulano Prestador',
            telefone: null,
            email: null,
            endereco: $end,
            regimeTributario: RegimeTributario::REGIME_NORMAL,
        );

        $this->assertSame($cpf, $prestador->getDocumento());
        $this->assertFalse($prestador->isCnpj());
        $this->assertNull($prestador->getCnpj());
        $this->assertSame($cpf, $prestador->getCpf());
    }

    public function test_create_with_nif_instead_of_documento(): void
    {
        $end = $this->createEndereco();
        $prestador = new Prestador(
            documento: null,
            inscricaoMunicipal: null,
            razaoSocial: 'Prestador NIF',
            telefone: null,
            email: null,
            endereco: $end,
            regimeTributario: RegimeTributario::MEI,
            nif: '123456789',
        );

        $this->assertNull($prestador->getDocumento());
        $this->assertSame('123456789', $prestador->getNif());
    }

    public function test_create_with_codigo_nao_nif(): void
    {
        $end = $this->createEndereco();
        $prestador = new Prestador(
            documento: null,
            inscricaoMunicipal: null,
            razaoSocial: 'Prestador cNaoNIF',
            telefone: null,
            email: null,
            endereco: $end,
            regimeTributario: RegimeTributario::SIMPLES_NACIONAL,
            codigoNaoNif: '9999999',
        );

        $this->assertNull($prestador->getDocumento());
        $this->assertSame('9999999', $prestador->getCodigoNaoNif());
    }

    public function test_create_with_all_fields(): void
    {
        $cnpj = new Cnpj('11444777000161');
        $end = $this->createEndereco();
        $telefone = new Telefone('11988888888');
        $email = new Email('prestador@teste.com');
        $prestador = new Prestador(
            documento: $cnpj,
            inscricaoMunicipal: '54321',
            razaoSocial: 'Prestador Completo Ltda',
            telefone: $telefone,
            email: $email,
            endereco: $end,
            regimeTributario: RegimeTributario::SIMPLES_NACIONAL,
            nif: '123456789',
            caepf: '12345678901234',
            codigoNaoNif: null,
        );

        $this->assertSame($cnpj, $prestador->getCnpj());
        $this->assertSame('54321', $prestador->getInscricaoMunicipal());
        $this->assertSame('Prestador Completo Ltda', $prestador->getRazaoSocial());
        $this->assertSame($telefone, $prestador->getTelefone());
        $this->assertSame($email, $prestador->getEmail());
        $this->assertSame($end, $prestador->getEndereco());
        $this->assertSame(RegimeTributario::SIMPLES_NACIONAL, $prestador->getRegimeTributario());
        $this->assertSame('123456789', $prestador->getNif());
        $this->assertSame('12345678901234', $prestador->getCaepf());
        $this->assertNull($prestador->getCodigoNaoNif());
    }

    public function test_empty_razao_social_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Razão social é obrigatória');

        new Prestador(
            documento: null,
            inscricaoMunicipal: null,
            razaoSocial: '',
            telefone: null,
            email: null,
            endereco: $this->createEndereco(),
            regimeTributario: RegimeTributario::SIMPLES_NACIONAL,
            nif: '123456789',
        );
    }

    public function test_razao_social_exceeds_150_chars_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('150 caracteres');

        new Prestador(
            documento: null,
            inscricaoMunicipal: null,
            razaoSocial: str_repeat('A', 151),
            telefone: null,
            email: null,
            endereco: $this->createEndereco(),
            regimeTributario: RegimeTributario::SIMPLES_NACIONAL,
            nif: '123456789',
        );
    }

    public function test_no_documento_nif_nor_codigo_nao_nif_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Prestador deve ter CNPJ, CPF, NIF ou cNaoNIF');

        new Prestador(
            documento: null,
            inscricaoMunicipal: null,
            razaoSocial: 'Prestador sem doc',
            telefone: null,
            email: null,
            endereco: $this->createEndereco(),
            regimeTributario: RegimeTributario::SIMPLES_NACIONAL,
        );
    }

    public function test_create_with_tributario_normal(): void
    {
        $end = $this->createEndereco();
        $prestador = new Prestador(
            documento: new Cnpj('11444777000161'),
            inscricaoMunicipal: null,
            razaoSocial: 'Prestador Normal',
            telefone: null,
            email: null,
            endereco: $end,
            regimeTributario: RegimeTributario::REGIME_NORMAL,
        );

        $this->assertSame(RegimeTributario::REGIME_NORMAL, $prestador->getRegimeTributario());
    }

    public function test_create_with_telefone_email(): void
    {
        $end = $this->createEndereco();
        $telefone = new Telefone('11999999999');
        $email = new Email('prestador@contato.com');
        $prestador = new Prestador(
            documento: new Cnpj('11444777000161'),
            inscricaoMunicipal: null,
            razaoSocial: 'Prestador Contato',
            telefone: $telefone,
            email: $email,
            endereco: $end,
            regimeTributario: RegimeTributario::SIMPLES_NACIONAL,
        );

        $this->assertSame($telefone, $prestador->getTelefone());
        $this->assertSame($email, $prestador->getEmail());
    }
}
