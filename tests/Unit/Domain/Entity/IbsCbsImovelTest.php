<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsEnderecoImovel;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsImovel;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsLocacaoImovel;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsUnidadeImobiliaria;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoCIB;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoMunicipio;
use PHPUnit\Framework\TestCase;

final class IbsCbsImovelTest extends TestCase
{
    public function test_create_with_municipio_only(): void
    {
        $imovel = new IbsCbsImovel(cMun: new CodigoMunicipio('3550308'));

        $this->assertSame('3550308', $imovel->getCMun()->getCodigo());
        $this->assertNull($imovel->getLocacao());
        $this->assertSame([], $imovel->getUnidades());
    }

    public function test_create_with_locacao_e_unidades(): void
    {
        $locacao = new IbsCbsLocacaoImovel(
            pCopropriedade: '50.00',
            vTotOper: '2000.00',
            vDescIncondTot: '100.00',
            vDescCondTot: '50.00',
            dVencOrig: new \DateTimeImmutable('2026-12-10'),
        );
        $unidade = new IbsCbsUnidadeImobiliaria(inscImobFisc: '12345', cCIB: new CodigoCIB('12345678'));

        $imovel = new IbsCbsImovel(
            cMun: new CodigoMunicipio('3550308'),
            locacao: $locacao,
            unidades: [$unidade],
        );

        $this->assertSame($locacao, $imovel->getLocacao());
        $this->assertSame([$unidade], $imovel->getUnidades());
        $this->assertSame('50.00', $imovel->getLocacao()->getPCopropriedade());
        $this->assertSame('2026-12-10', $imovel->getLocacao()->getDVencOrig()?->format('Y-m-d'));
    }

    public function test_aceita_ate_99_unidades(): void
    {
        $unidades = array_fill(0, 99, new IbsCbsUnidadeImobiliaria(cCIB: new CodigoCIB('12345678')));

        $imovel = new IbsCbsImovel(cMun: new CodigoMunicipio('3550308'), unidades: $unidades);

        $this->assertCount(99, $imovel->getUnidades());
    }

    public function test_mais_de_99_unidades_lanca_excecao(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no máximo 99');

        new IbsCbsImovel(
            cMun: new CodigoMunicipio('3550308'),
            unidades: array_fill(0, 100, new IbsCbsUnidadeImobiliaria(cCIB: new CodigoCIB('12345678'))),
        );
    }

    public function test_unidade_por_cib(): void
    {
        $unidade = new IbsCbsUnidadeImobiliaria(inscImobFisc: '12345', cCIB: new CodigoCIB('12345678'));

        $this->assertSame('12345', $unidade->getInscImobFisc());
        $this->assertSame('12345678', $unidade->getCCIB()?->getCodigo());
        $this->assertNull($unidade->getEndereco());
        $this->assertSame([], $unidade->getAjustes());
    }

    public function test_unidade_por_endereco_com_complemento_e_bairro_opcionais(): void
    {
        $endereco = new IbsCbsEnderecoImovel(cep: '01001000', xLgr: 'Rua A', nro: '10');
        $unidade = new IbsCbsUnidadeImobiliaria(endereco: $endereco);

        $this->assertSame($endereco, $unidade->getEndereco());
        $this->assertNull($unidade->getCCIB());
        $this->assertNull($endereco->getXCpl());
        $this->assertNull($endereco->getXBairro());
    }

    public function test_unidade_sem_cib_nem_endereco_lanca_excecao(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('exatamente um');

        new IbsCbsUnidadeImobiliaria();
    }

    public function test_unidade_com_cib_e_endereco_lanca_excecao(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('exatamente um');

        new IbsCbsUnidadeImobiliaria(
            cCIB: new CodigoCIB('12345678'),
            endereco: new IbsCbsEnderecoImovel(cep: '01001000', xLgr: 'Rua A', nro: '10'),
        );
    }
}
