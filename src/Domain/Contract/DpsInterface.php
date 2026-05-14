<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Contract;

interface DpsInterface
{
    public function render(): string;
}
