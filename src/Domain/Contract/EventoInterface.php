<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Domain\Contract;

use emissorNfseNacional\NfseNacional\Domain\Enum\TipoEvento;

interface EventoInterface
{
    public function getTipo(): TipoEvento;
    public function getChaveNfse(): string;
    public function getDataEvento(): \DateTimeImmutable;
}
