<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Request;

final readonly class DpsRequest
{
    /**
     * @param string $finNFSe Finalidade da NFS-e (TSRTCFinNFSe), obrigatória em `infDPS` desde a
     *                        NT 009 (independe do grupo IBSCBS). Nesta versão do leiaute o único
     *                        valor admitido é "0" (NFS-e regular); notas de ajuste de crédito/débito
     *                        ainda não têm leiaute publicado.
     */
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
        public ServicoRequest $servico,
        public ?TomadorRequest $tomador = null,
        public ?SubstituicaoRequest $substituicao = null,
        public ?IntermediarioRequest $intermediario = null,
        public ?IbsCbsRequest $ibscbs = null,
        public ?int $cMotivoEmisTI = null,
        public ?string $chNFSeRej = null,
        public string $finNFSe = '0',
    ) {
    }
}
