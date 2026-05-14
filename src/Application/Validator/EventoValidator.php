<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\Validator;

use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\EventoRequest;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ValidationException;

class EventoValidator
{
    public function validate(EventoRequest $request): void
    {
        $errors = [];

        if (empty($request->chaveNfse)) {
            $errors[] = 'Chave da NFSe é obrigatória';
        }

        if (empty($request->tipoEvento)) {
            $errors[] = 'Tipo do evento é obrigatório';
        }

        if (empty($request->versaoAplicacao)) {
            $errors[] = 'Versão da aplicação é obrigatória';
        }

        if (!empty($errors)) {
            throw new ValidationException(implode('; ', $errors));
        }
    }
}
