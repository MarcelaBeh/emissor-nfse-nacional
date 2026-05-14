<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Infrastructure\Config\Contract;

interface ConfigInterface
{
    public function getTipoAmbiente(): \emissorNfseNacional\NfseNacional\Domain\Enum\TipoAmbiente;
    public function getUrl(string $key): string;
    public function getOperation(string $key): string;
    public function get(string $key, mixed $default = null): mixed;
    public function getVersion(): string;
    public function getTipoApi(): string;
}
