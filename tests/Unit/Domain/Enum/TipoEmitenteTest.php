<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Enum;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEmitente;
use PHPUnit\Framework\TestCase;

final class TipoEmitenteTest extends TestCase
{
    public function test_prestador(): void
    {
        $this->assertSame(1, TipoEmitente::PRESTADOR->value);
        $this->assertSame('Emitente: Prestador', TipoEmitente::PRESTADOR->descricao());
    }

    public function test_tomador(): void
    {
        $this->assertSame(2, TipoEmitente::TOMADOR->value);
        $this->assertSame('Emitente: Tomador', TipoEmitente::TOMADOR->descricao());
    }

    public function test_intermediario(): void
    {
        $this->assertSame(3, TipoEmitente::INTERMEDIARIO->value);
        $this->assertSame('Emitente: Intermediário', TipoEmitente::INTERMEDIARIO->descricao());
    }
}
