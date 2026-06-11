<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\Service;

/**
 * Extrai a mensagem de erro real da resposta da SEFIN.
 *
 * A SEFIN (SefinNacional) retorna os erros em formato estruturado — `erros[]`
 * (emissão) ou `erro[]` (eventos) com `Codigo`+`Descricao` — e NÃO no campo
 * `mensagem`. A leitura ingênua de `data['mensagem']` caía sempre no fallback
 * genérico. O payload cru completo continua disponível em `dados` da resposta.
 */
trait SefinErrorMessageTrait
{
    /**
     * @param mixed $data corpo já decodificado da resposta da API
     */
    private function extrairMensagemErro(mixed $data, string $fallback): string
    {
        if (!is_array($data)) {
            return $fallback;
        }

        $erros = $data['erros'] ?? $data['erro'] ?? null;
        if (is_array($erros) && $erros !== []) {
            $primeiro = $erros[0] ?? null;

            if (is_array($primeiro)) {
                $codigo = $primeiro['codigo'] ?? $primeiro['Codigo'] ?? null;
                $descricao = $primeiro['descricao'] ?? $primeiro['Descricao']
                    ?? $primeiro['mensagem'] ?? $primeiro['Mensagem'] ?? null;
                if (is_string($descricao) && $descricao !== '') {
                    return is_string($codigo) && $codigo !== ''
                        ? "{$codigo} - {$descricao}"
                        : $descricao;
                }
            }

            if (is_string($primeiro) && $primeiro !== '') {
                return $primeiro;
            }
        }

        foreach (['mensagem', 'message', 'Mensagem', 'Message'] as $chave) {
            if (isset($data[$chave]) && is_string($data[$chave]) && $data[$chave] !== '') {
                return $data[$chave];
            }
        }

        return $fallback;
    }
}
