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
        private array $config,
        private Certificate $certificado,
    ) {
        $this->inicializarServicos();
    }

    public static function create(array $config, Certificate $certificado): self
    {
        return new self($config, $certificado);
    }

    public function emitirDps(DpsRequest $request): NfseResponse
    {
        return $this->emitirDpsService->executar($request);
    }

    public function consultarPorChave(string $chave): ?NfseResponse
    {
        return $this->consultarNfseService->consultarPorChave($chave);
    }

    public function consultarDpsPorChave(string $chave): array
    {
        return $this->consultarNfseService->consultarDpsPorChave($chave);
    }

    public function cancelar(EventoRequest $request): EventoResponse
    {
        return $this->cancelarNfseService->executar($request);
    }

    public function consultarEventos(
        string $chave,
        ?string $tipoEvento = null,
        ?int $sequencial = null
    ): array {
        return $this->consultarNfseService->consultarEventos(
            $chave,
            $tipoEvento,
            $sequencial
        );
    }

    public function consultarDanfse(string $chave): string|array
    {
        return $this->consultarNfseService->consultarDanfse($chave);
    }

    private function inicializarServicos(): void
    {
        $factory = new ServiceFactory($this->config, $this->certificado);

        $this->emitirDpsService = $factory->createEmitirDpsService();
        $this->consultarNfseService = $factory->createConsultarNfseService();
        $this->cancelarNfseService = $factory->createCancelarNfseService();
    }
}
