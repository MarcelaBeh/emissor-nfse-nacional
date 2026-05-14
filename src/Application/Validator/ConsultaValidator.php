<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\Validator;

use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\ConsultaRequest;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ValidationException;

class ConsultaValidator
{
    public function validate(ConsultaRequest $request): void
    {
        if (empty($request->chave)) {
            throw new ValidationException('Chave de acesso é obrigatória');
        }

        if (!preg_match('/^[0-9]{50}$/', $request->chave)) {
            throw new ValidationException('Chave de acesso deve ter exatamente 50 dígitos numéricos');
        }
    }
}
