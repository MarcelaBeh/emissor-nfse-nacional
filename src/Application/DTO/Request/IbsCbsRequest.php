<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Request;

final readonly class IbsCbsRequest
{
    public function __construct(
        public string $finNFSe,
        public string $cIndOp,
        public string $indDest,
        public string $cst,
        public string $cClassTrib,
        public ?string $indFinal = null,
        public ?string $tpOper = null,
        public ?string $tpEnteGov = null,
        public ?string $cCredPres = null,
        public ?IbsCbsDestRequest $dest = null,
        public ?IbsCbsTribRegularRequest $tribRegular = null,
        public ?IbsCbsDiferimentoRequest $diferimento = null,
    ) {
    }
}
