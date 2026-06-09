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
            tipoAmbiente: 2,
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
            tipoAmbiente: 2,
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
            tipoAmbiente: 2,
            cnpjAutor: '12345678000195',
            nSeqEvento: '1',
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Tipo de evento inválido: 999999');

        $this->validator->validate($request);
    }

    public function test_descricao_motivo_abaixo_de_15_caracteres_throws(): void
    {
        // xMotivo é TSMotivo: minLength 15. Descrição curta deve ser pega antes do schemaValidate.
        $request = new EventoRequest(
            tipoEvento: '101101',
            chaveNfse: self::CHAVE_50,
            dataEvento: '2026-05-15',
            versaoAplicacao: '1.0.0',
            tipoAmbiente: 2,
            cnpjAutor: '12345678000195',
            codigoMotivo: '1',
            descricaoMotivo: 'Erro',
            nSeqEvento: '1',
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Descrição do motivo (xMotivo) deve ter entre 15 e 255 caracteres (TSMotivo)');

        $this->validator->validate($request);
    }

    public function test_cancelamento_sem_descricao_throws_para_qualquer_codigo(): void
    {
        // Cancelamento (TE101101): xMotivo é minOccurs=1 no XSD → sempre obrigatório,
        // mesmo com código 1 (não só no "Outros"). Provado por schemaValidate.
        $request = new EventoRequest(
            tipoEvento: '101101',
            chaveNfse: self::CHAVE_50,
            dataEvento: '2026-05-15',
            versaoAplicacao: '1.0.0',
            tipoAmbiente: 2,
            cnpjAutor: '12345678000195',
            codigoMotivo: '1',
            nSeqEvento: '1',
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Descrição do motivo (xMotivo) é obrigatória para este tipo de evento');

        $this->validator->validate($request);
    }

    public function test_substituicao_sem_descricao_passa_para_qualquer_codigo(): void
    {
        // Substituição (TE105102): xMotivo é minOccurs=0 no XSD → opcional para qualquer código.
        foreach (['01', '99'] as $codigo) {
            $request = new EventoRequest(
                tipoEvento: '105102',
                chaveNfse: self::CHAVE_50,
                dataEvento: '2026-05-15',
                versaoAplicacao: '1.0.0',
                tipoAmbiente: 2,
                cnpjAutor: '12345678000195',
                codigoMotivo: $codigo,
                chSubstituta: '99345678901234567890123456789012345678901234567890',
                nSeqEvento: '1',
            );

            $this->validator->validate($request);
        }

        $this->assertTrue(true);
    }

    public function test_descricao_motivo_com_15_ou_mais_caracteres_passa(): void
    {
        $request = new EventoRequest(
            tipoEvento: '101101',
            chaveNfse: self::CHAVE_50,
            dataEvento: '2026-05-15',
            versaoAplicacao: '1.0.0',
            tipoAmbiente: 2,
            cnpjAutor: '12345678000195',
            codigoMotivo: '1',
            descricaoMotivo: 'Erro na emissao da nota',
            nSeqEvento: '1',
        );

        $this->validator->validate($request);

        $this->assertTrue(true);
    }

    public function test_cancelamento_oficio_sem_xprocadm_throws(): void
    {
        // TE305101: xProcAdm é minOccurs=1 no XSD. A lib gera esse evento, então deve exigi-lo.
        $request = new EventoRequest(
            tipoEvento: '305101',
            chaveNfse: self::CHAVE_50,
            dataEvento: '2026-05-15',
            versaoAplicacao: '1.0.0',
            tipoAmbiente: 2,
            cpfAutor: '52998224725',
            cpfAgTrib: '52998224725',
            nProcAdm: '123',
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('xProcAdm) é obrigatória');

        $this->validator->validate($request);
    }

    public function test_anulacao_rejeicao_sem_id_ev_manif_rej_throws(): void
    {
        // TE205208: idEvManifRej é minOccurs=1 no XSD.
        $request = new EventoRequest(
            tipoEvento: '205208',
            chaveNfse: self::CHAVE_50,
            dataEvento: '2026-05-15',
            versaoAplicacao: '1.0.0',
            tipoAmbiente: 2,
            cpfAutor: '52998224725',
            cpfAgTrib: '52998224725',
            descricaoMotivo: 'Anulacao de rejeicao indevida',
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('idEvManifRej');

        $this->validator->validate($request);
    }

    public function test_id_ev_manif_rej_formato_invalido_throws(): void
    {
        // idEvManifRej é TSIdNumEvento: [0-9]{59}.
        $request = new EventoRequest(
            tipoEvento: '205208',
            chaveNfse: self::CHAVE_50,
            dataEvento: '2026-05-15',
            versaoAplicacao: '1.0.0',
            tipoAmbiente: 2,
            cpfAutor: '52998224725',
            cpfAgTrib: '52998224725',
            descricaoMotivo: 'Anulacao de rejeicao indevida',
            idEvManifRej: '123',
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('idEvManifRej deve ter 59 dígitos numéricos (TSIdNumEvento)');

        $this->validator->validate($request);
    }

    public function test_bloqueio_oficio_cod_evento_invalido_throws(): void
    {
        // codEvento é TSCodigoEventoNFSe (enum). '1' não é válido.
        $request = new EventoRequest(
            tipoEvento: '305102',
            chaveNfse: self::CHAVE_50,
            dataEvento: '2026-05-15',
            versaoAplicacao: '1.0.0',
            tipoAmbiente: 2,
            cpfAutor: '52998224725',
            cpfAgTrib: '52998224725',
            descricaoMotivo: 'Bloqueio por ofício do fisco',
            codEventoBloqueio: '1',
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('codEvento (codEventoBloqueio) inválido');

        $this->validator->validate($request);
    }
}
