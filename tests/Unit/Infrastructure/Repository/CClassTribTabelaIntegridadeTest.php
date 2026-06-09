<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Infrastructure\Repository;

use MarcelaBeh\EmissorNfseNacional\Infrastructure\Repository\FileCstClassTribRepository;
use PHPUnit\Framework\TestCase;

/**
 * Garante que a tabela oficial CST x cClassTrib (storage/cClassTrib.json) existe, é carregável
 * pelo caminho que a ServiceFactory usa, e contém dados. Sem isso, a validação de regras de
 * negócio do IBS/CBS (cClassTrib válido, diferimento, gTribRegular) fica inativa silenciosamente.
 */
final class CClassTribTabelaIntegridadeTest extends TestCase
{
    private const TABELA = __DIR__ . '/../../../../storage/cClassTrib.json';

    public function test_tabela_existe(): void
    {
        $this->assertFileExists(self::TABELA, 'A tabela cClassTrib.json deve existir para a validação do IBS/CBS funcionar');
    }

    public function test_tabela_carrega_e_tem_dados(): void
    {
        $repo = new FileCstClassTribRepository(self::TABELA);

        // Código tributado integralmente, presente na tabela oficial.
        $props = $repo->findByCode('000001');

        $this->assertNotNull($props, 'cClassTrib 000001 deve estar na tabela oficial');
        $this->assertTrue($props->isValidoParaNfse());
    }

    public function test_codigo_inexistente_retorna_null(): void
    {
        $repo = new FileCstClassTribRepository(self::TABELA);

        $this->assertNull($repo->findByCode('999999'));
    }
}
