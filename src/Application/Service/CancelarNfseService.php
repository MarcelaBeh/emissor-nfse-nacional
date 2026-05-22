<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\Service;

use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\EventoRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Response\EventoResponse;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ServiceException;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ValidationException;
use MarcelaBeh\EmissorNfseNacional\Application\Validator\EventoValidator;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Evento;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEvento;
use MarcelaBeh\EmissorNfseNacional\Domain\Exception\DomainException;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\ChaveAcesso;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Config\ApiEndpoints;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\Contract\ApiConnectorInterface;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\Exception\HttpException;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\RequestBuilder;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Security\Contract\XmlSignerInterface;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Builder\Contract\XmlBuilderInterface;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Validator\Contract\XsdValidatorInterface;

class CancelarNfseService
{
    public function __construct(
        private ApiConnectorInterface $apiConnector,
        private XmlBuilderInterface $xmlBuilder,
        private XmlSignerInterface $xmlSigner,
        private XsdValidatorInterface $xsdValidator,
        private EventoValidator $validator,
        private RequestBuilder $requestBuilder,
        private ApiEndpoints $apiEndpoints,
    ) {
    }

    public function executar(EventoRequest $request): EventoResponse
    {
        try {
            $this->validator->validate($request);

            $evento = new Evento(
                tipo: TipoEvento::from($request->tipoEvento),
                chaveNfse: new ChaveAcesso($request->chaveNfse),
                dataEvento: new \DateTimeImmutable($request->dataEvento),
                versaoAplicacao: $request->versaoAplicacao,
                tipoAmbiente: $request->tipoAmbiente,
                cnpjAutor: $request->cnpjAutor,
                cpfAutor: $request->cpfAutor,
                codigoMotivo: $request->codigoMotivo,
                descricaoMotivo: $request->descricaoMotivo,
                nSeqEvento: $request->nSeqEvento,
                ambGer: $request->ambGer,
                dhProc: $request->dhProc !== null ? new \DateTimeImmutable($request->dhProc) : null,
                nDFSe: $request->nDFSe,
                chSubstituta: $request->chSubstituta,
                cpfAgTrib: $request->cpfAgTrib,
                nProcAdm: $request->nProcAdm,
                xProcAdm: $request->xProcAdm,
                idEvManifRej: $request->idEvManifRej,
                codEventoBloqueio: $request->codEventoBloqueio,
                idBloqOfic: $request->idBloqOfic,
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
