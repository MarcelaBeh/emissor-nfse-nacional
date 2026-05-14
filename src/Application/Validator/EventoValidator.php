<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\Validator;

use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\EventoRequest;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ValidationException;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\MotivoCancelamento;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\MotivoRejeicao;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\MotivoSubstituicao;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEvento;

class EventoValidator
{
    public function validate(EventoRequest $request): void
    {
        $errors = [];

        if (empty($request->chaveNfse)) {
            $errors[] = 'Chave da NFSe é obrigatória';
        } elseif (preg_match('/^[0-9]{50}$/', $request->chaveNfse) !== 1) {
            $errors[] = 'Chave da NFSe deve ter exatamente 50 dígitos numéricos';
        }

        if (empty($request->tipoEvento)) {
            $errors[] = 'Tipo do evento é obrigatório';
        } else {
            try {
                $tipoEvento = TipoEvento::from($request->tipoEvento);
            } catch (\ValueError) {
                $errors[] = "Tipo de evento inválido: {$request->tipoEvento}";
                $tipoEvento = null;
            }
        }

        if (empty($request->versaoAplicacao)) {
            $errors[] = 'Versão da aplicação é obrigatória';
        } elseif (mb_strlen($request->versaoAplicacao) > 20) {
            $errors[] = 'Versão da aplicação deve ter no máximo 20 caracteres';
        }

        if ($request->cnpjAutor !== null && $request->cpfAutor !== null) {
            $errors[] = 'Informar apenas CNPJ Autor ou CPF Autor, não ambos';
        }

        if ($request->cnpjAutor !== null && preg_match('/^[0-9]{14}$/', $request->cnpjAutor) !== 1) {
            $errors[] = 'CNPJ Autor deve ter 14 dígitos numéricos';
        }

        if ($request->cpfAutor !== null && preg_match('/^[0-9]{11}$/', $request->cpfAutor) !== 1) {
            $errors[] = 'CPF Autor deve ter 11 dígitos numéricos';
        }

        if (isset($tipoEvento) && $tipoEvento->hasMotivo()) {
            if (empty($request->codigoMotivo)) {
                $errors[] = 'Código do motivo é obrigatório para este tipo de evento';
            } else {
                $motivoErrors = $this->validarCodigoMotivo($tipoEvento, $request->codigoMotivo);
                array_push($errors, ...$motivoErrors);
            }
        }

        if (isset($tipoEvento) && $tipoEvento->needsChSubstituta()) {
            if (empty($request->chSubstituta)) {
                $errors[] = 'Chave da NFS-e substituta (chSubstituta) é obrigatória para substituição';
            } elseif (preg_match('/^[0-9]{50}$/', $request->chSubstituta) !== 1) {
                $errors[] = 'chSubstituta deve ter 50 dígitos numéricos';
            }
        }

        if (isset($tipoEvento) && $tipoEvento->needsCpfAgTrib() && empty($request->cpfAgTrib)) {
            $errors[] = 'CPF do agente tributário (cpfAgTrib) é obrigatório para este tipo de evento';
        }

        if (isset($tipoEvento) && $tipoEvento->needsNumeroProcesso() && empty($request->nProcAdm)) {
            $errors[] = 'Número do processo administrativo (nProcAdm) é obrigatório para este tipo de evento';
        }

        if ($request->codigoMotivo === '99' && empty($request->descricaoMotivo)) {
            $errors[] = 'Descrição do motivo é obrigatória quando código do motivo = 99 (Outros)';
        }

        if (empty($request->dataEvento)) {
            $errors[] = 'Data do evento é obrigatória';
        }

        if (!empty($errors)) {
            throw new ValidationException(implode('; ', $errors));
        }
    }

    /** @return list<string> */
    private function validarCodigoMotivo(TipoEvento $tipoEvento, string $codigo): array
    {
        $errors = [];

        $validos = match ($tipoEvento) {
            TipoEvento::CANCELAMENTO => MotivoCancelamento::valores(),
            TipoEvento::SUBSTITUICAO => MotivoSubstituicao::valores(),
            TipoEvento::SOLICITACAO_ANALISE_FISCAL => MotivoCancelamento::valores(),
            TipoEvento::CANCELAMENTO_DEFERIDO => ['1'],
            TipoEvento::CANCELAMENTO_INDEFERIDO => ['1', '2'],
            TipoEvento::REJEICAO_PRESTADOR, TipoEvento::REJEICAO_TOMADOR, TipoEvento::REJEICAO_INTERMEDIARIO => MotivoRejeicao::valores(),
            default => [],
        };

        if (!empty($validos) && !in_array($codigo, $validos, true)) {
            $errors[] = "Código de motivo '{$codigo}' inválido para {$tipoEvento->value}. Valores permitidos: " . implode(', ', $validos);
        }

        return $errors;
    }
}
