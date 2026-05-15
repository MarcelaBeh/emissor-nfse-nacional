<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Enum;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEnteGovernamental;
use PHPUnit\Framework\TestCase;

final class TipoEnteGovernamentalTest extends TestCase
{
    public function test_all_cases(): void
    {
        $this->assertSame('1', TipoEnteGovernamental::UNIAO->value);
        $this->assertSame('2', TipoEnteGovernamental::ESTADO->value);
        $this->assertSame('3', TipoEnteGovernamental::DISTRITO_FEDERAL->value);
        $this->assertSame('4', TipoEnteGovernamental::MUNICIPIO->value);
    }

    public function test_descricao(): void
    {
        $this->assertSame('União', TipoEnteGovernamental::UNIAO->descricao());
        $this->assertSame('Estado', TipoEnteGovernamental::ESTADO->descricao());
        $this->assertSame('Distrito Federal', TipoEnteGovernamental::DISTRITO_FEDERAL->descricao());
        $this->assertSame('Município', TipoEnteGovernamental::MUNICIPIO->descricao());
    }
}
