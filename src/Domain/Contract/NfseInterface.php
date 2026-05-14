<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Domain\Contract;

interface NfseInterface
{
    public function getChaveAcesso(): string;
    public function getNumero(): string;
    public function getCodigoVerificacao(): string;
}
