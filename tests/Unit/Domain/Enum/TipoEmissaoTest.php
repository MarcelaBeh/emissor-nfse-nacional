<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Enum;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEmissao;
use PHPUnit\Framework\TestCase;

final class TipoEmissaoTest extends TestCase
{
    public function test_prestador(): void
    {
        $this->assertSame(1, TipoEmissao::PRESTADOR->value);
        $this->assertSame('Emitente: Prestador', TipoEmissao::PRESTADOR->descricao());
    }

    public function test_tomador(): void
    {
        $this->assertSame(2, TipoEmissao::TOMADOR->value);
        $this->assertSame('Emitente: Tomador', TipoEmissao::TOMADOR->descricao());
    }

    public function test_intermediario(): void
    {
        $this->assertSame(3, TipoEmissao::INTERMEDIARIO->value);
        $this->assertSame('Emitente: Intermediário', TipoEmissao::INTERMEDIARIO->descricao());
    }
}
