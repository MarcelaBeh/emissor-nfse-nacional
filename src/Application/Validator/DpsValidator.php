<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\Validator;

use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\DpsRequest;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ValidationException;
use MarcelaBeh\EmissorNfseNacional\Domain\Contract\CstClassTribRepository;

class DpsValidator
{
    public function __construct(
        private ?CstClassTribRepository $cstClassTribRepository = null,
    ) {
    }

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

        // Validações do grupo obra
        if ($request->servico->obra !== null) {
            $o = $request->servico->obra;

            if ($o->cObra !== null && (strlen($o->cObra) < 1 || strlen($o->cObra) > 30)) {
                $errors[] = 'cObra deve ter entre 1 e 30 caracteres';
            }

            if ($o->cCIB !== null && !preg_match('/^[0-9]{8}$/', $o->cCIB)) {
                $errors[] = 'cCIB deve ter exatamente 8 dígitos numéricos';
            }

            $hasCObra = $o->cObra !== null;
            $hasCCIB = $o->cCIB !== null;
            $hasEnd = $o->endereco !== null;
            $choices = ($hasCObra ? 1 : 0) + ($hasCCIB ? 1 : 0) + ($hasEnd ? 1 : 0);

            if ($choices === 0) {
                $errors[] = 'É obrigatório informar cObra, cCIB ou endereço (end) no grupo obra';
            }
            if ($choices > 1) {
                $errors[] = 'cObra, cCIB e endereço (end) são mutuamente exclusivos — informe apenas um deles';
            }

            if ($o->endereco !== null) {
                $e = $o->endereco;
                if (empty($e->xLgr)) {
                    $errors[] = 'Logradouro (xLgr) é obrigatório no endereço da obra';
                }
                if (empty($e->xBairro)) {
                    $errors[] = 'Bairro (xBairro) é obrigatório no endereço da obra';
                }
            }
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

            if ($req->finNFSe === '') {
                $errors[] = 'Finalidade da NFS-e (finNFSe) é obrigatória para IBS/CBS';
            }

            if ($req->cIndOp === '') {
                $errors[] = 'Código indicador da operação (cIndOp) é obrigatório para IBS/CBS';
            }

            if ($req->indDest === '') {
                $errors[] = 'Indicador de destinação (indDest) é obrigatório para IBS/CBS';
            }

            if ($req->cst === '') {
                $errors[] = 'Código de Situação Tributária (CST) é obrigatório para IBS/CBS';
            }

            if ($req->cClassTrib === '') {
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
            //         indDest=1 → destinatário é terceiro (dest deve ser informado)
            if ($req->indDest === '0' && $req->dest !== null) {
                $errors[] = 'E0910: destinatário não deve ser identificado (indDest=0, tomador é o destinatário)';
            }
            if ($req->indDest === '1' && $req->dest === null) {
                $errors[] = 'E0910: destinatário deve ser identificado (indDest=1, terceiro é o destinatário)';
            }

            if ($req->cCredPres !== null && !preg_match('/^[0-9]{2}$/', $req->cCredPres)) {
                $errors[] = 'cCredPres deve ter exatamente 2 dígitos';
            }

            if ($req->dest !== null && empty($req->dest->xNome)) {
                $errors[] = 'Nome do destinatário (xNome) é obrigatório quando informado o grupo dest';
            }

            // gRefNFSe: grupo de NFS-e referenciadas
            if ($req->tpOper !== null && $req->tpOper !== '') {
                $tpOper = (int) $req->tpOper;
                $hasRef = $req->refNFSeList !== null && $req->refNFSeList !== [];

                if (($tpOper === 2 || $tpOper === 3) && !$hasRef) {
                    $errors[] = 'gRefNFSe deve ser informado quando tpOper = 2 ou 3';
                }
                if (!($tpOper === 2 || $tpOper === 3) && $hasRef) {
                    $errors[] = 'gRefNFSe não pode ser informado para o tpOper informado';
                }
            } elseif ($req->refNFSeList !== null && $req->refNFSeList !== []) {
                $errors[] = 'gRefNFSe não pode ser informado se tpOper não foi informado';
            }

            if ($req->refNFSeList !== null) {
                foreach ($req->refNFSeList as $i => $chave) {
                    if (!preg_match('/^[0-9]{50}$/', $chave)) {
                        $errors[] = "E0907: refNFSe #{$i} inválida — deve ter exatamente 50 dígitos numéricos";
                    }
                }
            }

            // Validações do grupo imovel
            if ($req->imovel !== null) {
                $im = $req->imovel;

                if ($im->cCIB !== null && !preg_match('/^[0-9]{8}$/', $im->cCIB)) {
                    $errors[] = 'cCIB deve ter exatamente 8 dígitos numéricos';
                }

                if ($im->cCIB !== null && $im->endereco !== null) {
                    $errors[] = 'cCIB e endereço (end) são mutuamente exclusivos — informe apenas um deles';
                }

                if ($im->cCIB === null && $im->endereco === null) {
                    $errors[] = 'É obrigatório informar cCIB ou endereço (end) no grupo imovel';
                }

                if ($im->endereco !== null) {
                    $e = $im->endereco;
                    if (empty($e->xLgr)) {
                        $errors[] = 'Logradouro (xLgr) é obrigatório no endereço do imóvel';
                    }
                    if (empty($e->xBairro)) {
                        $errors[] = 'Bairro (xBairro) é obrigatório no endereço do imóvel';
                    }
                }
            }

            // Validações do grupo gReeRepRes
            if ($req->reeRepRes !== null) {
                if (empty($req->reeRepRes->documentos)) {
                    $errors[] = 'gReeRepRes deve conter ao menos um documento';
                }

                foreach ($req->reeRepRes->documentos as $i => $doc) {
                    $prefix = "Documento #{$i}";

                    if (!in_array($doc->tipoDocumento, ['dFeNacional', 'docFiscalOutro', 'docOutro'], true)) {
                        $errors[] = "{$prefix}: tipoDocumento inválido '{$doc->tipoDocumento}'";
                    }

                    // Validate dates
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $doc->dtEmiDoc)) {
                        $errors[] = "{$prefix}: dtEmiDoc deve estar no formato AAAA-MM-DD";
                    }
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $doc->dtCompDoc)) {
                        $errors[] = "{$prefix}: dtCompDoc deve estar no formato AAAA-MM-DD";
                    }

