<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\ValueObject;

use MarcelaBeh\EmissorNfseNacional\Domain\Exception\ValidationException;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Nif;
use PHPUnit\Framework\TestCase;

final class NifTest extends TestCase
{
    public function test_valid_nif(): void
    {
        $nif = new Nif('123456789');
        $this->assertSame('123456789', $nif->getNif());
    }

    public function test_valid_nif_with_spaces_is_trimmed(): void
    {
        $nif = new Nif('  123456789  ');
        $this->assertSame('123456789', $nif->getNif());
    }

    public function test_max_length_nif(): void
    {
        $nif = new Nif('12345678901234567890');
        $this->assertSame('12345678901234567890', $nif->getNif());
    }

    public function test_empty_nif_throws_exception(): void
    {
        $this->expectException(ValidationException::class);
        new Nif('');
    }

    public function test_nif_too_long_throws_exception(): void
    {
        $this->expectException(ValidationException::class);
        new Nif('123456789012345678901');
    }

    public function test_to_string(): void
    {
        $nif = new Nif('123456789');
        $this->assertSame('123456789', (string) $nif);
    }
}
