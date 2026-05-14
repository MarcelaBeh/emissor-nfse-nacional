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
    ) {
    }
}
