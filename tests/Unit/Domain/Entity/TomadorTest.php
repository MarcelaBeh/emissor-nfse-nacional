<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Endereco;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Tomador;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cep;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cnpj;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoMunicipio;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cpf;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Email;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Telefone;
use PHPUnit\Framework\TestCase;

final class TomadorTest extends TestCase
{
    private function createEnderecoNacional(): Endereco
    {
        return new Endereco(
            logradouro: 'Rua do Tomador',
            numero: '200',
            complemento: null,
            bairro: 'Centro',
            codigoMunicipio: new CodigoMunicipio('3550308'),
            uf: 'SP',
            cep: new Cep('01001001'),
        );
    }

    public function test_create_with_cnpj(): void
    {
        $cnpj = new Cnpj('11444777000161');
        $end = $this->createEnderecoNacional();
        $tomador = new Tomador(
            documento: $cnpj,
            razaoSocial: 'Tomador Ltda',
            telefone: null,
            email: null,
            endereco: $end,
        );

        $this->assertSame($cnpj, $tomador->getDocumento());
        $this->assertTrue($tomador->isCnpj());
        $this->assertSame($cnpj, $tomador->getCnpj());
        $this->assertNull($tomador->getCpf());
        $this->assertSame('Tomador Ltda', $tomador->getRazaoSocial());
        $this->assertNull($tomador->getTelefone());
        $this->assertNull($tomador->getEmail());
        $this->assertSame($end, $tomador->getEndereco());
        $this->assertNull($tomador->getNif());
        $this->assertNull($tomador->getInscricaoMunicipal());
        $this->assertNull($tomador->getCodigoNaoNif());
        $this->assertNull($tomador->getCaepf());
    }

    public function test_create_with_cpf(): void
    {
        $cpf = new Cpf('52998224725');
        $end = $this->createEnderecoNacional();
        $tomador = new Tomador(
            documento: $cpf,
            razaoSocial: 'Fulano Tomador',
            telefone: null,
            email: null,
            endereco: $end,
        );

        $this->assertSame($cpf, $tomador->getDocumento());
        $this->assertFalse($tomador->isCnpj());
        $this->assertNull($tomador->getCnpj());
        $this->assertSame($cpf, $tomador->getCpf());
    }

    public function test_create_without_documento(): void
    {
        $end = $this->createEnderecoNacional();
        $tomador = new Tomador(
            documento: null,
            razaoSocial: 'Tomador sem Doc',
            telefone: null,
            email: null,
            endereco: $end,
        );

        $this->assertNull($tomador->getDocumento());
        $this->assertNull($tomador->getCnpj());
        $this->assertNull($tomador->getCpf());
    }

    public function test_create_with_nif_inscricao_municipal(): void
    {
        $end = $this->createEnderecoNacional();
        $tomador = new Tomador(
            documento: null,
            razaoSocial: 'Tomador NIF',
            telefone: null,
            email: null,
            endereco: $end,
            nif: '123456789',
            inscricaoMunicipal: '98765',
        );

        $this->assertSame('123456789', $tomador->getNif());
        $this->assertSame('98765', $tomador->getInscricaoMunicipal());
    }

    public function test_create_with_codigo_nao_nif_and_caepf(): void
    {
        $end = $this->createEnderecoNacional();
        $tomador = new Tomador(
            documento: null,
            razaoSocial: 'Tomador cNaoNIF',
            telefone: null,
            email: null,
            endereco: $end,
            codigoNaoNif: '9999999',
            caepf: '12345678901234',
        );

        $this->assertSame('9999999', $tomador->getCodigoNaoNif());
        $this->assertSame('12345678901234', $tomador->getCaepf());
    }

    public function test_create_with_telefone_email(): void
    {
        $end = $this->createEnderecoNacional();
        $telefone = new Telefone('11999999999');
        $email = new Email('tomador@teste.com');
        $tomador = new Tomador(
            documento: null,
            razaoSocial: 'Tomador Contato',
            telefone: $telefone,
            email: $email,
            endereco: $end,
        );

        $this->assertSame($telefone, $tomador->getTelefone());
        $this->assertSame($email, $tomador->getEmail());
    }

    public function test_create_empty_razao_social_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Razão social do tomador é obrigatória');

        new Tomador(
            documento: null,
            razaoSocial: '',
            telefone: null,
            email: null,
            endereco: $this->createEnderecoNacional(),
        );
    }

    public function test_create_razao_social_exceeds_150_chars_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Razão social do tomador deve ter no máximo 150 caracteres');

        new Tomador(
            documento: null,
            razaoSocial: str_repeat('A', 151),
            telefone: null,
            email: null,
            endereco: $this->createEnderecoNacional(),
        );
    }

    public function test_create_with_exterior_endereco(): void
    {
        $end = new Endereco(
            logradouro: 'Main Street',
            numero: '456',
            complemento: null,
            bairro: 'Downtown',
            codigoMunicipio: new CodigoMunicipio('3550308'),
            uf: 'SP',
            cep: new Cep('00000000'),
            codigoPais: '049',
            codigoPostalExterior: '10001',
            nomeCidadeExterior: 'New York',
            estadoProvinciaExterior: 'NY',
        );
        $tomador = new Tomador(
            documento: null,
            razaoSocial: 'Tomador Exterior',
            telefone: null,
            email: null,
            endereco: $end,
        );

        $this->assertTrue($tomador->getEndereco()->isExterior());
        $this->assertSame('049', $tomador->getEndereco()->getCodigoPais());
        $this->assertSame('New York', $tomador->getEndereco()->getNomeCidadeExterior());
    }

    public function test_create_without_endereco(): void
    {
        $tomador = new Tomador(
            documento: new Cpf('52998224725'),
            razaoSocial: 'Consumidor Final',
            telefone: null,
            email: null,
            endereco: null,
        );

        $this->assertNull($tomador->getEndereco());
        $this->assertSame('Consumidor Final', $tomador->getRazaoSocial());
    }

    public function test_create_with_all_fields(): void
    {
        $cnpj = new Cnpj('11444777000161');
        $end = $this->createEnderecoNacional();
        $telefone = new Telefone('11988888888');
        $email = new Email('tomador@completo.com');

        $tomador = new Tomador(
            documento: $cnpj,
            razaoSocial: 'Tomador Completo Ltda',
            telefone: $telefone,
            email: $email,
            endereco: $end,
            nif: '123456789',
            inscricaoMunicipal: '54321',
            codigoNaoNif: null,
            caepf: '12345678901234',
        );

        $this->assertSame($cnpj, $tomador->getCnpj());
        $this->assertSame('Tomador Completo Ltda', $tomador->getRazaoSocial());
        $this->assertSame($telefone, $tomador->getTelefone());
        $this->assertSame($email, $tomador->getEmail());
        $this->assertSame($end, $tomador->getEndereco());
        $this->assertSame('123456789', $tomador->getNif());
        $this->assertSame('54321', $tomador->getInscricaoMunicipal());
        $this->assertNull($tomador->getCodigoNaoNif());
        $this->assertSame('12345678901234', $tomador->getCaepf());
    }
}
