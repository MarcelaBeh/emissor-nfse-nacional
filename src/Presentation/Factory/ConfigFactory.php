<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Presentation\Factory;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoAmbiente;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Config\Configuration;

class ConfigFactory
{
    /**
     * @param array<string, mixed> $config
     */
    public static function create(array $config): Configuration
    {
        return new Configuration($config);
    }

    public static function createDefault(string $prefeitura, int $tpAmb = 2): Configuration
    {
        return new Configuration([
            'tpAmb' => $tpAmb,
            'prefeitura' => $prefeitura,
        ]);
    }

    public static function createHomologacao(string $prefeitura): Configuration
    {
        return self::createDefault($prefeitura, TipoAmbiente::HOMOLOGACAO->value);
    }

    public static function createProducao(string $prefeitura): Configuration
    {
        return self::createDefault($prefeitura, TipoAmbiente::PRODUCAO->value);
    }
}
