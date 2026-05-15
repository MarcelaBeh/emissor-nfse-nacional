<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Enum;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\VersaoSchema;
use PHPUnit\Framework\TestCase;

final class VersaoSchemaTest extends TestCase
{
    public function test_v1_00(): void
    {
        $this->assertSame('1.00', VersaoSchema::V1_00->value);
    }

    public function test_v1_01(): void
    {
        $this->assertSame('1.01', VersaoSchema::V1_01->value);
    }
}
