<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Infrastructure\Config;

class MunicipioConfigLoader
{
    /** @var array<string, mixed> */
    private array $configs;

    public function __construct(?string $jsonPath = null)
    {
        $jsonPath ??= __DIR__ . '/../../../storage/prefeituras.json';
        $this->load($jsonPath);
    }

    private function load(string $jsonPath): void
    {
        if (!file_exists($jsonPath)) {
            throw new \RuntimeException("Arquivo de configuração não encontrado: {$jsonPath}");
        }

        $content = file_get_contents($jsonPath);

        if ($content === false || !json_validate($content)) {
            throw new \RuntimeException("JSON inválido em {$jsonPath}");
        }

        $this->configs = json_decode($content, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function getUrls(string $codigoPrefeitura): array
    {
        return $this->configs[$codigoPrefeitura]['urls'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getOperations(string $codigoPrefeitura): array
    {
        return $this->configs[$codigoPrefeitura]['operations'] ?? [];
    }

    public function hasPrefeitura(string $codigoPrefeitura): bool
    {
        return isset($this->configs[$codigoPrefeitura]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getAll(): array
    {
        return $this->configs;
    }
}
