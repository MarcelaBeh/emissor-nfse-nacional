<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Enum;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\MotivoEvento;
use PHPUnit\Framework\TestCase;

final class MotivoEventoTest extends TestCase
{
    public function test_all_cases(): void
    {
        $this->assertSame('01', MotivoEvento::ERRO_EMISSAO->value);
        $this->assertSame('02', MotivoEvento::SERVICO_NAO_PRESTADO->value);
        $this->assertSame('03', MotivoEvento::DESENQUADRAMENTO_SIMPLES->value);
        $this->assertSame('04', MotivoEvento::ENQUADRAMENTO_SIMPLES->value);
        $this->assertSame('05', MotivoEvento::INCLUSAO_IMUNIDADE->value);
        $this->assertSame('06', MotivoEvento::EXCLUSAO_IMUNIDADE->value);
        $this->assertSame('07', MotivoEvento::REJEICAO_TOMADOR->value);
        $this->assertSame('99', MotivoEvento::OUTROS->value);
    }

    public function test_descricao(): void
    {
        $this->assertStringContainsString('Erro', MotivoEvento::ERRO_EMISSAO->descricao());
        $this->assertStringContainsString('Simples', MotivoEvento::DESENQUADRAMENTO_SIMPLES->descricao());
        $this->assertStringContainsString('Outros', MotivoEvento::OUTROS->descricao());
    }
}
