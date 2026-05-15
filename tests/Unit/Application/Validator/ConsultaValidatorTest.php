<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Application\Validator;

use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\ConsultaRequest;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ValidationException;
use MarcelaBeh\EmissorNfseNacional\Application\Validator\ConsultaValidator;
use PHPUnit\Framework\TestCase;

final class ConsultaValidatorTest extends TestCase
{
    private const CHAVE_50 = '12345678901234567890123456789012345678901234567890';

    private ConsultaValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ConsultaValidator();
    }

    public function test_validate_valid_chave(): void
    {
        $request = new ConsultaRequest(chave: self::CHAVE_50);

        $this->validator->validate($request);

        $this->assertTrue(true);
    }

    public function test_validate_invalid_chave_too_short(): void
    {
        $request = new ConsultaRequest(chave: '1234567890');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Chave de acesso deve ter exatamente 50 dígitos numéricos');

        $this->validator->validate($request);
    }
}
