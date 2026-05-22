<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Presentation\Facade;

use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\DpsRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\EventoRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Response\EventoResponse;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Response\NfseResponse;
use MarcelaBeh\EmissorNfseNacional\Application\Service\CancelarNfseService;
use MarcelaBeh\EmissorNfseNacional\Application\Service\ConsultarNfseService;
use MarcelaBeh\EmissorNfseNacional\Application\Service\EmitirDpsService;
use MarcelaBeh\EmissorNfseNacional\Presentation\Factory\ServiceFactory;
use NFePHP\Common\Certificate;

class NfseNacionalFacade
{
    private EmitirDpsService $emitirDpsService;
    private ConsultarNfseService $consultarNfseService;
    private CancelarNfseService $cancelarNfseService;

    private function __construct(
        /** @var array<string, mixed> */
        private array $config,
        private Certificate $certificado,
    ) {
        $this->inicializarServicos();
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function create(array $config, Certificate $certificado): self
    {
        return new self($config, $certificado);
    }

    public function emitirDps(DpsRequest $request): NfseResponse
    {
        return $this->emitirDpsService->executar($request);
    }

    public function consultarPorChave(string $chave, bool $encoding = false): ?NfseResponse
    {
        return $this->consultarNfseService->consultarPorChave($chave, $encoding);
    }

    /**
     * @return array<string, mixed>
     */
    public function consultarDpsPorChave(string $chave): array
    {
        return $this->consultarNfseService->consultarDpsPorChave($chave);
    }

    public function cancelar(EventoRequest $request): EventoResponse
    {
        return $this->cancelarNfseService->executar($request);
    }

    /**
     * @return array<int, mixed>
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

    public function verificarDpsExiste(string $id): bool
    {
        return $this->consultarNfseService->verificarDpsExiste($id);
    }

    public function emitirPorDecisaoJudicial(string $nfseXml): NfseResponse
    {
        return $this->emitirDpsService->executarPorDecisaoJudicial($nfseXml);
    }

    private function inicializarServicos(): void
    {
        $factory = new ServiceFactory($this->config, $this->certificado);

        $this->emitirDpsService = $factory->createEmitirDpsService();
        $this->consultarNfseService = $factory->createConsultarNfseService();
        $this->cancelarNfseService = $factory->createCancelarNfseService();
    }
}
