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

        // Validações dos novos campos do Servico (locPrest, cServ, valores)
        if ($request->servico->codigoPaisPrestacao !== null && !preg_match('/^[A-Z]{2}$/', $request->servico->codigoPaisPrestacao)) {
            $errors[] = 'cPaisPrestacao deve ser um código ISO de país de 2 letras maiúsculas';
        }

        if ($request->servico->codigoTributacaoMunicipal !== null && !preg_match('/^[0-9]{3}$/', $request->servico->codigoTributacaoMunicipal)) {
            $errors[] = 'cTribMun deve ter exatamente 3 dígitos numéricos';
        }

        if ($request->servico->codigoInternoContribuinte !== null && !preg_match('/^[a-zA-Z0-9]{1,20}$/', $request->servico->codigoInternoContribuinte)) {
            $errors[] = 'cIntContrib deve ser alfanumérico de 1 a 20 caracteres';
        }

        if ($request->servico->valorRecebido !== null && $request->servico->valorRecebido <= 0) {
            $errors[] = 'vReceb deve ser maior que zero';
        }

        // Validações do grupo comExterior
        if ($request->servico->comExterior !== null) {
            $ce = $request->servico->comExterior;

            if ($ce->modoPrestacao === null) {
                $errors[] = 'mdPrestacao é obrigatório no grupo comExterior';
            }

            if ($ce->vinculoPrestador === null) {
                $errors[] = 'vincPrest é obrigatório no grupo comExterior';
            }

            if ($ce->codigoMoeda !== null && !preg_match('/^[0-9]{3}$/', $ce->codigoMoeda)) {
                $errors[] = 'tpMoeda deve ser um código ISO de moeda numérico de 3 dígitos';
            }

            if ($ce->valorServicoMoeda !== null && $ce->valorServicoMoeda <= 0) {
                $errors[] = 'vServMoeda deve ser maior que zero';
            }
        }

        // Validações do grupo atvEvento
        if ($request->servico->atvEvento !== null) {
            $ae = $request->servico->atvEvento;

            if (empty($ae->descricao)) {
                $errors[] = 'xNome (descricao) é obrigatório no grupo atvEvento';
            }

            if ($ae->dataInicio !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $ae->dataInicio)) {
                $errors[] = 'dtIni deve estar no formato AAAA-MM-DD';
            }

            if ($ae->dataFim !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $ae->dataFim)) {
                $errors[] = 'dtFim deve estar no formato AAAA-MM-DD';
            }

            if ($ae->dataInicio !== null && $ae->dataFim !== null && $ae->dataInicio > $ae->dataFim) {
                $errors[] = 'dtIni não pode ser posterior a dtFim';
            }
        }

        // Validações do grupo infoCompl
        if ($request->servico->infoCompl !== null) {
            $ic = $request->servico->infoCompl;

            if ($ic->itensPedido !== null) {
                foreach ($ic->itensPedido as $i => $item) {
                    if (strlen($item) > 255) {
                        $errors[] = "gItemPed #{$i} deve ter no máximo 255 caracteres";
                    }
                }
            }
        }

        // Validações do grupo documentosDeducao (vDedRed)
        if ($request->servico->documentosDeducao !== null) {
            $validTypes = ['chNFSe', 'chNFe', 'NFSeMun', 'NFNFS', 'nDocFisc', 'nDoc'];

            foreach ($request->servico->documentosDeducao as $i => $doc) {
                $prefix = "DocDedRed #{$i}";

                if ($doc->tipoDocumento === null || !in_array($doc->tipoDocumento, $validTypes, true)) {
                    $errors[] = "{$prefix}: tipoDocumento inválido '{$doc->tipoDocumento}'";
                }

                if ($doc->tipoDocumento === 'chNFSe' && empty($doc->chaveNFSe)) {
                    $errors[] = "{$prefix}: chNFSe é obrigatória para tipoDocumento = chNFSe";
                }

                if ($doc->tipoDocumento === 'chNFSe' && !empty($doc->chaveNFSe) && !preg_match('/^[0-9]{50}$/', $doc->chaveNFSe)) {
                    $errors[] = "{$prefix}: chNFSe deve ter exatamente 50 dígitos numéricos";
                }

                if ($doc->tipoDocumento === 'chNFe' && empty($doc->chaveNFe)) {
                    $errors[] = "{$prefix}: chNFe é obrigatória para tipoDocumento = chNFe";
                }

                if ($doc->tipoDocumento === 'chNFe' && !empty($doc->chaveNFe) && !preg_match('/^[0-9]{44}$/', $doc->chaveNFe)) {
                    $errors[] = "{$prefix}: chNFe deve ter exatamente 44 dígitos numéricos";
                }

                if ($doc->tipoDocumento === 'NFSeMun') {
                    if (empty($doc->codigoMunicipioNFSe)) {
                        $errors[] = "{$prefix}: cMunNFSeMun é obrigatório para NFSeMun";
                    }
                    if (empty($doc->numeroNFSe)) {
                        $errors[] = "{$prefix}: nNFSeMun é obrigatório para NFSeMun";
                    }
                    if (empty($doc->codigoVerificacaoNFSe)) {
                        $errors[] = "{$prefix}: cVerifNFSeMun é obrigatório para NFSeMun";
                    }
                }

                if ($doc->tipoDocumento === 'NFNFS') {
                    if (empty($doc->numeroNFS)) {
                        $errors[] = "{$prefix}: nNFS é obrigatório para NFNFS";
                    }
                    if (empty($doc->modeloNFS)) {
                        $errors[] = "{$prefix}: modNFS é obrigatório para NFNFS";
                    }
                    if (empty($doc->serieNFS)) {
                        $errors[] = "{$prefix}: serieNFS é obrigatório para NFNFS";
                    }
                }

                if ($doc->tipoDocumento === 'nDocFisc' && empty($doc->numeroDocFiscal)) {
                    $errors[] = "{$prefix}: nDocFisc é obrigatório para tipoDocumento = nDocFisc";
                }

                if ($doc->tipoDocumento === 'nDoc' && empty($doc->numeroDoc)) {
                    $errors[] = "{$prefix}: nDoc é obrigatório para tipoDocumento = nDoc";
                }

                if ($doc->dataEmissaoDoc !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $doc->dataEmissaoDoc)) {
                    $errors[] = "{$prefix}: dEmiDoc deve estar no formato AAAA-MM-DD";
                }

                if ($doc->tipoDeducaoReducao !== null && !in_array($doc->tipoDeducaoReducao, ['1', '2', '3', '4', '5', '6', '7', '8', '9', '99'], true)) {
                    $errors[] = "{$prefix}: tpDedRed inválido '{$doc->tipoDeducaoReducao}'";
                }

                if ($doc->tipoDeducaoReducao === '99' && empty($doc->descricaoOutrasDeducoes)) {
                    $errors[] = "{$prefix}: xDescOutDed é obrigatório quando tpDedRed = 99";
                }

                if ($doc->valorDedutivel !== null && !is_numeric($doc->valorDedutivel)) {
                    $errors[] = "{$prefix}: vDedutivelRedutivel deve ser um valor numérico";
                }

                if ($doc->valorDeducao !== null && !is_numeric($doc->valorDeducao)) {
                    $errors[] = "{$prefix}: vDeducaoReducao deve ser um valor numérico";
                }
            }
        }

        // Validações do grupo tribMun
        if ($request->servico->tipoImunidade !== null && !in_array($request->servico->tipoImunidade, [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15], true)) {
            $errors[] = 'tpImunidade inválido — deve ser um número entre 1 e 15';
        }

        if ($request->servico->exigSusp !== null) {
            $es = $request->servico->exigSusp;

            if ($es->tipoSuspensao !== null && !in_array($es->tipoSuspensao, [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13], true)) {
                $errors[] = 'tpSusp inválido — deve ser um número entre 1 e 13';
            }

            if ($es->numeroProcesso !== null && $es->numeroProcesso === '') {
                $errors[] = 'nProcesso não pode ser vazio quando informado';
            }
        }

        if ($request->servico->beneficioMunicipal !== null && empty($request->servico->beneficioMunicipal->numeroBeneficio)) {
            $errors[] = 'nBM é obrigatório no grupo BM';
        }

        // Validações do grupo tribFed
        if ($request->servico->tribFederal !== null) {
            $tf = $request->servico->tribFederal;

            if ($tf->pisCofinsCst !== null && !preg_match('/^[0-9]{2}$/', $tf->pisCofinsCst)) {
                $errors[] = 'CST do PIS/COFINS deve ter exatamente 2 dígitos';
            }

            if ($tf->pisCofinsAliquotaPis !== null && ($tf->pisCofinsAliquotaPis < 0 || $tf->pisCofinsAliquotaPis > 100)) {
                $errors[] = 'pAliqPis deve estar entre 0 e 100';
            }

            if ($tf->pisCofinsAliquotaCofins !== null && ($tf->pisCofinsAliquotaCofins < 0 || $tf->pisCofinsAliquotaCofins > 100)) {
                $errors[] = 'pAliqCofins deve estar entre 0 e 100';
            }

            if ($tf->pisCofinsCst !== null && $tf->pisCofinsTipo === null) {
                $errors[] = 'tipo do PIS/COFINS é obrigatório quando CST é informado';
            }

            if ($tf->pisCofinsTipo !== null && !in_array($tf->pisCofinsTipo, ['1', '2', '3'], true)) {
                $errors[] = "tipo do PIS/COFINS inválido '{$tf->pisCofinsTipo}'";
            }
        }

        // Validações do grupo totTrib
        if ($request->servico->totTribTipo !== null) {
            $validTotTrib = ['vTotTrib', 'pTotTrib', 'indTotTrib', 'pTotTribSN'];
            if (!in_array($request->servico->totTribTipo, $validTotTrib, true)) {
                $errors[] = "totTribTipo inválido '{$request->servico->totTribTipo}'";
            }

            if ($request->servico->totTribTipo === 'pTotTrib') {
                if ($request->servico->pTotTribFed === null) {
                    $errors[] = 'pTotTribFed é obrigatório quando totTribTipo = pTotTrib';
                }
                if ($request->servico->pTotTribEst === null) {
                    $errors[] = 'pTotTribEst é obrigatório quando totTribTipo = pTotTrib';
                }
                if ($request->servico->pTotTribMun === null) {
                    $errors[] = 'pTotTribMun é obrigatório quando totTribTipo = pTotTrib';
                }
            }

            if ($request->servico->totTribTipo === 'indTotTrib' && $request->servico->indTotTrib === null) {
                $errors[] = 'indTotTrib é obrigatório quando totTribTipo = indTotTrib';
            }

            if ($request->servico->totTribTipo === 'pTotTribSN' && $request->servico->pTotTribSN === null) {
                $errors[] = 'pTotTribSN é obrigatório quando totTribTipo = pTotTribSN';
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
