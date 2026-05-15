<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Enum;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoAmbiente;
use PHPUnit\Framework\TestCase;

final class TipoAmbienteTest extends TestCase
{
    public function test_producao(): void
    {
        $this->assertSame(1, TipoAmbiente::PRODUCAO->value);
        $this->assertSame('Produção', TipoAmbiente::PRODUCAO->descricao());
        $this->assertTrue(TipoAmbiente::PRODUCAO->isProducao());
    }

    public function test_homologacao(): void
    {
        $this->assertSame(2, TipoAmbiente::HOMOLOGACAO->value);
        $this->assertSame('Homologação', TipoAmbiente::HOMOLOGACAO->descricao());
        $this->assertFalse(TipoAmbiente::HOMOLOGACAO->isProducao());
    }

    public function test_from(): void
    {
        $this->assertSame(TipoAmbiente::PRODUCAO, TipoAmbiente::from(1));
        $this->assertSame(TipoAmbiente::HOMOLOGACAO, TipoAmbiente::from(2));
    }
}
