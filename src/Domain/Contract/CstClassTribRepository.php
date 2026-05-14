<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Contract;

use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CstClassTribProperties;

interface CstClassTribRepository
{
    public function findByCode(string $cClassTrib): ?CstClassTribProperties;

    /**
     * @return array<int, CstClassTribProperties>
     */
    public function findByCst(string $cst): array;
}
