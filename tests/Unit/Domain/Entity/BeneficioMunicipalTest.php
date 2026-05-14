<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\BeneficioMunicipal;
use PHPUnit\Framework\TestCase;

final class BeneficioMunicipalTest extends TestCase
{
    public function test_create_empty(): void
    {
        $bm = new BeneficioMunicipal();

        $this->assertNull($bm->getNumeroBeneficio());
    }

    public function test_create_with_numero(): void
    {
        $bm = new BeneficioMunicipal(numeroBeneficio: 'BM-2026-001');

        $this->assertSame('BM-2026-001', $bm->getNumeroBeneficio());
    }

    public function test_create_with_empty_numero(): void
    {
        $bm = new BeneficioMunicipal(numeroBeneficio: '');

        $this->assertSame('', $bm->getNumeroBeneficio());
    }
}
