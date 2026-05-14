<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Response\Evento;

class EventoResponseParser
{
    private const EVENTO_CANCELAMENTO = '101101';
    private const EVENTO_SUBSTITUICAO = '105102';
    private const EVENTO_MANIFESTACAO = ['202201', '203202', '204203', '205204', '202205', '203206', '204207', '205208'];

    /**
     * @param array<string, mixed> $dados Array cru da API
     * @return EventoResponseInterface
     */
    public function parse(array $dados): EventoResponseInterface
    {
        $tipoEvento = $dados['tipoEvento'] ?? $dados['tipo_evento'] ?? '';
        $chaveNfse = $dados['chaveNfse'] ?? $dados['chave_nfse'] ?? $dados['chaveAcesso'] ?? '';

        return match ($tipoEvento) {
            self::EVENTO_CANCELAMENTO => $this->parseCancelamento($dados, $chaveNfse),
            self::EVENTO_SUBSTITUICAO => $this->parseSubstituicao($dados, $chaveNfse),
            default => $this->parseGenerico($dados, $chaveNfse, $tipoEvento),
        };
    }

    /**
     * @param array<string, mixed> $dados
     * @param string $chaveNfse
     * @return CancelamentoResponse
     */
    private function parseCancelamento(array $dados, string $chaveNfse): CancelamentoResponse
    {
        return new CancelamentoResponse(
            chaveNfse: $chaveNfse,
            tipoEvento: self::EVENTO_CANCELAMENTO,
            dataRegistro: $dados['dataRegistro'] ?? $dados['data_registro'] ?? null,
            numeroEvento: $dados['numeroEvento'] ?? $dados['numero_evento'] ?? null,
            sucesso: $dados['sucesso'] ?? $dados['success'] ?? true,
            mensagem: $dados['mensagem'] ?? $dados['message'] ?? null,
            codigoMotivo: $dados['codigoMotivo'] ?? $dados['codigo_motivo'] ?? null,
            descricaoMotivo: $dados['descricaoMotivo'] ?? $dados['descricao_motivo'] ?? null,
        );
    }

    /**
     * @param array<string, mixed> $dados
     * @param string $chaveNfse
     * @return SubstituicaoResponse
     */
    private function parseSubstituicao(array $dados, string $chaveNfse): SubstituicaoResponse
    {
        return new SubstituicaoResponse(
            chaveNfse: $chaveNfse,
            tipoEvento: self::EVENTO_SUBSTITUICAO,
            dataRegistro: $dados['dataRegistro'] ?? $dados['data_registro'] ?? null,
            numeroEvento: $dados['numeroEvento'] ?? $dados['numero_evento'] ?? null,
            sucesso: $dados['sucesso'] ?? $dados['success'] ?? true,
            mensagem: $dados['mensagem'] ?? $dados['message'] ?? null,
            codigoMotivo: $dados['codigoMotivo'] ?? $dados['codigo_motivo'] ?? null,
            descricaoMotivo: $dados['descricaoMotivo'] ?? $dados['descricao_motivo'] ?? null,
            chaveSubstituta: $dados['chaveSubstituta'] ?? $dados['chave_substituta'] ?? null,
        );
    }

    /**
     * @param array<string, mixed> $dados
     * @param string $chaveNfse
     * @param string $tipoEvento
     * @return EventoResponseInterface
     */
    private function parseGenerico(array $dados, string $chaveNfse, string $tipoEvento): EventoResponseInterface
    {
        if (in_array($tipoEvento, self::EVENTO_MANIFESTACAO, true)) {
            return new ManifestacaoResponse(
                chaveNfse: $chaveNfse,
                tipoEvento: $tipoEvento,
                dataRegistro: $dados['dataRegistro'] ?? $dados['data_registro'] ?? null,
                numeroEvento: $dados['numeroEvento'] ?? $dados['numero_evento'] ?? null,
                sucesso: $dados['sucesso'] ?? $dados['success'] ?? true,
                mensagem: $dados['mensagem'] ?? $dados['message'] ?? null,
                autor: $dados['autor'] ?? null,
                codigoMotivo: $dados['codigoMotivo'] ?? $dados['codigo_motivo'] ?? null,
                descricaoMotivo: $dados['descricaoMotivo'] ?? $dados['descricao_motivo'] ?? null,
            );
        }

        return new GenericEventoResponse(
            chaveNfse: $chaveNfse,
            tipoEvento: $tipoEvento,
            dataRegistro: $dados['dataRegistro'] ?? $dados['data_registro'] ?? null,
            numeroEvento: $dados['numeroEvento'] ?? $dados['numero_evento'] ?? null,
            sucesso: $dados['sucesso'] ?? $dados['success'] ?? true,
            mensagem: $dados['mensagem'] ?? $dados['message'] ?? null,
            dadosAdicionais: $dados,
        );
    }

    /**
     * @param array<string, mixed> $response Resposta completa da API
     * @return EventoResponseInterface[]
     */
    public function parseLista(array $response): array
    {
        $eventos = $response['eventos'] ?? $response['data'] ?? [];
        $result = [];

        foreach ($eventos as $evento) {
            $result[] = $this->parse($evento);
        }

        return $result;
    }
}
