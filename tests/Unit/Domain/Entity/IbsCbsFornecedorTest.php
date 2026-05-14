<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsFornecedor;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cnpj;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cpf;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Nif;
use PHPUnit\Framework\TestCase;

final class IbsCbsFornecedorTest extends TestCase
{
    public function test_create_with_cnpj(): void
    {
        $f = new IbsCbsFornecedor(
            cnpj: new Cnpj('11444777000161'),
            xNome: 'Fornecedor Ltda',
        );

        $this->assertSame('11444777000161', $f->getCnpj()->getNumero());
        $this->assertNull($f->getCpf());
        $this->assertSame('Fornecedor Ltda', $f->getXNome());
    }

    public function test_create_with_cpf(): void
    {
        $f = new IbsCbsFornecedor(
            cpf: new Cpf('52998224725'),
            xNome: 'Fulano',
        );

        $this->assertSame('52998224725', $f->getCpf()->getNumero());
        $this->assertNull($f->getCnpj());
    }

    public function test_create_with_nif(): void
    {
        $f = new IbsCbsFornecedor(
            nif: new Nif('123456789'),
            xNome: 'Fornecedor Exterior',
        );

        $this->assertSame('123456789', $f->getNif()->getNif());
    }

    public function test_create_with_codigo_nao_nif(): void
    {
        $f = new IbsCbsFornecedor(
            codigoNaoNif: '0',
            xNome: 'Sem NIF',
        );

        $this->assertSame('0', $f->getCodigoNaoNif());
    }
}
