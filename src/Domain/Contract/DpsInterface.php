<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Domain\Contract;

interface DpsInterface
{
    public function render(): string;
}
