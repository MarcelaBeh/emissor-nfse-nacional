<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Enum;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\MotivoSubstituicao;
use PHPUnit\Framework\TestCase;

final class MotivoSubstituicaoTest extends TestCase
{
    public function test_all_cases(): void
    {
        $this->assertSame('01', MotivoSubstituicao::DESENQUADRAMENTO_SIMPLES->value);
        $this->assertSame('02', MotivoSubstituicao::ENQUADRAMENTO_SIMPLES->value);
        $this->assertSame('03', MotivoSubstituicao::INCLUSAO_IMUNIDADE->value);
        $this->assertSame('04', MotivoSubstituicao::EXCLUSAO_IMUNIDADE->value);
        $this->assertSame('05', MotivoSubstituicao::REJEICAO_TOMADOR->value);
        $this->assertSame('99', MotivoSubstituicao::OUTROS->value);
    }

    public function test_descricao(): void
    {
        $this->assertStringContainsString('Desenquadramento', MotivoSubstituicao::DESENQUADRAMENTO_SIMPLES->descricao());
        $this->assertStringContainsString('Outros', MotivoSubstituicao::OUTROS->descricao());
    }

    public function test_valores(): void
    {
        $this->assertSame(['01', '02', '03', '04', '05', '99'], MotivoSubstituicao::valores());
    }
}
