<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\ValueObject;

use MarcelaBeh\EmissorNfseNacional\Domain\Exception\InvalidChaveAcessoException;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\ChaveAcesso;
use PHPUnit\Framework\TestCase;

final class ChaveAcessoTest extends TestCase
{
    private const VALID_CHAVE = '35230511444777000161550010000012341000012345678901';

    public function test_valid_chave_acesso(): void
    {
        $chave = new ChaveAcesso(self::VALID_CHAVE);
        $this->assertSame(self::VALID_CHAVE, $chave->getChave());
    }

    public function test_invalid_length_throws_exception(): void
    {
        $this->expectException(InvalidChaveAcessoException::class);
        new ChaveAcesso('1234567890123456789012345678901234567890123456789');
    }

    public function test_short_chave_throws_exception(): void
    {
        $this->expectException(InvalidChaveAcessoException::class);
        new ChaveAcesso('1234567890');
    }

    public function test_formatada(): void
    {
        $chave = new ChaveAcesso('12345678901234567890123456789012345678901234567890');
        $this->assertSame('1234 5678 9012 3456 7890 1234 5678 9012 3456 7890 1234 5678 90', $chave->formatada());
    }

    public function test_to_string(): void
    {
        $chave = new ChaveAcesso(self::VALID_CHAVE);
        $this->assertSame(self::VALID_CHAVE, (string) $chave);
    }

    // ===== inscrição federal alfanumérica (NT 009, item 2.1) =====

    public function test_chave_com_cnpj_alfanumerico_nas_posicoes_10_a_23(): void
    {
        // município (7) + ambiente (1) + tipo de inscrição (1) + CNPJ (14) + 27 dígitos
        $valor = '3550308' . '2' . '2' . '12ABC34501DE35' . str_repeat('1', 27);

        $chave = new ChaveAcesso($valor);

        $this->assertSame($valor, $chave->getChave());
        $this->assertTrue(ChaveAcesso::isValida($valor));
    }

    public function test_formato_impresso_em_blocos_e_aceito(): void
    {
        $chave = new ChaveAcesso(self::VALID_CHAVE);

        $this->assertSame(self::VALID_CHAVE, (new ChaveAcesso($chave->formatada()))->getChave());
    }

    public function test_letras_em_minusculas_sao_normalizadas(): void
    {
        $valor = '3550308' . '2' . '2' . '12ABC34501DE35' . str_repeat('1', 27);

        $this->assertSame($valor, (new ChaveAcesso(strtolower($valor)))->getChave());
    }

    public function test_letra_fora_da_inscricao_federal_lanca_excecao(): void
    {
        // Letra na posição 1 (município): só as posições 10 a 23 aceitam letras.
        $this->expectException(InvalidChaveAcessoException::class);
        $this->expectExceptionMessage('apenas as posições 10 a 23');

        new ChaveAcesso('A' . substr(self::VALID_CHAVE, 1));
    }

    public function test_letra_depois_da_inscricao_federal_lanca_excecao(): void
    {
        $this->expectException(InvalidChaveAcessoException::class);

        new ChaveAcesso(substr(self::VALID_CHAVE, 0, 49) . 'X');
    }

    public function test_e_valida_rejeita_tamanho_errado(): void
    {
        $this->assertFalse(ChaveAcesso::isValida('123'));
        $this->assertFalse(ChaveAcesso::isValida(self::VALID_CHAVE . '1'));
    }

    public function test_do_id_nfse_extrai_a_chave_do_id(): void
    {
        $this->assertSame(self::VALID_CHAVE, ChaveAcesso::doIdNfse('NFS' . self::VALID_CHAVE));
    }

    public function test_do_id_nfse_aceita_inscricao_alfanumerica(): void
    {
        $chave = '355030821' . '12ABC34501DE35' . substr(self::VALID_CHAVE, 23);

        $this->assertSame($chave, ChaveAcesso::doIdNfse('NFS' . $chave));
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function idsInvalidos(): array
    {
        return [
            'sem prefixo' => [self::VALID_CHAVE],
            'prefixo errado' => ['DPS' . self::VALID_CHAVE],
            'curto' => ['NFS' . substr(self::VALID_CHAVE, 1)],
            'longo' => ['NFS' . self::VALID_CHAVE . '1'],
            'letra fora da inscricao' => ['NFS' . 'A' . substr(self::VALID_CHAVE, 1)],
            'nulo' => [null],
            'nao e texto' => [123],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('idsInvalidos')]
    public function test_do_id_nfse_devolve_null_para_id_invalido(mixed $id): void
    {
        $this->assertNull(ChaveAcesso::doIdNfse($id));
    }

    public function test_pontuacao_nao_e_descartada_silenciosamente(): void
    {
        // Antes tudo que não fosse dígito era removido, mascarando chaves malformadas.
        $this->expectException(InvalidChaveAcessoException::class);

        new ChaveAcesso(substr(self::VALID_CHAVE, 0, 20) . '-' . substr(self::VALID_CHAVE, 21));
    }
}
