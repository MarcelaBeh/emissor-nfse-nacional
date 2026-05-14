<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Endereco;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Intermediario;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cep;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cnpj;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoMunicipio;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cpf;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Email;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Telefone;
use PHPUnit\Framework\TestCase;

final class IntermediarioTest extends TestCase
{
    private function createEnderecoNacional(): Endereco
    {
        return new Endereco(
            logradouro: 'Rua do Intermediario',
            numero: '100',
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
        $inter = new Intermediario(
            documento: $cnpj,
            razaoSocial: 'Intermediario Ltda',
            inscricaoMunicipal: '12345',
            telefone: null,
            email: null,
            endereco: $end,
        );

        $this->assertSame($cnpj, $inter->getDocumento());
        $this->assertTrue($inter->isCnpj());
        $this->assertSame($cnpj, $inter->getCnpj());
        $this->assertNull($inter->getCpf());
        $this->assertSame('Intermediario Ltda', $inter->getRazaoSocial());
        $this->assertSame('12345', $inter->getInscricaoMunicipal());
        $this->assertNull($inter->getTelefone());
        $this->assertNull($inter->getEmail());
        $this->assertSame($end, $inter->getEndereco());
        $this->assertNull($inter->getNif());
        $this->assertNull($inter->getCodigoNaoNif());
        $this->assertNull($inter->getCaepf());
    }

    public function test_create_with_cpf(): void
    {
        $cpf = new Cpf('52998224725');
        $end = $this->createEnderecoNacional();
        $inter = new Intermediario(
            documento: $cpf,
            razaoSocial: 'Fulano Intermediario',
            inscricaoMunicipal: null,
            telefone: null,
            email: null,
            endereco: $end,
        );

        $this->assertSame($cpf, $inter->getDocumento());
        $this->assertFalse($inter->isCnpj());
        $this->assertNull($inter->getCnpj());
        $this->assertSame($cpf, $inter->getCpf());
    }

    public function test_create_without_documento(): void
    {
        $end = $this->createEnderecoNacional();
        $inter = new Intermediario(
            documento: null,
            razaoSocial: 'Intermediario sem Doc',
            inscricaoMunicipal: null,
            telefone: null,
            email: null,
            endereco: $end,
            nif: '123456789',
            codigoNaoNif: '9999999',
            caepf: '12345678901234',
        );

        $this->assertNull($inter->getDocumento());
        $this->assertNull($inter->getCnpj());
        $this->assertNull($inter->getCpf());
        $this->assertSame('123456789', $inter->getNif());
        $this->assertSame('9999999', $inter->getCodigoNaoNif());
        $this->assertSame('12345678901234', $inter->getCaepf());
    }

    public function test_create_with_telefone_email(): void
    {
        $end = $this->createEnderecoNacional();
        $telefone = new Telefone('11999999999');
        $email = new Email('inter@teste.com');
        $inter = new Intermediario(
            documento: null,
            razaoSocial: 'Intermediario Contato',
            inscricaoMunicipal: null,
            telefone: $telefone,
            email: $email,
            endereco: $end,
        );

        $this->assertSame($telefone, $inter->getTelefone());
        $this->assertSame($email, $inter->getEmail());
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
        $inter = new Intermediario(
            documento: null,
            razaoSocial: 'Intermediario Exterior',
            inscricaoMunicipal: null,
            telefone: null,
            email: null,
            endereco: $end,
        );

        $this->assertTrue($inter->getEndereco()->isExterior());
        $this->assertSame('049', $inter->getEndereco()->getCodigoPais());
        $this->assertSame('New York', $inter->getEndereco()->getNomeCidadeExterior());
    }

    public function test_create_empty_razao_social_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Razão social do intermediário é obrigatória');

        new Intermediario(
            documento: null,
            razaoSocial: '',
            inscricaoMunicipal: null,
            telefone: null,
            email: null,
            endereco: $this->createEnderecoNacional(),
        );
    }

    public function test_create_razao_social_exceeds_150_chars_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Razão social do intermediário deve ter no máximo 150 caracteres');

        new Intermediario(
            documento: null,
            razaoSocial: str_repeat('A', 151),
            inscricaoMunicipal: null,
            telefone: null,
            email: null,
            endereco: $this->createEnderecoNacional(),
        );
    }

    public function test_create_with_all_fields(): void
    {
        $cnpj = new Cnpj('11444777000161');
        $end = $this->createEnderecoNacional();
        $telefone = new Telefone('11999999999');
        $email = new Email('inter@completo.com');

        $inter = new Intermediario(
            documento: $cnpj,
            razaoSocial: 'Intermediario Completo Ltda',
            inscricaoMunicipal: '54321',
            telefone: $telefone,
            email: $email,
            endereco: $end,
            nif: '123456789',
            codigoNaoNif: null,
            caepf: '12345678901234',
        );

        $this->assertSame($cnpj, $inter->getCnpj());
        $this->assertSame('Intermediario Completo Ltda', $inter->getRazaoSocial());
        $this->assertSame('54321', $inter->getInscricaoMunicipal());
        $this->assertSame($telefone, $inter->getTelefone());
        $this->assertSame($email, $inter->getEmail());
        $this->assertSame($end, $inter->getEndereco());
        $this->assertSame('123456789', $inter->getNif());
        $this->assertNull($inter->getCodigoNaoNif());
        $this->assertSame('12345678901234', $inter->getCaepf());
    }
}
