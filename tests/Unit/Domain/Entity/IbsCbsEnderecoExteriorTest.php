<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsEnderecoExterior;
use PHPUnit\Framework\TestCase;

final class IbsCbsEnderecoExteriorTest extends TestCase
{
    public function test_create(): void
    {
        $end = new IbsCbsEnderecoExterior(
            cEndPost: 'EC1A1BB',
            xCidade: 'London',
            xEstProvReg: 'Greater London',
        );

        $this->assertSame('EC1A1BB', $end->getCEndPost());
        $this->assertSame('London', $end->getXCidade());
        $this->assertSame('Greater London', $end->getXEstProvReg());
    }
}
