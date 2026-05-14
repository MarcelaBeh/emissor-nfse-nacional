<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\ExigSusp;
use PHPUnit\Framework\TestCase;

final class ExigSuspTest extends TestCase
{
    public function test_create_empty(): void
    {
        $es = new ExigSusp();

        $this->assertNull($es->getTipoSuspensao());
        $this->assertNull($es->getNumeroProcesso());
    }

    public function test_create_with_all_fields(): void
    {
        $es = new ExigSusp(
            tipoSuspensao: 1,
            numeroProcesso: 'PROC-2026-12345',
        );

        $this->assertSame(1, $es->getTipoSuspensao());
        $this->assertSame('PROC-2026-12345', $es->getNumeroProcesso());
    }

    public function test_create_with_tipo_only(): void
    {
        $es = new ExigSusp(tipoSuspensao: 13);

        $this->assertSame(13, $es->getTipoSuspensao());
        $this->assertNull($es->getNumeroProcesso());
    }

    public function test_create_with_processo_only(): void
    {
        $es = new ExigSusp(numeroProcesso: 'PROC-SEM-TIPO');

        $this->assertNull($es->getTipoSuspensao());
        $this->assertSame('PROC-SEM-TIPO', $es->getNumeroProcesso());
    }
}
