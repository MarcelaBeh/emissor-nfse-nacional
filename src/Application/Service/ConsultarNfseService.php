<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\Service;

use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\ConsultaRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Response\NfseResponse;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ServiceException;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ValidationException;
use MarcelaBeh\EmissorNfseNacional\Application\Validator\ConsultaValidator;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEvento;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\ChaveAcesso;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\EventoProcessado;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Config\ApiEndpoints;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\Contract\ApiConnectorInterface;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\Exception\HttpException;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\GzipBase64Decoder;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Parser\EventoXmlParser;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Parser\NfseXmlParser;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ConsultarNfseService
{
    use SefinErrorMessageTrait;

    public function __construct(
        private ApiConnectorInterface $apiConnector,
        private ApiEndpoints $apiEndpoints,
        private ConsultaValidator $validator,
        private NfseXmlParser $nfseXmlParser,
        private LoggerInterface $logger = new NullLogger(),
        private EventoXmlParser $eventoXmlParser = new EventoXmlParser(),
    ) {
    }

    /**
     * Consulta uma NFS-e pela chave de acesso. A consulta devolve a NFS-e como foi emitida: o `cStat` do XML continua
     * 100 mesmo depois de um cancelamento, que só aparece nos eventos (`consultarEventosProcessados()`).
     *
     * Quando a SEFIN recusa a consulta, o resultado tem `success` falso com a mensagem e os erros que ela devolveu.
     */
    public function consultarPorChave(string $chave, bool $encoding = false): ?NfseResponse
    {
        try {
            $this->validator->validate(new ConsultaRequest($chave));

            $endpoint = $this->apiEndpoints->consultarNfse($chave);
            $response = $this->apiConnector->get($endpoint);

            if (!$response['success']) {
                $status = (int) ($response['status'] ?? 404);
                $erro = $response['data'] ?? null;

                return new NfseResponse(
                    success: false,
                    mensagem: $this->extrairMensagemErro(
                        $erro,
                        $status === 404 ? 'NFSe não encontrada' : "Falha ao consultar NFSe (HTTP {$status})"
                    ),
                    dados: is_array($erro) ? $erro : null,
                    erros: $this->extrairErros($erro),
                );
            }

            $data = $response['data'];

            if (is_string($data) && str_contains($data, '<')) {
                $xml = $encoding ? mb_convert_encoding($data, 'ISO-8859-1') : $data;
                $dados = $this->nfseXmlParser->parse($xml)[0] ?? null;
                $numero = $dados['numero'] ?? null;

                return new NfseResponse(
                    success: true,
                    chaveAcesso: ChaveAcesso::doIdNfse($dados['id'] ?? null),
                    numero: is_string($numero) && $numero !== '' ? $numero : null,
                    dados: $dados,
                    xml: $xml,
                );
            }

            return new NfseResponse(
                success: true,
                dados: is_array($data) ? $data : null,
            );

        } catch (HttpException $e) {
            $this->logger->error('Falha HTTP ao consultar NFSe: {msg}', ['msg' => $e->getMessage()]);
            throw new ServiceException(
                "Falha ao consultar NFSe: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Descobre a chave de acesso da NFS-e gerada a partir de uma DPS (`GET dps/{id}`).
     *
     * @param string $idDps Id da DPS (TSIdDPS): "DPS" + 42 dígitos
     * @return string|null a chave de 50 posições, ou null se a SEFIN informa que nenhuma NFS-e foi gerada com esse Id
     * @throws ValidationException se o Id não tiver o formato do TSIdDPS
     * @throws ServiceException para qualquer outra falha da SEFIN
     */
    public function consultarChavePorDps(string $idDps): ?string
    {
        $this->validarIdDps($idDps);

        try {
            $response = $this->apiConnector->get($this->apiEndpoints->consultarDps($idDps));
        } catch (HttpException $e) {
            $this->logger->error('Falha HTTP ao consultar a DPS: {msg}', ['msg' => $e->getMessage()]);
            throw new ServiceException("Falha ao consultar a DPS: {$e->getMessage()}", 0, $e);
        }

        if (!$response['success']) {
            $status = (int) ($response['status'] ?? 0);
            if ($status === 404) {
                return null;
            }
            throw new ServiceException(
                'Falha ao consultar a DPS: ' . $this->extrairMensagemErro($response['data'] ?? null, "HTTP {$status}")
            );
        }

        $data = $response['data'] ?? null;
        $chave = is_array($data) ? ($data['chaveAcesso'] ?? null) : null;
        if (!is_string($chave) || !ChaveAcesso::isValida($chave)) {
            throw new ServiceException('Resposta da SEFIN sem a chave de acesso da NFS-e');
        }

        return (new ChaveAcesso($chave))->getChave();
    }

    public function verificarDpsExiste(string $id): bool
    {
        $this->validarIdDps($id);

        try {
            $endpoint = $this->apiEndpoints->verificarDps($id);
            $response = $this->apiConnector->head($endpoint);

            return $response['success'];
        } catch (HttpException $e) {
            return false;
        }
    }

    /**
     * Consulta um evento já registrado e devolve os itens como a SEFIN os envia (`chaveAcesso`, `tipoEvento`,
     * `numeroPedidoRegistroEvento`, `dataHoraRecebimento` e `arquivoXml`, este ainda codificado). Para o evento já
     * lido, use `consultarEventosProcessados()`.
     *
     * O tipo e o sequencial são obrigatórios: a SEFIN não tem listagem sem eles (`GET .../eventos` responde 405).
     *
     * @return list<array<string, mixed>> vazia se a SEFIN responde 404
     * @throws ValidationException se a chave, o tipo ou o sequencial forem inválidos
     * @throws ServiceException para qualquer outra falha da SEFIN
     */
    public function consultarEventos(string $chave, string $tipoEvento, int $sequencial): array
    {
        return $this->buscarEventos($chave, $tipoEvento, $sequencial);
    }

    /**
     * Consulta um evento já registrado e devolve o evento processado lido do XML que a SEFIN envia (`arquivoXml`).
     *
     * Quando não há evento com esse tipo e sequencial a SEFIN responde 404 sem mensagem, e o 404 também vale para uma
     * chave que não existe, então os dois casos devolvem lista vazia.
     *
     * @return list<EventoProcessado>
     * @throws ValidationException se a chave, o tipo ou o sequencial forem inválidos
     * @throws ServiceException para qualquer outra falha da SEFIN ou se o XML de um evento não puder ser lido
     */
    public function consultarEventosProcessados(string $chave, string $tipoEvento, int $sequencial): array
    {
        $eventos = [];
        foreach ($this->buscarEventos($chave, $tipoEvento, $sequencial) as $item) {
            $arquivo = $item['arquivoXml'] ?? null;
            if (!is_string($arquivo) || $arquivo === '') {
                throw new ServiceException('Evento devolvido pela SEFIN sem o campo arquivoXml');
            }
            try {
                $eventos[] = $this->eventoXmlParser->parse(GzipBase64Decoder::decode($arquivo));
            } catch (\RuntimeException $e) {
                throw new ServiceException("Não foi possível ler o evento devolvido pela SEFIN: {$e->getMessage()}", 0, $e);
            }
        }

        return $eventos;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buscarEventos(string $chave, string $tipoEvento, int $sequencial): array
    {
        $this->validator->validate(new ConsultaRequest($chave));

        if (TipoEvento::tryFrom($tipoEvento) === null) {
            throw new ValidationException("Tipo de evento inválido: {$tipoEvento}");
        }
        if ($sequencial < 1 || $sequencial > 999) {
            throw new ValidationException('O sequencial do evento deve estar entre 1 e 999');
        }

        try {
            $response = $this->apiConnector->get($this->apiEndpoints->consultarEventos($chave, $tipoEvento, $sequencial));
        } catch (HttpException $e) {
            $this->logger->error('Falha HTTP ao consultar evento: {msg}', ['msg' => $e->getMessage()]);
            throw new ServiceException("Falha ao consultar evento: {$e->getMessage()}", 0, $e);
        }

        if (!$response['success']) {
            $status = (int) ($response['status'] ?? 0);
            if ($status === 404) {
                return [];
            }
            throw new ServiceException(
                'Falha ao consultar evento: ' . $this->extrairMensagemErro($response['data'] ?? null, "HTTP {$status}")
            );
        }

        $data = $response['data'] ?? null;
        $itens = is_array($data) && is_array($data['eventos'] ?? null) ? $data['eventos'] : [];

        return array_values(array_filter($itens, 'is_array'));
    }

    private function validarIdDps(string $id): void
    {
        // Id do DPS é TSIdDPS: "DPS" + 42 dígitos (45 caracteres). Valida antes de chamar a API.
        if (preg_match('/^DPS[0-9]{42}$/', $id) !== 1) {
            throw new ValidationException('Id do DPS deve estar no formato DPS seguido de 42 dígitos (TSIdDPS)');
        }
    }
}
