<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Infrastructure\Security;

use MarcelaBeh\EmissorNfseNacional\Infrastructure\Security\Contract\LoggerInterface;

/**
 * Logger que sanitiza dados sensíveis (CPF, CNPJ, e-mail, chaves) antes de escrever.
 * Injete no lugar de NullLogger quando precisar de rastreabilidade em produção.
 */
final class SanitizedLogger implements LoggerInterface
{
    private SensitiveDataSanitizer $sanitizer;

    public function __construct(private readonly \Closure $writer)
    {
        $this->sanitizer = new SensitiveDataSanitizer();
    }

    public function info(string $message, mixed ...$context): void
    {
        $this->write('INFO', $message, $context);
    }

    public function warning(string $message, mixed ...$context): void
    {
        $this->write('WARNING', $message, $context);
    }

    public function error(string $message, mixed ...$context): void
    {
        $this->write('ERROR', $message, $context);
    }

    /** @param array<mixed> $context */
    private function write(string $level, string $message, array $context): void
    {
        $safe = sprintf(
            '[%s] [%s] %s%s',
            date('Y-m-d H:i:s'),
            $level,
            $this->sanitizer->sanitize($message),
            $context !== [] ? ' ' . json_encode($this->sanitizer->sanitize($context), JSON_UNESCAPED_UNICODE) : '',
        );

        ($this->writer)($safe);
    }
}
