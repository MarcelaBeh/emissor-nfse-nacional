<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Enum;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoAjusteBC;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoAjusteBCLocImoveis;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TipoAjusteBCTest extends TestCase
{
    public function test_dominio_oficial_do_tsrtctpajustebc(): void
    {
        $this->assertSame(
            ['2', '5', '6', '7', '8', '9', '99', '101', '102', '103', '104', '105', '199'],
            TipoAjusteBC::valores()
        );
    }

    public function test_tipos_extintos_nao_existem(): void
    {
        foreach (['1', '3', '4'] as $extinto) {
            $this->assertNull(TipoAjusteBC::tryFrom($extinto), "tpAjusteBC {$extinto} foi extinto na NT 009");
        }
    }

    /**
     * Espelha a planilha oficial AJUSTE_BC_REPERCUSSAO: o que cada tipo ajusta.
     *
     * @return array<string, array{string, bool, bool}>
     */
    public static function repercussao(): array
    {
        return [
            'materiais' => ['2', true, false],
            'repasse consorciado' => ['5', true, false],
            'repasse plano de saude' => ['6', true, false],
            'servicos' => ['7', true, false],
            'subempreitada' => ['8', true, false],
            'profissional parceiro' => ['9', true, false],
            'outras deducoes issqn' => ['99', true, false],
            'intermediacao de imoveis' => ['101', true, true],
            'agencia de turismo' => ['102', true, true],
            'publicidade producao externa' => ['103', true, true],
            'publicidade midia' => ['104', true, true],
            'glosa de saude' => ['105', false, true],
            'outros reembolsos' => ['199', true, true],
        ];
    }

    #[DataProvider('repercussao')]
    public function test_repercussao_nas_bases_de_calculo(string $codigo, bool $issqn, bool $ibscbs): void
    {
        $tipo = TipoAjusteBC::from($codigo);

        $this->assertSame($issqn, $tipo->ajustaBaseIssqn());
        $this->assertSame($ibscbs, $tipo->ajustaBaseIbsCbs());
    }

    #[DataProvider('repercussao')]
    public function test_exclusivo_issqn_e_o_que_nao_ajusta_ibscbs(string $codigo, bool $issqn, bool $ibscbs): void
    {
        $this->assertSame(!$ibscbs, TipoAjusteBC::from($codigo)->exclusivoIssqn());
    }

    public function test_apenas_99_e_199_exigem_descricao(): void
    {
        foreach (TipoAjusteBC::cases() as $tipo) {
            $esperado = in_array($tipo->value, ['99', '199'], true);
            $this->assertSame($esperado, $tipo->exigeDescricao(), "tpAjusteBC {$tipo->value}");
        }
    }

    public function test_todo_tipo_tem_descricao(): void
    {
        foreach (TipoAjusteBC::cases() as $tipo) {
            $this->assertNotSame('', $tipo->descricao());
        }
    }

    public function test_dominio_de_parcelas_de_locacao(): void
    {
        $this->assertSame(['01', '02', '03', '04', '99'], TipoAjusteBCLocImoveis::valores());
        $this->assertTrue(TipoAjusteBCLocImoveis::OUTRAS_PARCELAS->exigeDescricao());
        $this->assertFalse(TipoAjusteBCLocImoveis::REDUTOR_SOCIAL->exigeDescricao());
    }
}
