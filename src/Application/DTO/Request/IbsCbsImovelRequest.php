<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Request;

final readonly class IbsCbsImovelRequest
{
    /** @param IbsCbsUnidadeImobiliariaRequest[] $unidades */
    public function __construct(
        public string $cMun,
        public ?IbsCbsLocacaoImovelRequest $locacao = null,
        public array $unidades = [],
    ) {
    }
}
