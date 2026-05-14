<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Response\Evento;

interface EventoResponseInterface
{
    public function getTipoEvento(): string;
    public function getChaveNfse(): string;
    public function getDataRegistro(): ?string;
    public function getNumeroEvento(): ?string;
    public function getSucesso(): bool;
    public function getMensagem(): ?string;
}
