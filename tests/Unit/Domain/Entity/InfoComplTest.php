<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\InfoCompl;
use PHPUnit\Framework\TestCase;

final class InfoComplTest extends TestCase
{
    public function test_create_empty(): void
    {
        $ic = new InfoCompl();

        $this->assertNull($ic->getIdDocTecnico());
        $this->assertNull($ic->getDocReferencia());
        $this->assertNull($ic->getNumeroPedido());
        $this->assertNull($ic->getItensPedido());
        $this->assertNull($ic->getInfoComplementar());
    }

    public function test_create_with_all_fields(): void
    {
        $ic = new InfoCompl(
            idDocTecnico: 'CONTR-2026-001',
            docReferencia: 'PROP-2026-001',
            numeroPedido: 'PED-2026-001',
            itensPedido: ['Item 1', 'Item 2'],
            infoComplementar: 'Serviço prestado parcialmente no exterior',
        );

        $this->assertSame('CONTR-2026-001', $ic->getIdDocTecnico());
        $this->assertSame('PROP-2026-001', $ic->getDocReferencia());
        $this->assertSame('PED-2026-001', $ic->getNumeroPedido());
        $this->assertSame(['Item 1', 'Item 2'], $ic->getItensPedido());
        $this->assertSame('Serviço prestado parcialmente no exterior', $ic->getInfoComplementar());
    }

    public function test_create_with_some_fields(): void
    {
        $ic = new InfoCompl(
            numeroPedido: 'PED-002',
            infoComplementar: 'Sem contrato',
        );

        $this->assertNull($ic->getIdDocTecnico());
        $this->assertNull($ic->getDocReferencia());
        $this->assertSame('PED-002', $ic->getNumeroPedido());
        $this->assertNull($ic->getItensPedido());
        $this->assertSame('Sem contrato', $ic->getInfoComplementar());
    }

    public function test_create_with_empty_itens_pedido(): void
    {
        $ic = new InfoCompl(
            itensPedido: [],
        );

        $this->assertSame([], $ic->getItensPedido());
    }
}