                    if (!in_array($doc->tpReeRepRes, ['01', '02', '03', '04', '99'], true)) {
                        $errors[] = "{$prefix}: tpReeRepRes inválido '{$doc->tpReeRepRes}'";
                    }

                    if ($doc->vlrReeRepRes <= 0) {
                        $errors[] = "{$prefix}: vlrReeRepRes deve ser maior que zero";
                    }

                    if ($doc->tpReeRepRes === '99' && empty($doc->xTpReeRepRes)) {
                        $errors[] = "{$prefix}: xTpReeRepRes é obrigatório quando tpReeRepRes = 99";
                    }

                    if ($doc->tipoDocumento === 'dFeNacional') {
                        if (!in_array($doc->tipoChaveDFe, ['1', '2', '3', '9'], true)) {
                            $errors[] = "{$prefix}: tipoChaveDFe inválido '{$doc->tipoChaveDFe}'";
                        }
                        if (empty($doc->chaveDFe)) {
                            $errors[] = "{$prefix}: chaveDFe é obrigatória para dFeNacional";
                        }
                        if ($doc->tipoChaveDFe === '9' && empty($doc->xTipoChaveDFe)) {
                            $errors[] = "{$prefix}: xTipoChaveDFe é obrigatório quando tipoChaveDFe = 9";
                        }
                    }

                    if ($doc->tipoDocumento === 'docFiscalOutro') {
                        if (!preg_match('/^\d{7}$/', $doc->cMunDocFiscal ?? '')) {
                            $errors[] = "{$prefix}: cMunDocFiscal deve ter exatamente 7 dígitos";
                        }
                        if (empty($doc->nDocFiscal)) {
                            $errors[] = "{$prefix}: nDocFiscal é obrigatório para docFiscalOutro";
                        }
                        if (empty($doc->xDocFiscal)) {
                            $errors[] = "{$prefix}: xDocFiscal é obrigatório para docFiscalOutro";
                        }
                    }

