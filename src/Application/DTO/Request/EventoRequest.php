<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Request;

final readonly class EventoRequest
{
    /**
     * @param string|null $nSeqEvento @deprecated desde a 2.4.0, ignorado: é campo do evento processado (a resposta da SEFIN) e nunca foi enviado
     * @param int|null $ambGer @deprecated desde a 2.4.0, ignorado: é campo do evento processado (a resposta da SEFIN) e nunca foi enviado
     * @param string|null $dhProc @deprecated desde a 2.4.0, ignorado: é campo do evento processado (a resposta da SEFIN) e nunca foi enviado
     * @param string|null $nDFSe @deprecated desde a 2.4.0, ignorado: é campo do evento processado (a resposta da SEFIN) e nunca foi enviado
     * @param string|null $chSubstituta @deprecated desde a 2.4.0: a SEFIN recusa o pedido de evento 105102 (E1861); emita a DPS
     *        substituta com o grupo subst (`DpsRequest::$substituicao`)
     */
    public function __construct(
        public int $tipoAmbiente,
        public string $versaoAplicacao,
        public string $dataEvento,
        public string $chaveNfse,
        public string $tipoEvento,
        public ?string $cnpjAutor = null,
        public ?string $cpfAutor = null,
        public ?string $codigoMotivo = null,
        public ?string $descricaoMotivo = null,
        public ?string $nSeqEvento = null,
        public ?int $ambGer = null,
        public ?string $dhProc = null,
        public ?string $nDFSe = null,
        public ?string $chSubstituta = null,
        public ?string $cpfAgTrib = null,
        public ?string $nProcAdm = null,
        public ?string $xProcAdm = null,
        public ?string $idEvManifRej = null,
        public ?string $codEventoBloqueio = null,
        public ?string $idBloqOfic = null,
    ) {
    }
}
