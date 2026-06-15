<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Endereco;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsDest;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cep;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cnpj;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoMunicipio;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cpf;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Nif;
use PHPUnit\Framework\TestCase;

final class IbsCbsDestTest extends TestCase
{
    public function test_create_with_xnome_only(): void
    {
        $dest = new IbsCbsDest(xNome: 'Destinatário Teste');

        $this->assertSame('Destinatário Teste', $dest->getXNome());
        $this->assertNull($dest->getCnpj());
        $this->assertNull($dest->getCpf());
        $this->assertNull($dest->getNif());
        $this->assertNull($dest->getCodigoNaoNif());
        $this->assertNull($dest->getEndereco());
        $this->assertNull($dest->getFone());
        $this->assertNull($dest->getEmail());
    }

    public function test_create_with_cnpj(): void
    {
        $cnpj = new Cnpj('11444777000161');
        $dest = new IbsCbsDest(cnpj: $cnpj, xNome: 'Empresa Ltda');

        $this->assertSame($cnpj, $dest->getCnpj());
        $this->assertNull($dest->getCpf());
        $this->assertSame('Empresa Ltda', $dest->getXNome());
    }

    public function test_create_with_cpf(): void
    {
        $cpf = new Cpf('52998224725');
        $dest = new IbsCbsDest(cpf: $cpf, xNome: 'Fulano Silva');

        $this->assertSame($cpf, $dest->getCpf());
        $this->assertNull($dest->getCnpj());
    }

    public function test_create_with_nif(): void
    {
        $nif = new Nif('123456789');
        $dest = new IbsCbsDest(nif: $nif, xNome: 'Estrangeiro SA');

        $this->assertSame($nif, $dest->getNif());
    }

    public function test_create_with_codigo_nao_nif(): void
    {
        $dest = new IbsCbsDest(codigoNaoNif: '9999999', xNome: 'Sem NIF');

        $this->assertSame('9999999', $dest->getCodigoNaoNif());
    }

    public function test_create_with_endereco(): void
    {
        $endereco = new Endereco(
            logradouro: 'Rua Teste',
            numero: '123',
            complemento: null,
            bairro: 'Centro',
            codigoMunicipio: new CodigoMunicipio('3550308'),
            cep: new Cep('01001001'),
        );
        $dest = new IbsCbsDest(xNome: 'Com Endereço', endereco: $endereco);

        $this->assertSame($endereco, $dest->getEndereco());
        $this->assertSame('Rua Teste', $dest->getEndereco()->getLogradouro());
    }

    public function test_create_with_fone_email(): void
    {
        $dest = new IbsCbsDest(
            xNome: 'Contato Teste',
            fone: '11999999999',
            email: 'teste@teste.com',
        );

        $this->assertSame('11999999999', $dest->getFone());
        $this->assertSame('teste@teste.com', $dest->getEmail());
    }

    public function test_create_with_all_fields(): void
    {
        $cnpj = new Cnpj('11444777000161');
        $nif = new Nif('123456789');
        $endereco = new Endereco(
            logradouro: 'Rua Exemplo',
            numero: '456',
            complemento: 'Apto 10',
            bairro: 'Jardim',
            codigoMunicipio: new CodigoMunicipio('3550308'),
            cep: new Cep('01001001'),
        );

        $dest = new IbsCbsDest(
            cnpj: $cnpj,
            nif: $nif,
            codigoNaoNif: null,
            xNome: 'Destinatário Completo',
            endereco: $endereco,
            fone: '11988888888',
            email: 'contato@dest.com',
        );

        $this->assertSame($cnpj, $dest->getCnpj());
        $this->assertNull($dest->getCpf());
        $this->assertSame($nif, $dest->getNif());
        $this->assertNull($dest->getCodigoNaoNif());
        $this->assertSame('Destinatário Completo', $dest->getXNome());
        $this->assertSame($endereco, $dest->getEndereco());
        $this->assertSame('11988888888', $dest->getFone());
        $this->assertSame('contato@dest.com', $dest->getEmail());
    }

    public function test_xnome_vazio_throws(): void
    {
        // xNome é obrigatório (TCRTCInfoDest, minOccurs=1) — a lib não presume nome vazio.
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('xNome do destinatário é obrigatório');

        new IbsCbsDest(
            cnpj: new Cnpj('11444777000161'),
            xNome: '',
        );
    }
}
