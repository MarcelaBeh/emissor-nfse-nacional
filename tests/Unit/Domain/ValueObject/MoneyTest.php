<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\ValueObject;

use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function test_create_from_int(): void
    {
        $money = new Money(10);
        $this->assertSame(1000, $money->getCents());
        $this->assertSame(10.0, $money->getValue());
    }

    public function test_create_from_float(): void
    {
        $money = new Money(10.50);
        $this->assertSame(1050, $money->getCents());
        $this->assertSame(10.5, $money->getValue());
    }

    public function test_create_from_string_with_dot(): void
    {
        $money = new Money('10.50');
        $this->assertSame(1050, $money->getCents());
    }

    public function test_create_from_string_with_comma(): void
    {
        $money = new Money('10,50');
        $this->assertSame(1050, $money->getCents());
    }

    public function test_from_cents(): void
    {
        $money = Money::fromCents(1050);
        $this->assertSame(1050, $money->getCents());
        $this->assertSame(10.5, $money->getValue());
    }

    public function test_add(): void
    {
        $a = new Money(10);
        $b = new Money(5.50);
        $result = $a->add($b);
        $this->assertSame(1550, $result->getCents());
    }

    public function test_subtract(): void
    {
        $a = new Money(10);
        $b = new Money(3.50);
        $result = $a->subtract($b);
        $this->assertSame(650, $result->getCents());
    }

    public function test_multiply(): void
    {
        $money = new Money(10);
        $result = $money->multiply(2.5);
        $this->assertSame(2500, $result->getCents());
    }

    public function test_percentage(): void
    {
        $money = new Money(100);
        $result = $money->percentage(15);
        $this->assertSame(1500, $result->getCents());
    }

    public function test_is_positive(): void
    {
        $this->assertTrue((new Money(1))->isPositive());
        $this->assertFalse((new Money(0))->isPositive());
        $this->assertFalse((new Money(-1))->isPositive());
    }

    public function test_is_zero(): void
    {
        $this->assertTrue((new Money(0))->isZero());
        $this->assertFalse((new Money(1))->isZero());
    }

    public function test_formatted_with_symbol(): void
    {
        $money = new Money(1234.56);
        $this->assertSame('R$ 1.234,56', $money->formatted(true));
    }

    public function test_formatted_without_symbol(): void
    {
        $money = new Money(1234.56);
        $this->assertSame('1.234,56', $money->formatted(false));
    }

    public function test_to_string(): void
    {
        $money = new Money(1234.56);
        $this->assertSame('1.234,56', (string) $money);
    }
}
