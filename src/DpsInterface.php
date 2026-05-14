<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional;

interface DpsInterface
{
    public function render(): string;
}
