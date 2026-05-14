<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Infrastructure\Http\Client;

use emissorNfseNacional\NfseNacional\Infrastructure\Http\Contract\HttpClientInterface;
use emissorNfseNacional\NfseNacional\Infrastructure\Http\Exception\ConnectionException;
use emissorNfseNacional\NfseNacional\Infrastructure\Http\Exception\TimeoutException;

class CurlHttpClient implements HttpClientInterface
{
    private const DEFAULT_TIMEOUT = 60;
    private const DEFAULT_CONNECT_TIMEOUT = 10;

    public function __construct(
        private int $timeout = self::DEFAULT_TIMEOUT,
        private int $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT,
        private ?string $certPath = null,
        private ?string $privateKeyPath = null,
        private ?string $keyPassword = null,
    ) {}

    public function get(string $url, array $headers = []): array
    {
        return $this->request('GET', $url, null, $headers);
    }

    public function post(string $url, mixed $data, array $headers = []): array
    {
        return $this->request('POST', $url, $data, $headers);
    }

    private function request(string $method, string $url, mixed $data = null, array $headers = []): array
    {
        $ch = curl_init();

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $this->prepareHeaders($headers),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSLVERSION => CURL_SSLVERSION_DEFAULT,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_HEADER => 0,
        ];

        if ($data !== null) {
            $options[CURLOPT_POSTFIELDS] = is_array($data) ? json_encode($data) : $data;
        }

        if ($this->certPath !== null) {
            $options[CURLOPT_SSLCERT] = $this->certPath;
        }

        if ($this->privateKeyPath !== null) {
            $options[CURLOPT_SSLKEY] = $this->privateKeyPath;
        }

        if ($this->keyPassword !== null) {
            $options[CURLOPT_KEYPASSWD] = $this->keyPassword;
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);

        curl_close($ch);

        if ($errno === CURLE_OPERATION_TIMEDOUT) {
            throw new TimeoutException("Request timeout after {$this->timeout}s");
        }

        if ($errno !== 0) {
            throw new ConnectionException("CURL Error [{$errno}]: {$error}");
        }

        return [
            'status' => $httpCode,
            'body' => $response,
        ];
    }

    private function prepareHeaders(array $headers): array
    {
        $prepared = ['Content-Type: application/json'];

        foreach ($headers as $key => $value) {
            $prepared[] = "{$key}: {$value}";
        }

        return $prepared;
    }
}
