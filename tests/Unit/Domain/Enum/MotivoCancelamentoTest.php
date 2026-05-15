<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Enum;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\MotivoCancelamento;
use PHPUnit\Framework\TestCase;

final class MotivoCancelamentoTest extends TestCase
{
    public function test_erro_emissao(): void
    {
        $this->assertSame('1', MotivoCancelamento::ERRO_EMISSAO->value);
        $this->assertSame('Erro na Emissão', MotivoCancelamento::ERRO_EMISSAO->descricao());
    }

    public function test_servico_nao_prestado(): void
    {
        $this->assertSame('2', MotivoCancelamento::SERVICO_NAO_PRESTADO->value);
        $this->assertSame('Serviço não Prestado', MotivoCancelamento::SERVICO_NAO_PRESTADO->descricao());
    }

    public function test_outros(): void
    {
        $this->assertSame('9', MotivoCancelamento::OUTROS->value);
        $this->assertSame('Outros', MotivoCancelamento::OUTROS->descricao());
    }

    public function test_valores(): void
    {
        $this->assertSame(['1', '2', '9'], MotivoCancelamento::valores());
    }
}
