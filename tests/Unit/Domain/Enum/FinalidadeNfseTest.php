<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Enum;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\FinalidadeNfse;
use PHPUnit\Framework\TestCase;

final class FinalidadeNfseTest extends TestCase
{
    public function test_regular(): void
    {
        $this->assertSame('0', FinalidadeNfse::REGULAR->value);
        $this->assertSame('NFS-e regular', FinalidadeNfse::REGULAR->descricao());
    }
}
