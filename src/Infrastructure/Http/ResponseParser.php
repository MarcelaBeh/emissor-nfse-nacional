<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Infrastructure\Http;

class ResponseParser
{
    private const ERRO_NAO_CATALOGADO = 'E999';
    private const ERRO_CERTIFICADO_INVALIDO = 'E001';
    private const ERRO_CERTIFICADO_EXPIRADO = 'E002';

    /**
     * @param array{codigo?: string, mensagem?: string, detalhes?: array<string, mixed>} $response
     * @return array{codigo: string, mensagem: string, detalhes: array<string, mixed>, recuperavel: bool}
     */
    public function parseError(array $response): array
    {
        $codigo = $response['codigo'] ?? self::ERRO_NAO_CATALOGADO;
        $mensagem = $response['mensagem'] ?? 'Erro não catalogado';
        $detalhes = $response['detalhes'] ?? [];

        return [
            'codigo' => $codigo,
            'mensagem' => $this->traduzirMensagem($codigo, $mensagem),
            'detalhes' => $detalhes,
            'recuperavel' => $this->isRecuperavel($codigo),
        ];
    }

    private function traduzirMensagem(string $codigo, string $mensagem): string
    {
        return match ($codigo) {
            self::ERRO_NAO_CATALOGADO => 'Erro não catalogado. Se persistir em produção, contate o suporte.',
            self::ERRO_CERTIFICADO_INVALIDO => 'Certificado digital inválido ou não autorizado.',
            self::ERRO_CERTIFICADO_EXPIRADO => 'Certificado digital expirado.',
            default => $mensagem,
        };
    }

    private function isRecuperavel(string $codigo): bool
    {
        $recuperaveis = [
            self::ERRO_NAO_CATALOGADO,
            'E500',
            'E503',
        ];

        return in_array($codigo, $recuperaveis);
    }
}
