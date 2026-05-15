<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Infrastructure\Repository;

use MarcelaBeh\EmissorNfseNacional\Infrastructure\Repository\InMemoryCstClassTribRepository;
use PHPUnit\Framework\TestCase;

final class InMemoryCstClassTribRepositoryTest extends TestCase
{
    private InMemoryCstClassTribRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new InMemoryCstClassTribRepository();
    }

    public function test_find_existing_code(): void
    {
        $props = $this->repository->findByCode('000001');

        $this->assertNotNull($props);
        $this->assertSame('000001', $props->getCClassTrib());
        $this->assertSame('000', $props->getCst());
        $this->assertTrue($props->isValidoParaNfse());
        $this->assertFalse($props->isPermiteDiferimento());
    }

    public function test_find_nonexistent_code_returns_null(): void
    {
        $this->assertNull($this->repository->findByCode('999999'));
    }

    public function test_find_by_cst(): void
    {
        $results = $this->repository->findByCst('200');

        $this->assertGreaterThanOrEqual(1, count($results));
        foreach ($results as $props) {
            $this->assertSame('200', $props->getCst());
        }
    }

    public function test_find_nonexistent_cst_returns_empty(): void
    {
        $this->assertSame([], $this->repository->findByCst('999'));
    }

    public function test_diferimento_code(): void
    {
        $props = $this->repository->findByCode('200027');

        $this->assertNotNull($props);
        $this->assertSame('200', $props->getCst());
    }

    public function test_tributacao_regular_code(): void
    {
        $props = $this->repository->findByCode('000001');

        $this->assertNotNull($props);
        $this->assertSame('000', $props->getCst());
        $this->assertTrue($props->isValidoParaNfse());
    }

    public function test_code_not_valid_for_nfse(): void
    {
        $props = $this->repository->findByCode('820001');

        $this->assertNotNull($props);
        $this->assertFalse($props->isValidoParaNfse());
    }

    public function test_custom_data(): void
    {
        $custom = [
            '111111' => new \MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CstClassTribProperties(
                cClassTrib: '111111',
                cst: '111',
                descricao: 'Custom',
                validoParaNfse: true,
                permiteDiferimento: false,
                exigeGrupoTributacaoRegular: false,
            ),
        ];

        $repo = new InMemoryCstClassTribRepository($custom);

        $this->assertNotNull($repo->findByCode('111111'));
        $this->assertNull($repo->findByCode('000001'));
    }
}
