<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Infrastructure\Repository;

use MarcelaBeh\EmissorNfseNacional\Domain\Contract\CstClassTribRepository;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CstClassTribProperties;

final class CachedCstClassTribRepository implements CstClassTribRepository
{
    private array $cache = [];

    public function __construct(
        private CstClassTribRepository $inner,
    ) {
    }

    public function findByCode(string $cClassTrib): ?CstClassTribProperties
    {
        if (!array_key_exists($cClassTrib, $this->cache)) {
            $this->cache[$cClassTrib] = $this->inner->findByCode($cClassTrib);
        }

        return $this->cache[$cClassTrib];
    }

    public function findByCst(string $cst): array
    {
        $key = "__cst_{$cst}";
        if (!array_key_exists($key, $this->cache)) {
            $this->cache[$key] = $this->inner->findByCst($cst);
        }

        return $this->cache[$key];
    }
}
