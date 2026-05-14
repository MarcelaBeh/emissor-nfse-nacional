<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Request;

final readonly class DpsRequest
{
    public function __construct(
        public int $tipoAmbiente,
        public string $dataEmissao,
        public string $versaoAplicacao,
        public int $serie,
        public int $numero,
        public string $dataCompetencia,
        public int $tipoEmissao,
        public string $codigoMunicipioEmissor,
        public PrestadorRequest $prestador,
        public TomadorRequest $tomador,
        public ServicoRequest $servico,
        public ?SubstituicaoRequest $substituicao = null,
        public ?IntermediarioRequest $intermediario = null,
        public ?IbsCbsRequest $ibscbs = null,
    ) {
    }
}
