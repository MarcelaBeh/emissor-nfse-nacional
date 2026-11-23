<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Infrastructure\Config;

class ApiEndpoints
{
    private Configuration $config;

    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    public function consultarNfse(string $chave): string
    {
        return str_replace('{chave}', $chave, $this->config->getOperation('consultar_nfse'));
    }

    public function consultarDps(string $id): string
    {
        return str_replace('{id}', $id, $this->config->getOperation('consultar_dps'));
    }

    /** A SEFIN só tem a consulta com tipo e sequencial: `GET .../eventos` sem eles responde 405. */
    public function consultarEventos(string $chave, string $tipoEvento, int $nSequencial): string
    {
        return str_replace(
            ['{chave}', '{tipoEvento}', '{nSequencial}'],
            [$chave, $tipoEvento, (string) $nSequencial],
            $this->config->getOperation('consultar_eventos')
        );
    }

    public function emitirNfse(): string
    {
        return $this->config->getOperation('emitir_nfse');
    }

    public function cancelarNfse(string $chave): string
    {
        return str_replace('{chave}', $chave, $this->config->getOperation('cancelar_nfse'));
    }

    public function verificarDps(string $id): string
    {
        return str_replace('{id}', $id, $this->config->getOperation('verificar_dps'));
    }

    public function decisaoJudicialNfse(): string
    {
        return $this->config->getOperation('decisao_judicial_nfse');
    }
}
