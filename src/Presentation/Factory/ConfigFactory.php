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

    public static function createDefault(int $tpAmb = 2): Configuration
    {
        return new Configuration(['tpAmb' => $tpAmb]);
    }

    public static function createHomologacao(): Configuration
    {
        return self::createDefault(TipoAmbiente::HOMOLOGACAO->value);
    }

    public static function createProducao(): Configuration
    {
        return self::createDefault(TipoAmbiente::PRODUCAO->value);
    }
}
