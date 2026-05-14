<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Response\Evento;

final readonly class GenericEventoResponse implements EventoResponseInterface
{
    public function __construct(
        private string $chaveNfse,
        private string $tipoEvento,
        private ?string $dataRegistro = null,
        private ?string $numeroEvento = null,
        private bool $sucesso = true,
        private ?string $mensagem = null,
        private ?array $dadosAdicionais = null,
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

    public function getDadosAdicionais(): ?array
    {
        return $this->dadosAdicionais;
    }
}
