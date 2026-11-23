<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Infrastructure\Config;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoAmbiente;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Config\Configuration;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Config\Exception\ConfigException;
use MarcelaBeh\EmissorNfseNacional\Presentation\Factory\ConfigFactory;
use PHPUnit\Framework\TestCase;

final class ConfigurationTest extends TestCase
{
    public function test_so_o_tpamb_e_obrigatorio(): void
    {
        $config = new Configuration(['tpAmb' => 2]);

        $this->assertSame(TipoAmbiente::HOMOLOGACAO, $config->getTipoAmbiente());
        $this->assertSame('sefin', $config->getTipoApi());
    }

    public function test_sem_tpamb_e_recusado(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('tpAmb');

        new Configuration([]);
    }

    public function test_tpamb_invalido_e_recusado(): void
    {
        $this->expectException(ConfigException::class);

        new Configuration(['tpAmb' => 3]);
    }

    public function test_prefeitura_antigo_na_configuracao_e_ignorado(): void
    {
        // Quem ainda passa o parametro removido continua funcionando e usa a SEFIN Nacional.
        $antigo = new Configuration(['tpAmb' => 2, 'prefeitura' => '3501608']);
        $novo = new Configuration(['tpAmb' => 2]);

        foreach (['sefin_homologacao', 'sefin_producao', 'adn_homologacao', 'adn_producao'] as $chave) {
            $this->assertSame($novo->getUrl($chave), $antigo->getUrl($chave));
        }
        $this->assertSame($novo->getOperation('emitir_nfse'), $antigo->getOperation('emitir_nfse'));
    }

    public function test_urls_padrao_da_sefin_nacional(): void
    {
        $config = new Configuration(['tpAmb' => 2]);

        $this->assertSame('https://sefin.producaorestrita.nfse.gov.br/SefinNacional', $config->getUrl('sefin_homologacao'));
        $this->assertSame('https://sefin.nfse.gov.br/sefinnacional', $config->getUrl('sefin_producao'));
        $this->assertSame('nfse', $config->getOperation('emitir_nfse'));
        $this->assertSame('nfse/{chave}/eventos', $config->getOperation('cancelar_nfse'));
    }

    public function test_url_inexistente_lanca_excecao(): void
    {
        $this->expectException(ConfigException::class);

        (new Configuration(['tpAmb' => 2]))->getUrl('inexistente');
    }

    public function test_factory_cria_homologacao_e_producao_sem_municipio(): void
    {
        $this->assertSame(TipoAmbiente::HOMOLOGACAO, ConfigFactory::createHomologacao()->getTipoAmbiente());
        $this->assertSame(TipoAmbiente::PRODUCAO, ConfigFactory::createProducao()->getTipoAmbiente());
        $this->assertSame(TipoAmbiente::HOMOLOGACAO, ConfigFactory::createDefault()->getTipoAmbiente());
    }
}
