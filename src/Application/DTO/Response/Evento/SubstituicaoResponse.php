<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Response\Evento;

final readonly class SubstituicaoResponse implements EventoResponseInterface
{
    public function __construct(
        private string $chaveNfse,
        private string $tipoEvento,
        private ?string $dataRegistro = null,
        private ?string $numeroEvento = null,
        private bool $sucesso = true,
        private ?string $mensagem = null,
        private ?string $codigoMotivo = null,
        private ?string $descricaoMotivo = null,
        private ?string $chaveSubstituta = null,
    ) {
    }

    public function getTipoEvento(): string
    {
        return $this->tipoEvento;
    }

    public function getChaveNfse(): string
    {
        return $this->chaveNfse;
    }

    public function getDataRegistro(): ?string
    {
        return $this->dataRegistro;
    }

    public function getNumeroEvento(): ?string
    {
        return $this->numeroEvento;
    }

    public function getSucesso(): bool
    {
        return $this->sucesso;
    }

    public function getMensagem(): ?string
    {
        return $this->mensagem;
    }

    public function getCodigoMotivo(): ?string
    {
        return $this->codigoMotivo;
    }

    public function getDescricaoMotivo(): ?string
    {
        return $this->descricaoMotivo;
    }

    public function getChaveSubstituta(): ?string
    {
        return $this->chaveSubstituta;
    }
}
