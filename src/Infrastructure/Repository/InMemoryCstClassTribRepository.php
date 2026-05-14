<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Infrastructure\Repository;

use MarcelaBeh\EmissorNfseNacional\Domain\Contract\CstClassTribRepository;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CstClassTribProperties;

final class InMemoryCstClassTribRepository implements CstClassTribRepository
{
    /** @var array<string, CstClassTribProperties> */
    private array $properties;

    /** @param array<string, CstClassTribProperties>|null $properties */
    public function __construct(?array $properties = null)
    {
        $this->properties = $properties ?? self::defaultData();
    }

    public function findByCode(string $cClassTrib): ?CstClassTribProperties
    {
        return $this->properties[$cClassTrib] ?? null;
    }

    public function findByCst(string $cst): array
    {
        return array_values(
            array_filter(
                $this->properties,
                fn (CstClassTribProperties $p) => $p->getCst() === $cst,
            )
        );
    }

    /**
     * @return array<string, CstClassTribProperties>
     */
    public static function defaultData(): array
    {
        $data = [];

        foreach (self::rawDefaultData() as $row) {
            $data[$row[0]] = new CstClassTribProperties(...$row);
        }

        return $data;
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: string, 3: bool, 4: bool, 5: bool, 6: ?float, 7: ?float}>
     */
    public static function rawDefaultData(): array
    {
        return [
            // cClassTrib,     cst,  desc,                                    validoNfse, permiteDif, exigeTribReg, pRedIBS, pRedCBS
            ['000001', '000', 'Tributação integral',                          true,  false, false, null,  null],
            ['010001', '010', 'Tributação alíquotas uniformes',               true,  false, false, null,  null],
            ['011001', '011', 'Tributação alíquotas uniformes reduzidas',     true,  false, false, null,  null],
            ['200001', '200', 'Alíquota zero',                                true,  false, false, 100.0, 100.0],
            ['200002', '200', 'Alíquota reduzida 60%',                        true,  false, false, 60.0,  60.0],
            ['200003', '200', 'Alíquota reduzida 40%',                        true,  false, false, 40.0,  40.0],
            ['200004', '200', 'Alíquota reduzida 30%',                        true,  false, false, 30.0,  30.0],
            ['210001', '210', 'Alíquota reduzida c/ redutor BC',              true,  false, false, 60.0,  60.0],
            ['220001', '220', 'Alíquota fixa',                                true,  false, false, null,  null],
            ['221001', '221', 'Alíquota fixa proporcional',                   true,  false, false, null,  null],
            ['400001', '400', 'Isenção',                                      true,  false, false, 100.0, 100.0],
            ['410001', '410', 'Imunidade e não incidência',                   true,  false, false, 100.0, 100.0],
            ['510001', '510', 'Diferimento',                                  true,  true,  false, 100.0, 100.0],
            ['550001', '550', 'Suspensão',                                    true,  false, false, 100.0, 100.0],
            ['620001', '620', 'Tributação monofásica combustíveis',           false, false, false, null,  null],
            ['800001', '800', 'Transferência de crédito',                     false, false, false, null,  null],
            ['810001', '810', 'Ajustes',                                      false, false, false, null,  null],
            ['820001', '820', 'Tributação em declaração regime específico',   false, false, false, null,  null],
            ['100001', '100', 'Tributação integral serviços',                 true,  false, false, null,  null],
            ['100002', '100', 'Tributação integral serviços c/ dedução',      true,  false, true,  null,  null],
            ['510002', '510', 'Diferimento serviços',                         true,  true,  false, 100.0, 100.0],
        ];
    }
}
