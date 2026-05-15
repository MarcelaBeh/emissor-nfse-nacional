<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Application\Validator;

use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\EventoRequest;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ValidationException;
use MarcelaBeh\EmissorNfseNacional\Application\Validator\EventoValidator;
use PHPUnit\Framework\TestCase;

final class EventoValidatorTest extends TestCase
{
    private const CHAVE_50 = '12345678901234567890123456789012345678901234567890';

    private EventoValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new EventoValidator();
    }

    public function test_validate_valid_evento_request(): void
    {
        $request = new EventoRequest(
            tipoEvento: '101101',
            chaveNfse: self::CHAVE_50,
            dataEvento: '2026-05-15',
            versaoAplicacao: '1.0.0',
            tipoAmbiente: '2',
            cnpjAutor: '12345678000195',
            codigoMotivo: '1',
            descricaoMotivo: 'Cancelamento de teste',
            nSeqEvento: '1',
        );

        $this->validator->validate($request);

        $this->assertTrue(true);
    }

    public function test_validate_invalid_chave_too_short(): void
    {
        $request = new EventoRequest(
            tipoEvento: '101101',
            chaveNfse: '12345',
            dataEvento: '2026-05-15',
            versaoAplicacao: '1.0.0',
            tipoAmbiente: '2',
            cnpjAutor: '12345678000195',
            codigoMotivo: '1',
            nSeqEvento: '1',
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Chave da NFSe deve ter exatamente 50 dígitos numéricos');

        $this->validator->validate($request);
    }

    public function test_validate_invalid_tipo_evento(): void
    {
        $request = new EventoRequest(
            tipoEvento: '999999',
            chaveNfse: self::CHAVE_50,
            dataEvento: '2026-05-15',
            versaoAplicacao: '1.0.0',
            tipoAmbiente: '2',
            cnpjAutor: '12345678000195',
            nSeqEvento: '1',
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Tipo de evento inválido: 999999');

        $this->validator->validate($request);
    }
}
