<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Request;

final readonly class ServicoRequest
{
    public function __construct(
        public string $discriminacao,
        public string $codigoTributacao,
        public float $valorServicos,
        public ?float $descontoIncondicionado = null,
        public ?float $descontoCondicionado = null,
        public ?float $aliquotaIss = null,
        public ?string $codigoMunicipioPrestacao = null,
        public ?string $codigoNbs = null,
        public ?ObraRequest $obra = null,
        public ?string $tribISSQN = null,
        public ?string $tpRetISSQN = null,
        public ?string $codigoPaisPrestacao = null,
        public ?string $codigoPaisResultado = null,
        public ?string $codigoTributacaoMunicipal = null,
        public ?string $codigoInternoContribuinte = null,
        public ?float $valorRecebido = null,
        public ?ComExteriorRequest $comExterior = null,
        public ?AtvEventoRequest $atvEvento = null,
        public ?InfoComplRequest $infoCompl = null,
        /** @var DocAjusteBCRequest[]|null */
        public ?array $documentosAjusteBc = null,
        public ?int $tipoImunidade = null,
        public ?ExigSuspRequest $exigSusp = null,
        public ?BeneficioMunicipalRequest $beneficioMunicipal = null,
        public ?TribFederalRequest $tribFederal = null,
        public ?string $totTribTipo = null,
        public ?float $pTotTribFed = null,
        public ?float $pTotTribEst = null,
        public ?float $pTotTribMun = null,
        public ?string $indTotTrib = null,
        public ?float $pTotTribSN = null,
        /** pAjusteBCISSQN: percentual padrão de ajuste da base de cálculo do ISSQN (0 a 100). */
        public ?float $percentualAjusteBcIssqn = null,
        /** vAjusteBCISSQN: valor monetário padrão de ajuste da base de cálculo do ISSQN. */
        public ?float $valorAjusteBcIssqn = null,
        public ?float $vTotTribFed = null,
        public ?float $vTotTribEst = null,
        public ?float $vTotTribMun = null,
    ) {
    }
}
