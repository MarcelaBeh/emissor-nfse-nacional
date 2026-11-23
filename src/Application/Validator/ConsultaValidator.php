<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\Validator;

use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\ConsultaRequest;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ValidationException;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\ChaveAcesso;

class ConsultaValidator
{
    public function validate(ConsultaRequest $request): void
    {
        if (empty($request->chave)) {
            throw new ValidationException('Chave de acesso é obrigatória');
        }

        if (!preg_match(ChaveAcesso::PADRAO, $request->chave)) {
            throw new ValidationException('Chave de acesso deve ter 50 posições, com letras A-Z somente na inscrição federal (posições 10 a 23)');
        }
    }
}
