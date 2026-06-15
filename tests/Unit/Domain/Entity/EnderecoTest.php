<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Endereco;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cep;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoMunicipio;
use PHPUnit\Framework\TestCase;

final class EnderecoTest extends TestCase
{
    public function test_create_nacional(): void
    {
        $end = new Endereco(
            logradouro: 'Rua Exemplo',
            numero: '123',
            complemento: null,
            bairro: 'Centro',
            codigoMunicipio: new CodigoMunicipio('3550308'),
            cep: new Cep('01001001'),
        );

        $this->assertSame('Rua Exemplo', $end->getLogradouro());
        $this->assertSame('123', $end->getNumero());
        $this->assertNull($end->getComplemento());
        $this->assertSame('Centro', $end->getBairro());
        $this->assertSame('3550308', $end->getCodigoMunicipio()->getCodigo());
        $this->assertSame('01001001', $end->getCep()->getCep());
        $this->assertNull($end->getCodigoPais());
        $this->assertNull($end->getCodigoPostalExterior());
        $this->assertNull($end->getNomeCidadeExterior());
        $this->assertNull($end->getEstadoProvinciaExterior());
        $this->assertFalse($end->isExterior());
    }

    public function test_create_exterior(): void
    {
        // Exterior: cMun/CEP nacionais não se aplicam (choice endExt) — são null.
        $end = new Endereco(
            logradouro: 'Main Street',
            numero: '456',
            complemento: 'Suite 200',
            bairro: 'Downtown',
            codigoMunicipio: null,
            cep: null,
            codigoPais: '049',
            codigoPostalExterior: '10001',
            nomeCidadeExterior: 'New York',
            estadoProvinciaExterior: 'NY',
        );

        $this->assertSame('Main Street', $end->getLogradouro());
        $this->assertSame('456', $end->getNumero());
        $this->assertSame('Suite 200', $end->getComplemento());
        $this->assertSame('Downtown', $end->getBairro());
        $this->assertNull($end->getCodigoMunicipio());
        $this->assertNull($end->getCep());
        $this->assertSame('049', $end->getCodigoPais());
        $this->assertSame('10001', $end->getCodigoPostalExterior());
        $this->assertSame('New York', $end->getNomeCidadeExterior());
        $this->assertSame('NY', $end->getEstadoProvinciaExterior());
        $this->assertTrue($end->isExterior());
    }

    public function test_create_nacional_sem_cmun_throws(): void
    {
        // Endereço nacional exige cMun e CEP — a lib não presume '0000000'/'00000000'.
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Endereço nacional exige');

        new Endereco(
            logradouro: 'Rua Exemplo',
            numero: '123',
            complemento: null,
            bairro: 'Centro',
            codigoMunicipio: null,
            cep: new Cep('01001001'),
        );
    }

    public function test_create_with_complemento(): void
    {
        $end = new Endereco(
            logradouro: 'Av. Paulista',
            numero: '1000',
            complemento: 'Apto 42',
            bairro: 'Bela Vista',
            codigoMunicipio: new CodigoMunicipio('3550308'),
            cep: new Cep('01310000'),
        );

        $this->assertSame('Apto 42', $end->getComplemento());
    }

    public function test_create_missing_logradouro_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Logradouro é obrigatório');

        new Endereco(
            logradouro: '',
            numero: '123',
            complemento: null,
            bairro: 'Centro',
            codigoMunicipio: new CodigoMunicipio('3550308'),
            cep: new Cep('01001001'),
        );
    }

    public function test_create_missing_bairro_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Bairro é obrigatório');

        new Endereco(
            logradouro: 'Rua Exemplo',
            numero: '123',
            complemento: null,
            bairro: '',
            codigoMunicipio: new CodigoMunicipio('3550308'),
            cep: new Cep('01001001'),
        );
    }

    public function test_getters_return_correct_types(): void
    {
        $end = new Endereco(
            logradouro: 'Rua A',
            numero: '1',
            complemento: null,
            bairro: 'Bairro X',
            codigoMunicipio: new CodigoMunicipio('3550308'),
            cep: new Cep('01001001'),
        );

        $this->assertIsString($end->getLogradouro());
        $this->assertIsString($end->getNumero());
        $this->assertIsString($end->getBairro());
        $this->assertInstanceOf(CodigoMunicipio::class, $end->getCodigoMunicipio());
        $this->assertInstanceOf(Cep::class, $end->getCep());
    }
}
