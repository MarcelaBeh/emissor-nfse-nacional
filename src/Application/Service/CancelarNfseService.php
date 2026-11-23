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
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\EventoProcessado;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Config\ApiEndpoints;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\Contract\ApiConnectorInterface;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\Exception\HttpException;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\GzipBase64Decoder;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\RequestBuilder;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Security\Contract\XmlSignerInterface;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Builder\Contract\XmlBuilderInterface;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Parser\EventoXmlParser;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Validator\Contract\XsdValidatorInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class CancelarNfseService
{
    use SefinErrorMessageTrait;

    public function __construct(
        private ApiConnectorInterface $apiConnector,
        private XmlBuilderInterface $xmlBuilder,
        private XmlSignerInterface $xmlSigner,
        private XsdValidatorInterface $xsdValidator,
        private EventoValidator $validator,
        private RequestBuilder $requestBuilder,
        private ApiEndpoints $apiEndpoints,
        private LoggerInterface $logger = new NullLogger(),
        private EventoXmlParser $eventoXmlParser = new EventoXmlParser(),
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
                    mensagem: $this->extrairMensagemErro($response['data'] ?? null, 'Erro ao cancelar NFSe'),
                    dados: $response['data'] ?? null,
                    erros: $this->extrairErros($response['data'] ?? null),
                );
            }

            return new EventoResponse(
                success: true,
                mensagem: 'Cancelamento realizado com sucesso',
                dados: $response['data'] ?? null,
                evento: $this->lerEventoProcessado($response['data'] ?? null),
            );

        } catch (DomainException $e) {
            $this->logger->warning('Validação cancelamento falhou: {msg}', ['msg' => $e->getMessage()]);
            throw new ValidationException(
                "Dados inválidos: {$e->getMessage()}",
                0,
                $e
            );
        } catch (HttpException $e) {
            $this->logger->error('Falha HTTP ao cancelar NFSe: {msg}', ['msg' => $e->getMessage()]);
            throw new ServiceException(
                "Falha ao cancelar NFSe: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Lê o evento processado que a SEFIN devolve junto com o registro (`eventoXmlGZipB64`). O evento já está
     * registrado, então a leitura é um complemento: se falhar, o motivo vai para o log e o dado original continua em
     * `dados`, em vez de transformar um registro bem-sucedido em erro.
     */
    private function lerEventoProcessado(mixed $data): ?EventoProcessado
    {
        $compactado = is_array($data) ? ($data['eventoXmlGZipB64'] ?? null) : null;
        if (!is_string($compactado) || $compactado === '') {
            return null;
        }

        try {
            return $this->eventoXmlParser->parse(GzipBase64Decoder::decode($compactado));
        } catch (\RuntimeException $e) {
            $this->logger->warning(
                'Evento registrado, mas o XML processado não pôde ser lido: {msg}',
                ['msg' => $e->getMessage()]
            );

            return null;
        }
    }
}
