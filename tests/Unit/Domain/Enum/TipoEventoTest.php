<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Enum;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEvento;
use PHPUnit\Framework\TestCase;

final class TipoEventoTest extends TestCase
{
    public function test_cancelamento(): void
    {
        $this->assertSame('101101', TipoEvento::CANCELAMENTO->value);
        $this->assertSame('Cancelamento', TipoEvento::CANCELAMENTO->descricao());
        $this->assertSame('Cancelamento de NFS-e', TipoEvento::CANCELAMENTO->xDesc());
        $this->assertSame('e101101', TipoEvento::CANCELAMENTO->eventTypeTag());
        $this->assertFalse(TipoEvento::CANCELAMENTO->needsChSubstituta());
        $this->assertTrue(TipoEvento::CANCELAMENTO->hasMotivo());
    }

    public function test_substituicao(): void
    {
        $this->assertSame('105102', TipoEvento::SUBSTITUICAO->value);
        $this->assertTrue(TipoEvento::SUBSTITUICAO->needsChSubstituta());
        $this->assertTrue(TipoEvento::SUBSTITUICAO->hasMotivo());
    }

    public function test_cancelamento_deferido(): void
    {
        $this->assertTrue(TipoEvento::CANCELAMENTO_DEFERIDO->needsCpfAgTrib());
        // nProcAdm é minOccurs=0 no XSD para deferido/indeferido (obrigatório só no cancelamento por ofício).
        $this->assertFalse(TipoEvento::CANCELAMENTO_DEFERIDO->needsNumeroProcesso());
    }

    public function test_cancelamento_oficio(): void
    {
        $this->assertTrue(TipoEvento::CANCELAMENTO_OFICIO->needsCpfAgTrib());
        $this->assertTrue(TipoEvento::CANCELAMENTO_OFICIO->needsNumeroProcesso());
    }

    public function test_confirmitacao_tacita(): void
    {
        $this->assertFalse(TipoEvento::CONFIRMACAO_TACITA->hasMotivo());
        $this->assertFalse(TipoEvento::CONFIRMACAO_TACITA->needsChSubstituta());
    }
}
