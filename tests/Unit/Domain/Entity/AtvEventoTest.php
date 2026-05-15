<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\AtvEvento;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsEnderecoObra;
use PHPUnit\Framework\TestCase;

final class AtvEventoTest extends TestCase
{
    public function test_create_with_identificacao(): void
    {
        $ae = new AtvEvento(
            descricao: 'Feira Tecnológica',
            dataInicio: new \DateTimeImmutable('2026-06-01'),
            dataFim: new \DateTimeImmutable('2026-06-10'),
            identificacaoEvento: 'EVT-2026-001',
        );

        $this->assertSame('Feira Tecnológica', $ae->getDescricao());
        $this->assertEquals(new \DateTimeImmutable('2026-06-01'), $ae->getDataInicio());
        $this->assertEquals(new \DateTimeImmutable('2026-06-10'), $ae->getDataFim());
        $this->assertSame('EVT-2026-001', $ae->getIdentificacaoEvento());
        $this->assertNull($ae->getEndereco());
        $this->assertTrue($ae->isPorIdentificacao());
    }

    public function test_create_with_endereco(): void
    {
        $endereco = new IbsCbsEnderecoObra(
            cep: '01310000',
            endExt: null,
            xLgr: 'Av. Paulista',
            nro: '1000',
            xBairro: 'Bela Vista',
        );

        $ae = new AtvEvento(
            descricao: 'Conferência Anual',
            dataInicio: new \DateTimeImmutable('2026-07-15'),
            dataFim: new \DateTimeImmutable('2026-07-20'),
            endereco: $endereco,
        );

        $this->assertSame('Conferência Anual', $ae->getDescricao());
        $this->assertNull($ae->getIdentificacaoEvento());
        $this->assertSame($endereco, $ae->getEndereco());
        $this->assertSame('Av. Paulista', $ae->getEndereco()->getXLgr());
        $this->assertTrue($ae->isPorEndereco());
    }

    public function test_same_date_inicio_fim(): void
    {
        $ae = new AtvEvento(
            descricao: 'Evento de um dia',
            dataInicio: new \DateTimeImmutable('2026-08-01'),
            dataFim: new \DateTimeImmutable('2026-08-01'),
            identificacaoEvento: 'EVT-001',
        );

        $this->assertEquals(
            $ae->getDataInicio()->format('Y-m-d'),
            $ae->getDataFim()->format('Y-m-d'),
        );
    }

    public function test_create_without_choice_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Atividade/Evento deve informar exatamente um dos campos: identificacaoEvento ou endereco');
        new AtvEvento(
            descricao: 'Evento',
            dataInicio: new \DateTimeImmutable('2026-01-01'),
            dataFim: new \DateTimeImmutable('2026-01-02'),
        );
    }
}
