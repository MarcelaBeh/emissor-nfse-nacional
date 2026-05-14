<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Contract;

interface NfseInterface
{
    public function getChaveAcesso(): string;
    public function getNumero(): string;
    public function getCodigoVerificacao(): string;
}
