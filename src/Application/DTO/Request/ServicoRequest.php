<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Request;

final readonly class ServicoRequest
{
    public function __construct(
        public string $discriminacao,
        public string $codigoTributacao,
        public string $codigoMunicipioPrestacao,
        public float $valorServicos,
        public float $valorDeducoes,
        public float $descontoIncondicionado,
        public float $descontoCondicionado,
        public float $aliquotaIss,
        public ?string $codigoNbs = null,
        public ?string $codigoCnae = null,
        public ?ObraRequest $obra = null,
        public ?string $tribISSQN = null,
        public ?string $tpRetISSQN = null,
        public ?string $codigoPaisPrestacao = null,
        public ?string $codigoTributacaoMunicipal = null,
        public ?string $codigoInternoContribuinte = null,
        public ?float $valorRecebido = null,
        public ?ComExteriorRequest $comExterior = null,
        public ?AtvEventoRequest $atvEvento = null,
        public ?InfoComplRequest $infoCompl = null,
        /** @var DocDedRedRequest[]|null */
        public ?array $documentosDeducao = null,
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
    ) {
    }
}
