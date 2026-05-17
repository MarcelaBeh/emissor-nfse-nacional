<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Infrastructure\Http;

use MarcelaBeh\EmissorNfseNacional\Infrastructure\Config\Configuration;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\Contract\HttpClientInterface;

class ApiConnector
{
    private string $baseUrl;

    public function __construct(
        private Configuration $config,
        private HttpClientInterface $httpClient,
    ) {
        $this->baseUrl = $this->resolveBaseUrl();
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function get(string $endpoint, array $params = []): array
    {
        $url = $this->buildUrl($endpoint, $params);
        $headers = $this->buildHeaders();
        $response = $this->httpClient->get($url, $headers);
        return $this->parseResponse($response);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function post(string $endpoint, array $data): array
    {
        $url = $this->buildUrl($endpoint);
        $headers = $this->buildHeaders();
        $response = $this->httpClient->post($url, $data, $headers);
        return $this->parseResponse($response);
    }

    /**
     * @return array<string, mixed>
     */
    public function head(string $endpoint): array
    {
        $url = $this->buildUrl($endpoint);
        $headers = $this->buildHeaders();
        $response = $this->httpClient->head($url, $headers);

        return [
            'status' => $response['status'],
            'success' => $response['status'] >= 200 && $response['status'] < 300,
        ];
    }

    private function resolveBaseUrl(): string
    {
        $ambiente = $this->config->getTipoAmbiente();
        $tipo = $this->config->getTipoApi();

        $key = "{$tipo}_" . ($ambiente->isProducao() ? 'producao' : 'homologacao');

        return $this->config->getUrl($key);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function buildUrl(string $endpoint, array $params = []): string
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    /**
     * @return array<string, string>
     */
    private function buildHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'NFSeNacional-PHP/' . $this->config->getVersion(),
        ];
    }

    /**
     * @param array<string, mixed> $response
     * @return array<string, mixed>
     */
    private function parseResponse(array $response): array
    {
        $body = $response['body'];
        $status = $response['status'];

        if (is_string($body) && str_contains($body, 'nfseXmlGZipB64')) {
            if (json_validate($body)) {
                $decoded = json_decode($body, true);
                if (isset($decoded['nfseXmlGZipB64'])) {
                    $body = $this->decodeGzipBase64($decoded['nfseXmlGZipB64']);
                }
            }
        }

        if (is_string($body)) {
            if (json_validate($body)) {
                $body = json_decode($body, true);
            }
        }

        return [
            'status' => $status,
            'data' => $body,
            'success' => $status >= 200 && $status < 300,
        ];
    }

    private function decodeGzipBase64(string $data): string
    {
        $decoded = base64_decode($data, true);
        if ($decoded === false) {
            throw new \RuntimeException('Falha ao decodificar base64');
        }

        $uncompressed = gzdecode($decoded);
        if ($uncompressed === false) {
            throw new \RuntimeException('Falha ao descomprimir gzip');
        }

        return $uncompressed;
    }
}
