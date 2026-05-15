<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Enum;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoChaveDocumentoFiscal;
use PHPUnit\Framework\TestCase;

final class TipoChaveDocumentoFiscalTest extends TestCase
{
    public function test_all_cases(): void
    {
        $this->assertSame('1', TipoChaveDocumentoFiscal::NFS_E->value);
        $this->assertSame('2', TipoChaveDocumentoFiscal::NF_E->value);
        $this->assertSame('3', TipoChaveDocumentoFiscal::CT_E->value);
        $this->assertSame('9', TipoChaveDocumentoFiscal::OUTRO->value);
    }

    public function test_from_value(): void
    {
        $this->assertSame(TipoChaveDocumentoFiscal::NFS_E, TipoChaveDocumentoFiscal::fromValue('1'));
        $this->assertSame(TipoChaveDocumentoFiscal::NF_E, TipoChaveDocumentoFiscal::fromValue('2'));
    }

    public function test_from_value_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        TipoChaveDocumentoFiscal::fromValue('99');
    }
}