                    if ($doc->tipoDocumento === 'docOutro') {
                        if (empty($doc->nDoc)) {
                            $errors[] = "{$prefix}: nDoc é obrigatório para docOutro";
                        }
                        if (empty($doc->xDoc)) {
                            $errors[] = "{$prefix}: xDoc é obrigatório para docOutro";
                        }
                    }
                }
            }

            // Validações contra tabela de propriedades cClassTrib (IT 2025.002)
            if ($this->cstClassTribRepository !== null && $req->cClassTrib !== '') {
                $props = $this->cstClassTribRepository->findByCode($req->cClassTrib);

                if ($props === null) {
                    $errors[] = "cClassTrib '{$req->cClassTrib}' não encontrado na tabela oficial de classificação tributária";
                } else {
                    if (!$props->isValidoParaNfse()) {
                        $errors[] = "cClassTrib '{$req->cClassTrib}' não é suportado para operações de prestação de serviços (NFS-e)";
                    }

                    if ($props->isPermiteDiferimento() && $req->diferimento === null) {
                        $errors[] = 'Diferimento (gDif) deve ser informado para o cClassTrib indicado (permiteDiferimento=true)';
                    }
                    if (!$props->isPermiteDiferimento() && $req->diferimento !== null) {
                        $errors[] = 'Diferimento (gDif) não deve ser informado para o cClassTrib indicado (permiteDiferimento=false)';
                    }

                    if ($props->isExigeGrupoTributacaoRegular() && $req->tribRegular === null) {
                        $errors[] = 'Grupo de tributação regular (gTribRegular) deve ser informado para o cClassTrib indicado';
                    }
                    if (!$props->isExigeGrupoTributacaoRegular() && $req->tribRegular !== null) {
                        $errors[] = 'Grupo de tributação regular (gTribRegular) não deve ser informado para o cClassTrib indicado';
                    }
                }

                // Valida cClassTribReg se gTribRegular estiver presente
                if ($req->tribRegular !== null && $req->tribRegular->cClassTribReg !== '') {
                    $propsReg = $this->cstClassTribRepository->findByCode($req->tribRegular->cClassTribReg);
                    if ($propsReg === null) {
                        $errors[] = "cClassTribReg '{$req->tribRegular->cClassTribReg}' não encontrado na tabela oficial de classificação tributária";
                    } elseif (!$propsReg->isValidoParaNfse()) {
                        $errors[] = "cClassTribReg '{$req->tribRegular->cClassTribReg}' não é suportado para operações de prestação de serviços (NFS-e)";
                    }
                }
            }
        }

        // Validações do grupo substituição
        if ($request->substituicao !== null) {
            $s = $request->substituicao;

            if (!preg_match('/^[0-9]{50}$/', $s->chaveSubstituida)) {
                $errors[] = 'chSubstda deve ter exatamente 50 dígitos numéricos';
            }

            if (!in_array($s->codigoMotivo, ['01', '02', '03', '04', '05', '99'], true)) {
                $errors[] = "cMotivo inválido: '{$s->codigoMotivo}'";
            }

            if ($s->codigoMotivo === '99') {
                if ($s->descricaoMotivo === null || trim($s->descricaoMotivo) === '') {
                    $errors[] = 'xMotivo é obrigatório quando cMotivo = 99';
                }
            }

            if ($s->descricaoMotivo !== null) {
                $len = mb_strlen(trim($s->descricaoMotivo));
                if ($len > 0 && $len < 15) {
                    $errors[] = 'xMotivo deve ter no mínimo 15 caracteres';
                }
                if ($len > 255) {
                    $errors[] = 'xMotivo deve ter no máximo 255 caracteres';
                }
            }
        }

        if (!empty($errors)) {
            throw new ValidationException(implode('; ', $errors));
        }
    }
}
