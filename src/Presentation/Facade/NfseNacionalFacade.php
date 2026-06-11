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
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Config\Configuration;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Security\Exception\CertificateExpiredException;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Security\Exception\CertificateExpiringException;
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
     * @throws CertificateExpiredException se o certificado estiver vencido
     * @throws CertificateExpiringException se o certificado vencer em menos de 30 dias
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
     * Consulta o DPS original de uma NFS-e pela chave de acesso.
     *
     * @return array<string, mixed>
     * @throws ServiceException se a comunicação com a API falhar
     */
    public function consultarDpsPorChave(string $chave): array
    {
        return $this->consultarNfseService->consultarDpsPorChave($chave);
    }

    /**
     * Cancela, manifesta ou substitui uma NFS-e via evento.
     *
     * @throws ValidationException se os dados do evento forem inválidos
     * @throws ServiceException se a comunicação com a API falhar
     */
    public function cancelar(EventoRequest $request): EventoResponse
    {
        return $this->cancelarNfseService->executar($request);
    }

    /**
     * Consulta os eventos registrados para uma NFS-e.
     *
     * @return array<int, mixed>
     * @throws ServiceException se a comunicação com a API falhar
     */
    public function consultarEventos(
        string $chave,
        ?string $tipoEvento = null,
        ?int $sequencial = null
    ): array {
        return array_values($this->consultarNfseService->consultarEventos(
            $chave,
            $tipoEvento,
            $sequencial
        ));
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
