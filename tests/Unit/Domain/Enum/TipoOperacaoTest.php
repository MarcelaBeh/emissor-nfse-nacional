<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Enum;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoOperacao;
use PHPUnit\Framework\TestCase;

final class TipoOperacaoTest extends TestCase
{
    public function test_all_cases(): void
    {
        $this->assertSame('1', TipoOperacao::FORNECIMENTO_POSTERIOR->value);
        $this->assertSame('2', TipoOperacao::RECEBIMENTO_FORNECIMENTO_REALIZADO->value);
        $this->assertSame('3', TipoOperacao::FORNECIMENTO_PAGAMENTO_REALIZADO->value);
        $this->assertSame('4', TipoOperacao::RECEBIMENTO_FORNECIMENTO_POSTERIOR->value);
        $this->assertSame('5', TipoOperacao::CONCOMITANTE->value);
    }

    public function test_descricao(): void
    {
        $this->assertStringContainsString('pagamento posterior', TipoOperacao::FORNECIMENTO_POSTERIOR->descricao());
        $this->assertStringContainsString('concomitantes', TipoOperacao::CONCOMITANTE->descricao());
    }
}
