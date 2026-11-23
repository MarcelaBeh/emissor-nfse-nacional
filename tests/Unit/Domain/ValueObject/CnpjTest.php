<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\ValueObject;

use MarcelaBeh\EmissorNfseNacional\Domain\Exception\InvalidCnpjException;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cnpj;
use PHPUnit\Framework\TestCase;

final class CnpjTest extends TestCase
{
    public function test_valid_cnpj(): void
    {
        $cnpj = new Cnpj('11444777000161');
        $this->assertSame('11444777000161', $cnpj->getNumero());
    }

    public function test_valid_cnpj_with_mask(): void
    {
        $cnpj = new Cnpj('11.444.777/0001-61');
        $this->assertSame('11444777000161', $cnpj->getNumero());
    }

    public function test_invalid_cnpj_throws_exception(): void
    {
        $this->expectException(InvalidCnpjException::class);
        new Cnpj('11444777000100');
    }

    public function test_short_cnpj_throws_exception(): void
    {
        $this->expectException(InvalidCnpjException::class);
        new Cnpj('1234567890123');
    }

    public function test_repeated_digits_throws_exception(): void
    {
        $this->expectException(InvalidCnpjException::class);
        new Cnpj('00000000000000');
    }

    public function test_formatado(): void
    {
        $cnpj = new Cnpj('11444777000161');
        $this->assertSame('11.444.777/0001-61', $cnpj->formatado());
    }

    public function test_equals(): void
    {
        $a = new Cnpj('11444777000161');
        $b = new Cnpj('11444777000161');
        $c = new Cnpj('33444555000181');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function test_to_string(): void
    {
        $cnpj = new Cnpj('11444777000161');
        $this->assertSame('11444777000161', (string) $cnpj);
    }

    // ===== CNPJ alfanumérico (NT 009, item 2.1) =====

    public function test_cnpj_alfanumerico_do_exemplo_oficial_da_receita(): void
    {
        $cnpj = new Cnpj('12.ABC.345/01DE-35');

        $this->assertSame('12ABC34501DE35', $cnpj->getNumero());
        $this->assertTrue($cnpj->isAlfanumerico());
        $this->assertSame('12.ABC.345/01DE-35', $cnpj->formatado());
    }

    public function test_cnpj_numerico_nao_e_alfanumerico(): void
    {
        $this->assertFalse((new Cnpj('11444777000161'))->isAlfanumerico());
    }

    public function test_letras_minusculas_sao_normalizadas_para_maiusculas(): void
    {
        $this->assertSame('12ABC34501DE35', (new Cnpj('12abc34501de35'))->getNumero());
    }

    public function test_cnpj_alfanumerico_com_dv_errado_lanca_excecao(): void
    {
        $this->expectException(InvalidCnpjException::class);
        $this->expectExceptionMessage('dígito verificador');

        new Cnpj('12ABC34501DE36');
    }

    public function test_dv_nao_numerico_lanca_excecao(): void
    {
        $this->expectException(InvalidCnpjException::class);
        $this->expectExceptionMessage('dígitos verificadores são numéricos');

        new Cnpj('12ABC34501DEAB');
    }

    public function test_letras_nao_sao_descartadas_silenciosamente(): void
    {
        // Antes qualquer caractere não numérico era removido e o erro virava "deve ter 14 dígitos. Fornecido: 9".
        $this->expectException(InvalidCnpjException::class);
        $this->expectExceptionMessage('14 caracteres. Fornecido: 13');

        new Cnpj('12ABC34501DE3');
    }

    public function test_caractere_fora_de_a_z_e_0_a_9_lanca_excecao(): void
    {
        $this->expectException(InvalidCnpjException::class);

        new Cnpj('12ABC34501D$35');
    }

    public function test_is_valido(): void
    {
        $this->assertTrue(Cnpj::isValido('12.ABC.345/01DE-35'));
        $this->assertTrue(Cnpj::isValido('11.444.777/0001-61'));
        $this->assertFalse(Cnpj::isValido('12ABC34501DE36'));
        $this->assertFalse(Cnpj::isValido(''));
    }

    public function test_equals_considera_a_normalizacao(): void
    {
        $this->assertTrue((new Cnpj('12.ABC.345/01DE-35'))->equals(new Cnpj('12abc34501de35')));
    }
}
