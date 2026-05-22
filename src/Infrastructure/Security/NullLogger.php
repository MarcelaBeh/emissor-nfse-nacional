<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Infrastructure\Security;

use MarcelaBeh\EmissorNfseNacional\Infrastructure\Security\Contract\LoggerInterface;

final class NullLogger implements LoggerInterface
{
    public function info(string $message, mixed ...$context): void {}

    public function warning(string $message, mixed ...$context): void {}

    public function error(string $message, mixed ...$context): void {}
}
