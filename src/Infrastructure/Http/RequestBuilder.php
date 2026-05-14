<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Infrastructure\Http;

class RequestBuilder
{
    /**
     * @return array<string, string>
     */
    public function buildGzipBase64Payload(string $xml, string $fieldName): array
    {
        $gzipped = gzencode($xml);
        if ($gzipped === false) {
            throw new \RuntimeException('Failed to gzip encode XML');
        }
        $base64 = base64_encode($gzipped);
        return [$fieldName => $base64];
    }

    /**
     * @return array<string, string>
     */
    public function buildDpsPayload(string $xml): array
    {
        return $this->buildGzipBase64Payload($xml, 'dpsXmlGZipB64');
    }

    /**
     * @return array<string, string>
     */
    public function buildEventoPayload(string $xml): array
    {
        return $this->buildGzipBase64Payload($xml, 'pedidoRegistroEventoXmlGZipB64');
    }
}
