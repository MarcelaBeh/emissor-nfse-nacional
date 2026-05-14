<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Request;

final readonly class IbsCbsReeRepResRequest
{
    /** @param IbsCbsDocumentoReeRepResRequest[] $documentos */
    public function __construct(
        public array $documentos,
    ) {
    }
}
