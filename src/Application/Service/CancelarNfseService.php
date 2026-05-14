<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Application\Service;

use emissorNfseNacional\NfseNacional\Application\DTO\Request\EventoRequest;
use emissorNfseNacional\NfseNacional\Application\DTO\Response\EventoResponse;
use emissorNfseNacional\NfseNacional\Application\Exception\ServiceException;
use emissorNfseNacional\NfseNacional\Application\Exception\ValidationException;
use emissorNfseNacional\NfseNacional\Application\Validator\EventoValidator;
use emissorNfseNacional\NfseNacional\Domain\Entity\Evento;
use emissorNfseNacional\NfseNacional\Domain\Enum\TipoEvento;
use emissorNfseNacional\NfseNacional\Domain\Enum\TipoAmbiente;
use emissorNfseNacional\NfseNacional\Domain\ValueObject\ChaveAcesso;
use emissorNfseNacional\NfseNacional\Domain\Exception\DomainException;
use emissorNfseNacional\NfseNacional\Infrastructure\Http\ApiConnector;
use emissorNfseNacional\NfseNacional\Infrastructure\Http\RequestBuilder;
use emissorNfseNacional\NfseNacional\Infrastructure\Http\Exception\HttpException;
use emissorNfseNacional\NfseNacional\Infrastructure\Security\XmlSigner;
use emissorNfseNacional\NfseNacional\Infrastructure\Xml\Builder\EventoXmlBuilder;
use emissorNfseNacional\NfseNacional\Infrastructure\Xml\Validator\XsdValidator;
use emissorNfseNacional\NfseNacional\Infrastructure\Config\ApiEndpoints;

class CancelarNfseService
{
    public function __construct(
        private ApiConnector $apiConnector,
        private EventoXmlBuilder $xmlBuilder,
        private XmlSigner $xmlSigner,
        private XsdValidator $xsdValidator,
        private EventoValidator $validator,
        private RequestBuilder $requestBuilder,
        private ApiEndpoints $apiEndpoints,
    ) {}

    public function executar(EventoRequest $request): EventoResponse
    {
        try {
            $this->validator->validate($request);

            $evento = new Evento(
                tipo: TipoEvento::from($request->tipoEvento),
                chaveNfse: new ChaveAcesso($request->chaveNfse),
                dataEvento: new \DateTimeImmutable($request->dataEvento),
                versaoAplicacao: $request->versaoAplicacao,
                cnpjAutor: $request->cnpjAutor,
                cpfAutor: $request->cpfAutor,
                codigoMotivo: $request->codigoMotivo,
                descricaoMotivo: $request->descricaoMotivo,
            );

            $xml = $this->xmlBuilder->build($evento);

            $this->xsdValidator->validate($xml, 'pedRegEvento');

            $xmlAssinado = $this->xmlSigner->sign($xml, 'infPedReg', 'pedRegEvento');
            $xmlAssinado = '<?xml version="1.0" encoding="UTF-8"?>' . $xmlAssinado;

            $payload = $this->requestBuilder->buildEventoPayload($xmlAssinado);

            $endpoint = $this->apiEndpoints->cancelarNfse($request->chaveNfse);
            $response = $this->apiConnector->post($endpoint, $payload);

            if (!$response['success']) {
                return new EventoResponse(
                    success: false,
                    mensagem: $response['data']['mensagem'] ?? 'Erro ao cancelar NFSe',
                    dados: $response['data'] ?? null,
                );
            }

            return new EventoResponse(
                success: true,
                mensagem: 'Cancelamento realizado com sucesso',
                dados: $response['data'] ?? null,
            );

        } catch (DomainException $e) {
            throw new ValidationException(
                "Dados inválidos: {$e->getMessage()}",
                0,
                $e
            );
        } catch (HttpException $e) {
            throw new ServiceException(
                "Falha ao cancelar NFSe: {$e->getMessage()}",
                0,
                $e
            );
        }
    }
}
