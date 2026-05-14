<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Infrastructure\Repository;

use MarcelaBeh\EmissorNfseNacional\Infrastructure\Repository\FileCstClassTribRepository;
use PHPUnit\Framework\TestCase;

final class FileCstClassTribRepositoryTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'cstclasstrib_test_');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function test_load_from_json_file(): void
    {
        $json = json_encode([
            [
                'cClassTrib' => '000001',
                'cst' => '000',
                'descricao' => 'Tributação integral',
                'validoParaNfse' => true,
                'permiteDiferimento' => false,
                'exigeGrupoTributacaoRegular' => false,
            ],
            [
                'cClassTrib' => '510001',
                'cst' => '510',
                'descricao' => 'Diferimento',
                'validoParaNfse' => true,
                'permiteDiferimento' => true,
                'exigeGrupoTributacaoRegular' => false,
                'pRedIBS' => 100.0,
                'pRedCBS' => 100.0,
            ],
        ]);

        file_put_contents($this->tempFile, $json);

        $repo = new FileCstClassTribRepository($this->tempFile);

        $props = $repo->findByCode('000001');
        $this->assertNotNull($props);
        $this->assertSame('000001', $props->getCClassTrib());
        $this->assertFalse($props->isPermiteDiferimento());

        $props2 = $repo->findByCode('510001');
        $this->assertNotNull($props2);
        $this->assertTrue($props2->isPermiteDiferimento());
        $this->assertTrue($props2->hasReducaoIBS());

        $this->assertNull($repo->findByCode('999999'));
    }

    public function test_find_by_cst_from_json(): void
    {
        $json = json_encode([
            [
                'cClassTrib' => '200001',
                'cst' => '200',
                'descricao' => 'Alíquota zero',
                'validoParaNfse' => true,
                'permiteDiferimento' => false,
                'exigeGrupoTributacaoRegular' => false,
                'pRedIBS' => 100.0,
            ],
            [
                'cClassTrib' => '200002',
                'cst' => '200',
                'descricao' => 'Redução 60%',
                'validoParaNfse' => true,
                'permiteDiferimento' => false,
                'exigeGrupoTributacaoRegular' => false,
                'pRedIBS' => 60.0,
            ],
        ]);

        file_put_contents($this->tempFile, $json);

        $repo = new FileCstClassTribRepository($this->tempFile);

        $results = $repo->findByCst('200');
        $this->assertCount(2, $results);

        $results = $repo->findByCst('999');
        $this->assertCount(0, $results);
    }

    public function test_missing_file_returns_null(): void
    {
        $repo = new FileCstClassTribRepository('/tmp/nonexistent_file_12345.json');
        $this->assertNull($repo->findByCode('000001'));
    }

    public function test_invalid_json_returns_null(): void
    {
        file_put_contents($this->tempFile, 'not json');
        $repo = new FileCstClassTribRepository($this->tempFile);
        $this->assertNull($repo->findByCode('000001'));
    }

    public function test_caches_after_first_load(): void
    {
        $json = json_encode([
            [
                'cClassTrib' => '000001',
                'cst' => '000',
                'descricao' => 'Test',
                'validoParaNfse' => true,
                'permiteDiferimento' => false,
                'exigeGrupoTributacaoRegular' => false,
            ],
        ]);

        file_put_contents($this->tempFile, $json);

        $repo = new FileCstClassTribRepository($this->tempFile);

        $this->assertNotNull($repo->findByCode('000001'));

        file_put_contents($this->tempFile, '[]');

        $this->assertNotNull($repo->findByCode('000001'), 'should use cache');
    }
}
