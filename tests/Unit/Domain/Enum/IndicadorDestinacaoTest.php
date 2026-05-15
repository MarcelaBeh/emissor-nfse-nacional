<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Enum;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\IndicadorDestinacao;
use PHPUnit\Framework\TestCase;

final class IndicadorDestinacaoTest extends TestCase
{
    public function test_tomador(): void
    {
        $this->assertSame('0', IndicadorDestinacao::TOMADOR->value);
        $this->assertSame('Destinatário é o próprio tomador', IndicadorDestinacao::TOMADOR->descricao());
    }

    public function test_terceiro(): void
    {
        $this->assertSame('1', IndicadorDestinacao::TERCEIRO->value);
        $this->assertSame('Destinatário é terceiro (diferente do tomador)', IndicadorDestinacao::TERCEIRO->descricao());
    }
}
