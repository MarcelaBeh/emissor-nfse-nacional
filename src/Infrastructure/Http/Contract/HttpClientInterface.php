<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\Contract;

interface HttpClientInterface
{
    /**
     * @param array<string, string> $headers
     * @return array{status: int, body: string}
     */
    public function get(string $url, array $headers = []): array;
    /**
     * @param array<string, string> $headers
     * @return array{status: int, body: string}
     */
    public function post(string $url, mixed $data, array $headers = []): array;
}
