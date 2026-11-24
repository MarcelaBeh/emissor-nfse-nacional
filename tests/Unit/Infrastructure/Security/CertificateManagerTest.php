<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Infrastructure\Security;

use MarcelaBeh\EmissorNfseNacional\Infrastructure\Security\CertificateManager;
use NFePHP\Common\Certificate;
use PHPUnit\Framework\TestCase;

final class CertificateManagerTest extends TestCase
{
    /** @var list<string> diretórios criados pelo manager, removidos ao fim de cada teste */
    private array $criados = [];

    protected function tearDown(): void
    {
        foreach ($this->criados as $dir) {
            @rmdir($dir);
            @rmdir(dirname($dir));
        }
        $this->criados = [];
    }

    private function certificado(?string $cnpj, ?string $cpf = null): Certificate
    {
        return new class ($cnpj, $cpf) extends Certificate {
            public function __construct(private ?string $cnpjFalso, private ?string $cpfFalso)
            {
            }

            public function isExpired()
            {
                return false;
            }

            public function getCnpj()
            {
                return $this->cnpjFalso;
            }

            public function getCpf()
            {
                return $this->cpfFalso;
            }
        };
    }

    private function diretorioDe(?string $cnpj, ?string $cpf = null): string
    {
        $dir = (new CertificateManager($this->certificado($cnpj, $cpf)))->getTempDir();
        $this->criados[] = rtrim($dir, '/\\');

        return str_replace('\\', '/', $dir);
    }

    public function test_cnpj_alfanumerico_vira_nome_de_diretorio(): void
    {
        $this->assertStringEndsWith('/9WS28PHA000134/certs/', $this->diretorioDe('9WS28PHA000134'));
    }

    public function test_cpf_e_usado_quando_nao_ha_cnpj(): void
    {
        $this->assertStringEndsWith('/12345678909/certs/', $this->diretorioDe(null, '12345678909'));
    }

    public function test_documento_com_caracteres_de_caminho_nao_escapa_do_diretorio_temporario(): void
    {
        $dir = $this->diretorioDe('../../etc/9W.S28/PHA');

        $this->assertStringEndsWith('/etc9WS28PHA/certs/', $dir);
        $this->assertStringNotContainsString('..', $dir);
    }

    public function test_sem_documento_usa_nome_fixo(): void
    {
        $this->assertStringEndsWith('/sem-documento/certs/', $this->diretorioDe(null, null));
    }
}
