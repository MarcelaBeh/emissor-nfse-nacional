<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\Service;

use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\ConsultaRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Response\NfseResponse;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ServiceException;
use MarcelaBeh\EmissorNfseNacional\Application\Validator\ConsultaValidator;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Config\ApiEndpoints;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\ApiConnector;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\Exception\HttpException;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Parser\NfseXmlParser;

class ConsultarNfseService
{
    public function __construct(
        private ApiConnector $apiConnector,
        private ApiEndpoints $apiEndpoints,
        private ConsultaValidator $validator,
        private NfseXmlParser $nfseXmlParser,
    ) {
    }

    public function consultarPorChave(string $chave): ?NfseResponse
    {
        try {
            $this->validator->validate(new ConsultaRequest($chave));

            $endpoint = $this->apiEndpoints->consultarNfse($chave);
            $response = $this->apiConnector->get($endpoint);

            if (!$response['success']) {
                return new NfseResponse(
                    success: false,
                    mensagem: 'NFSe não encontrada',
                );
            }

            $data = $response['data'];

            if (is_string($data) && str_contains($data, '<')) {
                $parsed = $this->nfseXmlParser->parse($data);
                return new NfseResponse(
                    success: true,
                    dados: $parsed,
                    xml: $data,
                );
            }

            return new NfseResponse(
                success: true,
                dados: is_array($data) ? $data : null,
            );

        } catch (HttpException $e) {
            throw new ServiceException(
                "Falha ao consultar NFSe: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    public function consultarDpsPorChave(string $chave): array
    {
        try {
            $this->validator->validate(new ConsultaRequest($chave));

            $endpoint = $this->apiEndpoints->consultarDps($chave);
            $response = $this->apiConnector->get($endpoint);

            if (!$response['success']) {
                return ['erro' => 'DPS não encontrada'];
            }

            $data = $response['data'];

            if (is_string($data)) {
                return json_decode($data, true) ?? ['dados' => $data];
            }

            return $data ?? [];

        } catch (HttpException $e) {
            throw new ServiceException(
                "Falha ao consultar DPS: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    public function consultarEventos(string $chave, ?string $tipoEvento = null, ?int $sequencial = null): array
    {
        try {
            $this->validator->validate(new ConsultaRequest($chave));

            $endpoint = $this->apiEndpoints->consultarEventos($chave, $tipoEvento, $sequencial);
            $response = $this->apiConnector->get($endpoint);

            if (!$response['success']) {
                return ['erro' => 'Eventos não encontrados'];
            }

            $data = $response['data'];
            return is_string($data) ? json_decode($data, true) ?? [] : $data ?? [];

        } catch (HttpException $e) {
            throw new ServiceException(
                "Falha ao consultar eventos: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    public function consultarDanfse(string $chave): string|array
    {
        try {
            $this->validator->validate(new ConsultaRequest($chave));

            $endpoint = $this->apiEndpoints->consultarDanfse($chave);
            $response = $this->apiConnector->get($endpoint);

            if (!$response['success']) {
                return $this->consultarDanfseNfse($chave);
            }

            $data = $response['data'];

            if (is_string($data)) {
                return $data;
            }

            if (is_array($data) && !isset($data['erro'])) {
                return $data;
            }

            return $this->consultarDanfseNfse($chave);

        } catch (HttpException $e) {
            throw new ServiceException(
                "Falha ao consultar DANFSe: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    private function consultarDanfseNfse(string $chave): string|array
    {
        $endpointCert = $this->apiEndpoints->consultarDanfseNfseCertificado();
        $response = $this->apiConnector->get($endpointCert);

        if (is_array($response['data']) && ($response['data']['sucesso'] ?? false)) {
            $endpoint = $this->apiEndpoints->consultarDanfseNfseDownload($chave);
            $response = $this->apiConnector->get($endpoint);

            if ($response['success'] && is_string($response['data'])) {
                return $response['data'];
            }
        }

        return ['erro' => 'Não foi possível obter o DANFSe'];
    }
}
