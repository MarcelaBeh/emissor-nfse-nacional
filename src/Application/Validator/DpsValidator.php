<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\Validator;

use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\DpsRequest;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ValidationException;

class DpsValidator
{
    public function validate(DpsRequest $request): void
    {
        $errors = [];

        if ($request->tipoAmbiente !== 1 && $request->tipoAmbiente !== 2) {
            $errors[] = 'Tipo de ambiente inválido';
        }

        if ($request->serie <= 0) {
            $errors[] = 'Série deve ser maior que zero';
        }

        if ($request->numero <= 0) {
            $errors[] = 'Número deve ser maior que zero';
        }

        if (empty($request->versaoAplicacao)) {
            $errors[] = 'Versão da aplicação é obrigatória';
        }

        if (empty($request->prestador->razaoSocial)) {
            $errors[] = 'Razão social do prestador é obrigatória';
        }

        if (empty($request->tomador->razaoSocial)) {
            $errors[] = 'Razão social do tomador é obrigatória';
        }

        if (empty($request->servico->discriminacao)) {
            $errors[] = 'Discriminação do serviço é obrigatória';
        }

        if ($request->servico->aliquotaIss < 0 || $request->servico->aliquotaIss > 100) {
            $errors[] = 'Alíquota ISS deve estar entre 0 e 100';
        }

        if ($request->servico->valorServicos <= 0) {
            $errors[] = 'Valor dos serviços deve ser maior que zero';
        }

        if (!empty($errors)) {
            throw new ValidationException(implode('; ', $errors));
        }
    }
}
