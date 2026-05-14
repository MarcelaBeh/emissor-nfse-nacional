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

        if ($request->ibscbs !== null) {
            $req = $request->ibscbs;

            // E0850: IBS/CBS permitido somente a partir de 01/01/2026
            $dataCompetencia = new \DateTimeImmutable($request->dataCompetencia);
            $dataLimite = new \DateTimeImmutable('2026-01-01');
            if ($dataCompetencia < $dataLimite) {
                $errors[] = 'E0850: IBS/CBS permitido somente a partir da data de competência 01/01/2026';
            }

            // NBS obrigatório quando IBSCBS informado
            if (empty($request->servico->codigoNbs)) {
                $errors[] = 'E1508: Código NBS é obrigatório quando informações de IBS/CBS são declaradas';
            }

            if (empty($req->finNFSe)) {
                $errors[] = 'Finalidade da NFS-e (finNFSe) é obrigatória para IBS/CBS';
            }

            if (empty($req->cIndOp)) {
                $errors[] = 'Código indicador da operação (cIndOp) é obrigatório para IBS/CBS';
            }

            if (empty($req->indDest)) {
                $errors[] = 'Indicador de destinação (indDest) é obrigatório para IBS/CBS';
            }

            if (empty($req->cst)) {
                $errors[] = 'Código de Situação Tributária (CST) é obrigatório para IBS/CBS';
            }

            if (empty($req->cClassTrib)) {
                $errors[] = 'Código de Classificação Tributária (cClassTrib) é obrigatório para IBS/CBS';
            }

            if ($req->finNFSe !== '0') {
                $errors[] = 'Finalidade da NFS-e deve ser 0 (NFS-e regular)';
            }

            if (!preg_match('/^[0-9]{6}$/', $req->cIndOp)) {
                $errors[] = 'cIndOp deve ter exatamente 6 dígitos';
            }

            if (!preg_match('/^[0-9]{3}$/', $req->cst)) {
                $errors[] = 'CST deve ter exatamente 3 dígitos';
            }

            if (!preg_match('/^[0-9]{6}$/', $req->cClassTrib)) {
                $errors[] = 'cClassTrib deve ter exatamente 6 dígitos';
            }

            // E0959: 3 primeiros dígitos do cClassTrib devem ser iguais ao CST
            if (substr($req->cClassTrib, 0, 3) !== $req->cst) {
                $errors[] = 'E0959: cClassTrib não pertence ao grupo CST informado (3 primeiros dígitos devem ser iguais ao CST)';
            }

            // E0970: 3 primeiros dígitos do cClassTribReg devem ser iguais ao CSTReg
            if ($req->tribRegular !== null) {
                if (substr($req->tribRegular->cClassTribReg, 0, 3) !== $req->tribRegular->cstReg) {
                    $errors[] = 'E0970: cClassTribReg não pertence ao grupo CSTReg informado (3 primeiros dígitos devem ser iguais ao CSTReg)';
                }
            }

            // E0910: indDest=0 → destinatário é o tomador (dest não deve ser informado)
            //         indDest=1 → destinatário é intermediario (dest deve ser informado)
            if ($req->indDest === '0' && $req->dest !== null) {
                $errors[] = 'E0910: destinatário não deve ser identificado (indDest=0, tomador é o destinatário)';
            }
            if ($req->indDest === '1' && $req->dest === null) {
                $errors[] = 'E0910: destinatário deve ser identificado (indDest=1, intermediário é o destinatário)';
            }

            if ($req->cCredPres !== null && !preg_match('/^[0-9]{2}$/', $req->cCredPres)) {
                $errors[] = 'cCredPres deve ter exatamente 2 dígitos';
            }

            if ($req->dest !== null && empty($req->dest->xNome)) {
                $errors[] = 'Nome do destinatário (xNome) é obrigatório quando informado o grupo dest';
            }
        }

        if (!empty($errors)) {
            throw new ValidationException(implode('; ', $errors));
        }
    }
}
