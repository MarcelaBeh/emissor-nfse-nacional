<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\ComExterior;
use PHPUnit\Framework\TestCase;

final class ComExteriorTest extends TestCase
{
    public function test_create(): void
    {
        $ce = new ComExterior(
            modoPrestacao: 1,
            vinculoPrestador: 2,
            codigoMoeda: 'USD',
            valorServicoMoeda: 5000.00,
            mecanismoApoioPrestador: '1',
            mecanismoApoioTomador: '1',
            movimentacaoTemporaria: 'N',
            enviarMDIC: 'N',
        );

        $this->assertSame(1, $ce->getModoPrestacao());
        $this->assertSame(2, $ce->getVinculoPrestador());
        $this->assertSame('USD', $ce->getCodigoMoeda());
        $this->assertSame(5000.00, $ce->getValorServicoMoeda());
        $this->assertSame('1', $ce->getMecanismoApoioPrestador());
        $this->assertSame('1', $ce->getMecanismoApoioTomador());
        $this->assertSame('N', $ce->getMovimentacaoTemporaria());
        $this->assertSame('N', $ce->getEnviarMDIC());
        $this->assertNull($ce->getNumeroDeclaracaoImportacao());
        $this->assertNull($ce->getNumeroRegistroExportacao());
    }

    public function test_create_with_optional_fields(): void
    {
        $ce = new ComExterior(
            modoPrestacao: 5,
            vinculoPrestador: 3,
            codigoMoeda: 'EUR',
            valorServicoMoeda: 3000.00,
            mecanismoApoioPrestador: '2',
            mecanismoApoioTomador: '2',
            movimentacaoTemporaria: 'S',
            enviarMDIC: 'S',
            numeroDeclaracaoImportacao: '25DI1234567',
            numeroRegistroExportacao: '25RE7654321',
        );

        $this->assertSame(5, $ce->getModoPrestacao());
        $this->assertSame('EUR', $ce->getCodigoMoeda());
        $this->assertSame('25DI1234567', $ce->getNumeroDeclaracaoImportacao());
        $this->assertSame('25RE7654321', $ce->getNumeroRegistroExportacao());
    }

    public function test_create_with_zero_valor_servico_moeda(): void
    {
        $ce = new ComExterior(
            modoPrestacao: 1,
            vinculoPrestador: 1,
            codigoMoeda: 'BRL',
            valorServicoMoeda: 0.0,
            mecanismoApoioPrestador: '1',
            mecanismoApoioTomador: '1',
            movimentacaoTemporaria: 'N',
            enviarMDIC: 'N',
        );

        $this->assertSame(0.0, $ce->getValorServicoMoeda());
    }
}
