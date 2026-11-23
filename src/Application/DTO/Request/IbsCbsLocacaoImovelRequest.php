<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Request;

final readonly class IbsCbsLocacaoImovelRequest
{
    public function __construct(
        public string $pCopropriedade,
        public string $vTotOper,
        public ?string $vDescIncondTot = null,
        public ?string $vDescCondTot = null,
        public ?string $dVencOrig = null,
    ) {
    }
}
