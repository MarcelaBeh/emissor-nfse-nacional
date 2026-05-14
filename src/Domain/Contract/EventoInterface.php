<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Contract;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEvento;

interface EventoInterface
{
    public function getTipo(): TipoEvento;
    public function getChaveNfse(): string;
    public function getDataEvento(): \DateTimeImmutable;
}
