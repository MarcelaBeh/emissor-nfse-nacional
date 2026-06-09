<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Infrastructure\Config;

use MarcelaBeh\EmissorNfseNacional\Infrastructure\Config\ApiEndpoints;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Config\Configuration;
use PHPUnit\Framework\TestCase;

final class ApiEndpointsTest extends TestCase
{
    private ApiEndpoints $endpoints;

    protected function setUp(): void
    {
        $config = new Configuration(['tpAmb' => 2, 'prefeitura' => '3550308']);
        $this->endpoints = new ApiEndpoints($config);
    }

    public function test_emitir_nfse(): void
    {
        $this->assertSame('nfse', $this->endpoints->emitirNfse());
    }

    public function test_cancelar_nfse(): void
    {
        $chave = str_repeat('1', 50);
        $this->assertSame("nfse/{$chave}/eventos", $this->endpoints->cancelarNfse($chave));
    }

    public function test_consultar_nfse(): void
    {
        $chave = str_repeat('1', 50);
        $this->assertSame("nfse/{$chave}", $this->endpoints->consultarNfse($chave));
    }

    public function test_consultar_dps(): void
    {
        $chave = str_repeat('1', 50);
        $this->assertSame("dps/{$chave}", $this->endpoints->consultarDps($chave));
    }

    public function test_verificar_dps_nao_lanca_operacao_nao_configurada(): void
    {
        // Regressão: a operação verificar_dps estava ausente da config e o método quebrava em runtime.
        $id = 'DPS' . str_repeat('1', 42);
        $this->assertSame("dps/{$id}", $this->endpoints->verificarDps($id));
    }

    public function test_decisao_judicial_nfse_nao_lanca_operacao_nao_configurada(): void
    {
        // Regressão: a operação decisao_judicial_nfse estava ausente da config.
        $this->assertSame('decisao-judicial/nfse', $this->endpoints->decisaoJudicialNfse());
    }
}
