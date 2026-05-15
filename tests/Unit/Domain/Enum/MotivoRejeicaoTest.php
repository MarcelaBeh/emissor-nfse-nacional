<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Enum;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\MotivoRejeicao;
use PHPUnit\Framework\TestCase;

final class MotivoRejeicaoTest extends TestCase
{
    public function test_all_cases(): void
    {
        $this->assertSame('1', MotivoRejeicao::DUPLICIDADE->value);
        $this->assertSame('2', MotivoRejeicao::EMITIDA_TOMADOR->value);
        $this->assertSame('3', MotivoRejeicao::NAO_OCORRENCIA_FATO->value);
        $this->assertSame('4', MotivoRejeicao::ERRO_RESPONSABILIDADE->value);
        $this->assertSame('5', MotivoRejeicao::ERRO_VALOR->value);
        $this->assertSame('9', MotivoRejeicao::OUTROS->value);
    }

    public function test_descricao(): void
    {
        $this->assertStringContainsString('duplicidade', MotivoRejeicao::DUPLICIDADE->descricao());
        $this->assertStringContainsString('Outros', MotivoRejeicao::OUTROS->descricao());
    }

    public function test_valores(): void
    {
        $this->assertSame(['1', '2', '3', '4', '5', '9'], MotivoRejeicao::valores());
    }
}
