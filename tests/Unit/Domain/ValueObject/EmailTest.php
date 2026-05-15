<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\ValueObject;

use MarcelaBeh\EmissorNfseNacional\Domain\Exception\ValidationException;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function test_valid_email(): void
    {
        $email = new Email('teste@exemplo.com');
        $this->assertSame('teste@exemplo.com', $email->getEmail());
    }

    public function test_valid_email_with_subdomain(): void
    {
        $email = new Email('usuario@sub.dominio.com.br');
        $this->assertSame('usuario@sub.dominio.com.br', $email->getEmail());
    }

    public function test_email_with_whitespace_is_trimmed(): void
    {
        $email = new Email('  teste@exemplo.com  ');
        $this->assertSame('teste@exemplo.com', $email->getEmail());
    }

    public function test_invalid_email_throws_exception(): void
    {
        $this->expectException(ValidationException::class);
        new Email('email-invalido');
    }

    public function test_empty_email_throws_exception(): void
    {
        $this->expectException(ValidationException::class);
        new Email('');
    }

    public function test_to_string(): void
    {
        $email = new Email('teste@exemplo.com');
        $this->assertSame('teste@exemplo.com', (string) $email);
    }
}
