<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Infrastructure\Config;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoAmbiente;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Config\Exception\ConfigException;

class Configuration implements Contract\ConfigInterface
{
    /** @var array<string, mixed> */
    private array $config;
    /** @var array<string, string> */
    private array $urls;
    /** @var array<string, string> */
    private array $operations;
    private string $tipoApi;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->config = $this->validateConfig($config);
        $this->tipoApi = $config['tipoApi'] ?? 'sefin';
        $this->loadUrls();
        $this->loadOperations();
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function validateConfig(array $config): array
    {
        $required = ['tpAmb', 'prefeitura'];

        foreach ($required as $field) {
            if (!isset($config[$field])) {
                throw new ConfigException("Campo obrigatório não informado: {$field}");
            }
        }

        if (!in_array($config['tpAmb'], [1, 2])) {
            throw new ConfigException('tpAmb deve ser 1 (Produção) ou 2 (Homologação)');
        }

        return $config;
    }

    private function loadUrls(): void
    {
        $this->urls = [
            'sefin_homologacao' => 'https://sefin.producaorestrita.nfse.gov.br/SefinNacional',
            'sefin_producao' => 'https://sefin.nfse.gov.br/sefinnacional',
            'adn_homologacao' => 'https://adn.producaorestrita.nfse.gov.br',
            'adn_producao' => 'https://adn.nfse.gov.br',
        ];

        $configFile = __DIR__ . '/../../../storage/prefeituras.json';
        if (file_exists($configFile)) {
            $content = file_get_contents($configFile);
            if ($content === false || !json_validate($content)) {
                throw new ConfigException("Arquivo de configuração inválido: {$configFile}");
            }
            $json = json_decode($content, true);
            $prefeitura = $this->config['prefeitura'];

            if (isset($json[$prefeitura]['urls'])) {
                $this->urls = array_merge($this->urls, $json[$prefeitura]['urls']);
            }
        }
    }

    private function loadOperations(): void
    {
        $this->operations = [
            'consultar_nfse' => 'nfse/{chave}',
            'consultar_dps' => 'dps/{chave}',
            'consultar_eventos' => 'nfse/{chave}/eventos/{tipoEvento}/{nSequencial}',
            'emitir_nfse' => 'nfse',
            'cancelar_nfse' => 'nfse/{chave}/eventos',
        ];

        $configFile = __DIR__ . '/../../../storage/prefeituras.json';
        if (file_exists($configFile)) {
            $content = file_get_contents($configFile);
            if ($content === false || !json_validate($content)) {
                throw new ConfigException("Arquivo de configuração inválido: {$configFile}");
            }
            $json = json_decode($content, true);
            $prefeitura = $this->config['prefeitura'];

            if (isset($json[$prefeitura]['operations'])) {
                $this->operations = array_merge($this->operations, $json[$prefeitura]['operations']);
            }
        }
    }

    #[\Override]
    public function getTipoAmbiente(): TipoAmbiente
    {
        return TipoAmbiente::from($this->config['tpAmb']);
    }

    #[\Override]
    public function getTipoApi(): string
    {
        return $this->tipoApi;
    }

    #[\Override]
    public function getUrl(string $key): string
    {
        if (!isset($this->urls[$key])) {
            throw new ConfigException("URL não configurada: {$key}");
        }

        return $this->urls[$key];
    }

    #[\Override]
    public function getOperation(string $key): string
    {
        if (!isset($this->operations[$key])) {
            throw new ConfigException("Operação não configurada: {$key}");
        }

        return $this->operations[$key];
    }

    #[\Override]
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    #[\Override]
    public function getVersion(): string
    {
        return '2.0.0';
    }
}
