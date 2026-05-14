<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsDiferimento;
use PHPUnit\Framework\TestCase;

final class IbsCbsDiferimentoTest extends TestCase
{
    public function test_create(): void
    {
        $entity = new IbsCbsDiferimento(
            pDifUF: 10.5,
            pDifMun: 5.2,
            pDifCBS: 8.7,
        );

        $this->assertSame(10.5, $entity->getPDifUF());
        $this->assertSame(5.2, $entity->getPDifMun());
        $this->assertSame(8.7, $entity->getPDifCBS());
    }

    public function test_create_with_zero_values(): void
    {
        $entity = new IbsCbsDiferimento(
            pDifUF: 0.0,
            pDifMun: 0.0,
            pDifCBS: 0.0,
        );

        $this->assertSame(0.0, $entity->getPDifUF());
        $this->assertSame(0.0, $entity->getPDifMun());
        $this->assertSame(0.0, $entity->getPDifCBS());
    }

    public function test_create_with_high_values(): void
    {
        $entity = new IbsCbsDiferimento(
            pDifUF: 100.0,
            pDifMun: 50.0,
            pDifCBS: 99.99,
        );

        $this->assertSame(100.0, $entity->getPDifUF());
        $this->assertSame(50.0, $entity->getPDifMun());
        $this->assertSame(99.99, $entity->getPDifCBS());
    }
}
