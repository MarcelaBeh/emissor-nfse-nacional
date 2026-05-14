<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Domain\Enum;

enum VersaoSchema: string
{
    case V1_00 = '1.00';
    case V1_01 = '1.01';

    public function descricao(): string
    {
        return "v{$this->value}";
    }
}
