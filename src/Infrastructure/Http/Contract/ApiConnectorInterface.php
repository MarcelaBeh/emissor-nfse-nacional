<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\Contract;

interface ApiConnectorInterface
{
    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function get(string $endpoint, array $params = []): array;

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function post(string $endpoint, array $data): array;

    /**
     * @return array<string, mixed>
     */
    public function head(string $endpoint): array;
}
