<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Infrastructure\Http\Contract;

interface HttpClientInterface
{
    public function get(string $url, array $headers = []): array;
    public function post(string $url, mixed $data, array $headers = []): array;
}
