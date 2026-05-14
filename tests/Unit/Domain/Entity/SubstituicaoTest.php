<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Substituicao;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\ChaveAcesso;
use PHPUnit\Framework\TestCase;

final class SubstituicaoTest extends TestCase
{
    public function test_create_with_all_fields(): void
    {
        $chave = new ChaveAcesso('12345678901234567890123456789012345678901234567890');
        $substituicao = new Substituicao(
            chaveSubstituida: $chave,
            codigoMotivo: '01',
            descricaoMotivo: 'Erro na emissão da NFSe',
        );

        $this->assertSame($chave, $substituicao->getChaveSubstituida());
        $this->assertSame('01', $substituicao->getCodigoMotivo());
        $this->assertSame('Erro na emissão da NFSe', $substituicao->getDescricaoMotivo());
    }

    public function test_create_without_descricao(): void
    {
        $chave = new ChaveAcesso('12345678901234567890123456789012345678901234567890');
        $substituicao = new Substituicao(
            chaveSubstituida: $chave,
            codigoMotivo: '02',
        );

        $this->assertSame('02', $substituicao->getCodigoMotivo());
        $this->assertNull($substituicao->getDescricaoMotivo());
    }

    public function test_create_with_codigo_motivo_99_and_descricao(): void
    {
        $chave = new ChaveAcesso('12345678901234567890123456789012345678901234567890');
        $substituicao = new Substituicao(
            chaveSubstituida: $chave,
            codigoMotivo: '99',
            descricaoMotivo: 'Outro motivo justificado com descrição',
        );

        $this->assertSame('99', $substituicao->getCodigoMotivo());
        $this->assertSame('Outro motivo justificado com descrição', $substituicao->getDescricaoMotivo());
    }

    public function test_invalid_codigo_motivo_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Código de motivo inválido');

        new Substituicao(
            chaveSubstituida: new ChaveAcesso('12345678901234567890123456789012345678901234567890'),
            codigoMotivo: '00',
        );
    }

    public function test_codigo_motivo_99_missing_descricao_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Descrição do motivo (xMotivo) é obrigatória');

        new Substituicao(
            chaveSubstituida: new ChaveAcesso('12345678901234567890123456789012345678901234567890'),
            codigoMotivo: '99',
            descricaoMotivo: '',
        );
    }

    public function test_descricao_motivo_too_short_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Descrição do motivo (xMotivo) deve ter no mínimo 15 caracteres');

        new Substituicao(
            chaveSubstituida: new ChaveAcesso('12345678901234567890123456789012345678901234567890'),
            codigoMotivo: '99',
            descricaoMotivo: 'Curta',
        );
    }

    public function test_descricao_motivo_too_long_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Descrição do motivo (xMotivo) deve ter no máximo 255 caracteres');

        new Substituicao(
            chaveSubstituida: new ChaveAcesso('12345678901234567890123456789012345678901234567890'),
            codigoMotivo: '99',
            descricaoMotivo: str_repeat('A', 256),
        );
    }

    public function test_codigo_motivo_03_valid(): void
    {
        $chave = new ChaveAcesso('12345678901234567890123456789012345678901234567890');
        $substituicao = new Substituicao(
            chaveSubstituida: $chave,
            codigoMotivo: '03',
        );

        $this->assertSame('03', $substituicao->getCodigoMotivo());
    }

    public function test_codigo_motivo_04_valid(): void
    {
        $chave = new ChaveAcesso('12345678901234567890123456789012345678901234567890');
        $substituicao = new Substituicao(
            chaveSubstituida: $chave,
            codigoMotivo: '04',
        );

        $this->assertSame('04', $substituicao->getCodigoMotivo());
    }

    public function test_codigo_motivo_05_valid(): void
    {
        $chave = new ChaveAcesso('12345678901234567890123456789012345678901234567890');
        $substituicao = new Substituicao(
            chaveSubstituida: $chave,
            codigoMotivo: '05',
        );

        $this->assertSame('05', $substituicao->getCodigoMotivo());
    }
}
