<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Presentation\Facade;

use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\DpsRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\EventoRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Response\EventoResponse;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Response\NfseResponse;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ServiceException;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ValidationException;
use MarcelaBeh\EmissorNfseNacional\Application\Service\CancelarNfseService;
use MarcelaBeh\EmissorNfseNacional\Application\Service\ConsultarNfseService;
use MarcelaBeh\EmissorNfseNacional\Application\Service\EmitirDpsService;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\EventoProcessado;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Config\Configuration;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Security\Exception\CertificateExpiredException;
use MarcelaBeh\EmissorNfseNacional\Presentation\Factory\ServiceFactory;
use NFePHP\Common\Certificate;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class NfseNacionalFacade
{
    private EmitirDpsService $emitirDpsService;
    private ConsultarNfseService $consultarNfseService;
    private CancelarNfseService $cancelarNfseService;

    private function __construct(
        /** @var Configuration|array<string, mixed> */
        private Configuration|array $config,
        private Certificate $certificado,
        private LoggerInterface $logger = new NullLogger(),
    ) {
        $this->inicializarServicos();
    }

    /**
     * @param Configuration|array<string, mixed> $config aceita o array cru ou um objeto
     *        Configuration (ex.: produzido por ConfigFactory).
     * @param LoggerInterface $logger logger dos serviços. Padrão: NullLogger (sem saída). Para
     *        rastreabilidade em produção sem vazar dados sensíveis, passe um SanitizedLogger.
     * @throws CertificateExpiredException se o certificado estiver vencido. A proximidade de
     *         vencimento não é verificada pela lib; cabe ao integrador monitorá-la.
     */
    public static function create(
        Configuration|array $config,
        Certificate $certificado,
        LoggerInterface $logger = new NullLogger(),
    ): self {
        return new self($config, $certificado, $logger);
    }

    /**
     * Emite uma NFS-e a partir de um DPS.
     *
     * @throws ValidationException se os dados do DPS forem inválidos
     * @throws ServiceException se a comunicação com a API falhar
     */
    public function emitirDps(DpsRequest $request): NfseResponse
    {
        return $this->emitirDpsService->executar($request);
    }

    /**
     * Consulta uma NFS-e pela chave de acesso (50 dígitos).
     *
     * @throws ServiceException se a comunicação com a API falhar
     */
    public function consultarPorChave(string $chave, bool $encoding = false): ?NfseResponse
    {
        return $this->consultarNfseService->consultarPorChave($chave, $encoding);
    }

    /**
     * Descobre a chave de acesso da NFS-e gerada a partir de uma DPS, pelo Id da DPS ("DPS" + 42 dígitos). Devolve null
     * se nenhuma NFS-e foi gerada com esse Id.
     *
     * @throws ValidationException se o Id não tiver o formato do TSIdDPS
     * @throws ServiceException se a comunicação com a API falhar
     */
    public function consultarChavePorDps(string $idDps): ?string
    {
        return $this->consultarNfseService->consultarChavePorDps($idDps);
    }

    /**
     * Cancela ou manifesta uma NFS-e via evento. A substituição não é um pedido de evento: emita a NFS-e substituta com
     * `DpsRequest::$substituicao` e a SEFIN registra o evento 105102 na original.
     *
     * @throws ValidationException se os dados do evento forem inválidos
     * @throws ServiceException se a comunicação com a API falhar
     */
    public function cancelar(EventoRequest $request): EventoResponse
    {
        return $this->cancelarNfseService->executar($request);
    }

    /**
     * Consulta um evento já registrado e devolve os itens como a SEFIN os envia (com o `arquivoXml` ainda codificado).
     * O tipo e o sequencial são obrigatórios, porque a SEFIN não lista eventos sem eles. Para o evento já lido, use
     * `consultarEventosProcessados()`.
     *
     * @return list<array<string, mixed>> vazia se a SEFIN não encontrar o evento
     * @throws ValidationException se a chave, o tipo ou o sequencial forem inválidos
     * @throws ServiceException se a comunicação com a API falhar
     */
    public function consultarEventos(string $chave, string $tipoEvento, int $sequencial): array
    {
        return $this->consultarNfseService->consultarEventos($chave, $tipoEvento, $sequencial);
    }

    /**
     * Consulta um evento já registrado (o cancelamento, por exemplo, é o tipo 101101 sequencial 1) e devolve o evento
     * processado: data e hora do registro, sequencial e o XML assinado para guardar como comprovante. Lista vazia se a
     * SEFIN não encontrar o evento.
     *
     * @return list<EventoProcessado>
     * @throws ValidationException se a chave, o tipo ou o sequencial forem inválidos
     * @throws ServiceException se a comunicação com a API falhar ou o evento não puder ser lido
     */
    public function consultarEventosProcessados(string $chave, string $tipoEvento, int $sequencial): array
    {
        return $this->consultarNfseService->consultarEventosProcessados($chave, $tipoEvento, $sequencial);
    }

    /**
     * Verifica se uma NFS-e já foi gerada a partir de um DPS (HEAD /dps/{id}).
     */
    public function verificarDpsExiste(string $id): bool
    {
        return $this->consultarNfseService->verificarDpsExiste($id);
    }

    /**
     * Emite uma NFS-e por decisão judicial (POST /decisao-judicial/nfse).
     *
     * @throws ValidationException se o XML da NFS-e for inválido
     * @throws ServiceException se a comunicação com a API falhar
     */
    public function emitirPorDecisaoJudicial(string $nfseXml): NfseResponse
    {
        return $this->emitirDpsService->executarPorDecisaoJudicial($nfseXml);
    }

    private function inicializarServicos(): void
    {
        $factory = new ServiceFactory($this->config, $this->certificado, $this->logger);

        $this->emitirDpsService = $factory->createEmitirDpsService();
        $this->consultarNfseService = $factory->createConsultarNfseService();
        $this->cancelarNfseService = $factory->createCancelarNfseService();
    }
}
