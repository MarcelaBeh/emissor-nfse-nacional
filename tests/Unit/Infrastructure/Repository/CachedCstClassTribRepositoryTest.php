<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Infrastructure\Repository;

use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CstClassTribProperties;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Repository\CachedCstClassTribRepository;
use PHPUnit\Framework\TestCase;

final class CachedCstClassTribRepositoryTest extends TestCase
{
    public function test_delegates_and_caches(): void
    {
        $inner = $this->createMock(\MarcelaBeh\EmissorNfseNacional\Domain\Contract\CstClassTribRepository::class);

        $props = new CstClassTribProperties(
            cClassTrib: '000001',
            cst: '000',
            descricao: 'Test',
            validoParaNfse: true,
            permiteDiferimento: false,
            exigeGrupoTributacaoRegular: false,
        );

        $inner->expects($this->once())
            ->method('findByCode')
            ->with('000001')
            ->willReturn($props);

        $cached = new CachedCstClassTribRepository($inner);

        $this->assertSame($props, $cached->findByCode('000001'));
        $this->assertSame($props, $cached->findByCode('000001'));
    }

    public function test_delegates_and_caches_find_by_cst(): void
    {
        $inner = $this->createMock(\MarcelaBeh\EmissorNfseNacional\Domain\Contract\CstClassTribRepository::class);

        $props = new CstClassTribProperties(
            cClassTrib: '000001',
            cst: '000',
            descricao: 'Test',
            validoParaNfse: true,
            permiteDiferimento: false,
            exigeGrupoTributacaoRegular: false,
        );

        $inner->expects($this->once())
            ->method('findByCst')
            ->with('000')
            ->willReturn([$props]);

        $cached = new CachedCstClassTribRepository($inner);

        $this->assertSame([$props], $cached->findByCst('000'));
        $this->assertSame([$props], $cached->findByCst('000'));
    }

    public function test_cache_null_result(): void
    {
        $inner = $this->createMock(\MarcelaBeh\EmissorNfseNacional\Domain\Contract\CstClassTribRepository::class);

        $inner->expects($this->once())
            ->method('findByCode')
            ->with('999999')
            ->willReturn(null);

        $cached = new CachedCstClassTribRepository($inner);

        $this->assertNull($cached->findByCode('999999'));
        $this->assertNull($cached->findByCode('999999'));
    }
}
