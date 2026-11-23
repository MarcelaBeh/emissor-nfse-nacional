<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Infrastructure\Security;

use MarcelaBeh\EmissorNfseNacional\Infrastructure\Security\SensitiveDataSanitizer;
use PHPUnit\Framework\TestCase;

final class SensitiveDataSanitizerTest extends TestCase
{
    private SensitiveDataSanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = new SensitiveDataSanitizer();
    }

    public function test_sanitiza_cpf_sem_mascara(): void
    {
        $result = $this->sanitizer->sanitize('CPF: 12345678901');
        $this->assertStringNotContainsString('12345678901', $result);
        $this->assertStringContainsString('***********', $result);
    }

    public function test_sanitiza_cpf_com_mascara(): void
    {
        $result = $this->sanitizer->sanitize('CPF: 123.456.789-01');
        $this->assertStringNotContainsString('123.456.789-01', $result);
        $this->assertStringContainsString('***.***.***-**', $result);
    }

    public function test_sanitiza_cnpj_sem_mascara(): void
    {
        $result = $this->sanitizer->sanitize('CNPJ: 12345678000195');
        $this->assertStringNotContainsString('12345678000195', $result);
        $this->assertStringContainsString('**************', $result);
    }

    public function test_sanitiza_cnpj_com_mascara(): void
    {
        $result = $this->sanitizer->sanitize('CNPJ: 12.345.678/0001-95');
        $this->assertStringNotContainsString('12.345.678/0001-95', $result);
        $this->assertStringContainsString('**.***.***/****-**', $result);
    }

    public function test_sanitiza_email(): void
    {
        $result = $this->sanitizer->sanitize('email: usuario@empresa.com.br');
        $this->assertStringNotContainsString('usuario@empresa.com.br', $result);
        $this->assertStringContainsString('***@***', $result);
    }

    public function test_sanitiza_chave_acesso_50_digitos(): void
    {
        $chave = str_repeat('1', 50);
        $result = $this->sanitizer->sanitize("chave: {$chave}");
        $this->assertStringNotContainsString($chave, $result);
        $this->assertStringContainsString('**************************************************', $result);
    }

    public function test_redact_chave_sensivel_em_array(): void
    {
        $data = ['usuario' => 'joao', 'senha' => 'segredo123', 'token' => 'abc'];
        $result = $this->sanitizer->sanitize($data);
        $this->assertSame('joao', $result['usuario']);
        $this->assertSame('[REDACTED]', $result['senha']);
        $this->assertSame('[REDACTED]', $result['token']);
    }

    public function test_redact_chaves_insensivel_ao_case(): void
    {
        $data = ['Password' => 'abc', 'SENHA' => 'xyz', 'ApiKey' => '123'];
        $result = $this->sanitizer->sanitize($data);
        $this->assertSame('[REDACTED]', $result['Password']);
        $this->assertSame('[REDACTED]', $result['SENHA']);
        $this->assertSame('[REDACTED]', $result['ApiKey']);
    }

    public function test_nao_altera_texto_sem_dados_sensiveis(): void
    {
        $text = 'Erro genérico de conexão com a API';
        $this->assertSame($text, $this->sanitizer->sanitize($text));
    }

    public function test_retorna_inteiro_sem_alteracao(): void
    {
        $this->assertSame(42, $this->sanitizer->sanitize(42));
    }

    public function test_sanitiza_array_aninhado(): void
    {
        $data = ['prestador' => ['cnpj' => '12345678000195', 'nome' => 'Empresa']];
        $result = $this->sanitizer->sanitize($data);
        $this->assertStringNotContainsString('12345678000195', (string) $result['prestador']['cnpj']);
    }

    // ===== CNPJ alfanumérico (NT 009): não pode vazar nos logs =====

    public function test_sanitiza_cnpj_alfanumerico_sem_mascara(): void
    {
        $result = $this->sanitizer->sanitize('CNPJ: 12ABC34501DE35');

        $this->assertStringNotContainsString('12ABC34501DE35', $result);
        $this->assertStringContainsString('**************', $result);
    }

    public function test_sanitiza_cnpj_alfanumerico_com_mascara(): void
    {
        $result = $this->sanitizer->sanitize('CNPJ: 12.ABC.345/01DE-35');

        $this->assertStringNotContainsString('12.ABC.345/01DE-35', $result);
        $this->assertStringContainsString('**.***.***/****-**', $result);
    }

    public function test_sanitiza_cnpj_alfanumerico_em_tag_xml(): void
    {
        $result = $this->sanitizer->sanitize('<CNPJ>12ABC34501DE35</CNPJ>');

        $this->assertStringNotContainsString('12ABC34501DE35', $result);
    }

    public function test_sanitiza_chave_de_acesso_com_cnpj_alfanumerico(): void
    {
        $chave = '3550308' . '2' . '2' . '12ABC34501DE35' . str_repeat('1', 27);
        $result = $this->sanitizer->sanitize("chave: {$chave}");

        $this->assertStringNotContainsString($chave, $result);
        $this->assertStringNotContainsString('12ABC34501DE35', $result);
    }

    public function test_nao_mascara_palavras_maiusculas_comuns(): void
    {
        // Sem dígitos nem 14 posições exatas, texto técnico em maiúsculas não deve ser tratado como CNPJ.
        $texto = 'ERRO NA VALIDACAO DO CERTIFICADO ICPBRASIL';

        $this->assertSame($texto, $this->sanitizer->sanitize($texto));
    }
}
