<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Application\Validator;

use emissorNfseNacional\NfseNacional\Application\DTO\Request\EventoRequest;
use emissorNfseNacional\NfseNacional\Application\Exception\ValidationException;

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
