<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\AtvEvento;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Endereco;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cep;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoMunicipio;
use PHPUnit\Framework\TestCase;

final class AtvEventoTest extends TestCase
{
    public function test_create_with_required_fields(): void
    {
        $ae = new AtvEvento(
            descricao: 'Feira Tecnológica',
            dataInicio: new \DateTimeImmutable('2026-06-01'),
            dataFim: new \DateTimeImmutable('2026-06-10'),
        );

        $this->assertSame('Feira Tecnológica', $ae->getDescricao());
        $this->assertEquals(new \DateTimeImmutable('2026-06-01'), $ae->getDataInicio());
        $this->assertEquals(new \DateTimeImmutable('2026-06-10'), $ae->getDataFim());
        $this->assertNull($ae->getIdentificacaoEvento());
        $this->assertNull($ae->getEndereco());
    }

    public function test_create_with_all_fields(): void
    {
        $endereco = new Endereco(
            logradouro: 'Av. Paulista',
            numero: '1000',
            complemento: null,
            bairro: 'Bela Vista',
            codigoMunicipio: new CodigoMunicipio('3550308'),
            uf: 'SP',
            cep: new Cep('01310000'),
        );

        $ae = new AtvEvento(
            descricao: 'Conferência Anual',
            dataInicio: new \DateTimeImmutable('2026-07-15'),
            dataFim: new \DateTimeImmutable('2026-07-20'),
            identificacaoEvento: 'CONF-2026-001',
            endereco: $endereco,
        );

        $this->assertSame('Conferência Anual', $ae->getDescricao());
        $this->assertSame('CONF-2026-001', $ae->getIdentificacaoEvento());
        $this->assertSame($endereco, $ae->getEndereco());
        $this->assertSame('Av. Paulista', $ae->getEndereco()->getLogradouro());
    }

    public function test_same_date_inicio_fim(): void
    {
        $ae = new AtvEvento(
            descricao: 'Evento de um dia',
            dataInicio: new \DateTimeImmutable('2026-08-01'),
            dataFim: new \DateTimeImmutable('2026-08-01'),
        );

        $this->assertEquals(
            $ae->getDataInicio()->format('Y-m-d'),
            $ae->getDataFim()->format('Y-m-d'),
        );
    }
}
