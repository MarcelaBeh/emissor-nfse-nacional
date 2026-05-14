<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsEnderecoObra;
use PHPUnit\Framework\TestCase;

final class IbsCbsEnderecoObraTest extends TestCase
{
    public function test_create(): void
    {
        $end = new IbsCbsEnderecoObra(
            cep: '01001001',
            xLgr: 'Rua Exemplo',
            nro: '123',
            xCpl: 'Bloco A',
            xBairro: 'Centro',
        );

        $this->assertSame('01001001', $end->getCep());
        $this->assertSame('Rua Exemplo', $end->getXLgr());
        $this->assertSame('123', $end->getNro());
        $this->assertSame('Bloco A', $end->getXCpl());
        $this->assertSame('Centro', $end->getXBairro());
        $this->assertNull($end->getEndExt());
    }

    public function test_create_optional_fields(): void
    {
        $end = new IbsCbsEnderecoObra(
            xLgr: 'Rua',
            nro: '1',
            xBairro: 'Bairro',
        );

        $this->assertNull($end->getCep());
        $this->assertNull($end->getXCpl());
        $this->assertNull($end->getEndExt());
    }
}
