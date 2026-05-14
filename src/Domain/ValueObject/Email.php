<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\ValueObject;

use MarcelaBeh\EmissorNfseNacional\Domain\Exception\ValidationException;

final readonly class Email
{
    private string $email;

    public function __construct(string $email)
    {
        $this->email = $this->validate($email);
    }

    private function validate(string $email): string
    {
        $email = trim($email);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException("Email inválido: {$email}");
        }

        return $email;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function __toString(): string
    {
        return $this->email;
    }
}
