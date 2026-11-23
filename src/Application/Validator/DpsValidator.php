<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\Validator;

use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\DocAjusteBCRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\DpsRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsFornecedorRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsUnidadeImobiliariaRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\ServicoRequest;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ValidationException;
use MarcelaBeh\EmissorNfseNacional\Domain\Contract\CstClassTribRepository;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\DocAjusteBC;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsImovel;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsInfo;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\CausaNaoNif;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\EnviarMdic;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\FinalidadeNfse;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\IndicadorDestinacao;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\IndicadorFinal;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\MecanismoApoioPrestador;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\MecanismoApoioTomador;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\ModoPrestacao;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\MotivoEmissaoTI;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\MotivoSubstituicao;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\MovimentacaoTemporaria;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\RegimeEspecialTributacao;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\RegimeTributario;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoAjusteBC;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoAjusteBCLocImoveis;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoAmbiente;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoChaveDocumentoFiscal;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEmitente;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEnteGovernamental;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoOperacao;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoRetencaoIssqn;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TributacaoIssqn;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\VinculoPrestador;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\ChaveAcesso;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cnpj;

class DpsValidator
{
    private const UFS = ['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'];

    /** Domínio oficial do CST de PIS/COFINS (TSTipoCST), conforme NT-007. */
    private const CST_PISCOFINS = [
        '00','01','02','03','04','05','06','07','08','09','49',
        '50','51','52','53','54','55','56',
        '60','61','62','63','64','65','66','67',
        '70','71','72','73','74','75','98','99',
    ];

    public function __construct(
        private ?CstClassTribRepository $cstClassTribRepository = null,
    ) {
    }

    public function validate(DpsRequest $request): void
    {
        $errors = [];

        $this->validateInfDps($request, $errors);
        $this->validatePrestador($request, $errors);
        $this->validateTomador($request, $errors);
        $this->validateIntermediario($request, $errors);
        $this->validateServico($request, $errors);
        $this->validateIbsCbs($request, $errors);
        $this->validateSubstituicao($request, $errors);

        if (!empty($errors)) {
            throw new ValidationException(implode('; ', $errors));
        }
    }

    /** @param array<string> $errors */
    private function validateInfDps(DpsRequest $request, array &$errors): void
    {
        if (!in_array($request->tipoAmbiente, TipoAmbiente::valores(), true)) {
            $errors[] = 'tpAmb inválido - deve ser 1 (Produção) ou 2 (Homologação)';
        }

        if (!in_array($request->tipoEmissao, TipoEmitente::valores(), true)) {
            $errors[] = 'tpEmit inválido - deve ser 1 (Prestador), 2 (Tomador) ou 3 (Intermediário) (TSEmitenteDPS)';
        }

        if ($request->cMotivoEmisTI !== null && !in_array($request->cMotivoEmisTI, MotivoEmissaoTI::valores(), true)) {
            $errors[] = 'cMotivoEmisTI inválido (TSMotivoEmisTI)';
        }

        // TSSerieDPS (XSD NT 009): [0-9]{1,4}|[0-8][0-9]{4}, ou seja, 1 a 89999. A faixa 90000 a 99999
        // não é aceita pelo leiaute; a faixa permitida a cada tipo de emissor é regra do servidor.
        if ($request->serie < 1 || $request->serie > 89999) {
            $errors[] = 'serie deve estar entre 1 e 89999 (TSSerieDPS)';
        }

        if ($request->finNFSe === '') {
            $errors[] = 'finNFSe é obrigatória (TSRTCFinNFSe)';
        } elseif (!in_array($request->finNFSe, FinalidadeNfse::valores(), true)) {
            $errors[] = 'finNFSe inválido (TSRTCFinNFSe): nesta versão do leiaute só é admitido 0 (NFS-e regular)';
        }

        $nDpsStr = (string) $request->numero;
        if (!preg_match('/^[1-9][0-9]{0,14}$/', $nDpsStr)) {
            $errors[] = 'nDPS deve ser numérico de 1 a 15 dígitos, sem leading zeros (TSNumDPS)';
        }

        if (empty($request->versaoAplicacao)) {
            $errors[] = 'verAplic é obrigatória';
        } elseif (mb_strlen($request->versaoAplicacao, 'UTF-8') > 20) {
            $errors[] = 'verAplic deve ter no máximo 20 caracteres (TSVerAplic)';
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[\-+]\d{2}:\d{2}$/', $request->dataEmissao)) {
            $errors[] = 'dhEmi deve estar no formato AAAA-MM-DDTHH:MM:SS±HH:MM (TSDateTimeUTC)';
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $request->dataCompetencia)) {
            $errors[] = 'dCompet deve estar no formato AAAA-MM-DD (TSData)';
        }

        if (!preg_match('/^[0-9]{7}$/', $request->codigoMunicipioEmissor)) {
            $errors[] = 'cLocEmi deve ter exatamente 7 dígitos numéricos (TSCodMunIBGE)';
        }

        if ($request->chNFSeRej !== null && !preg_match(ChaveAcesso::PADRAO, $request->chNFSeRej)) {
            $errors[] = 'chNFSeRej deve ter 50 posições, com letras A-Z somente na inscrição federal (posições 10 a 23) (TSChaveNFSe)';
        }
    }

    /** @param array<string> $errors */
    private function validatePrestador(DpsRequest $request, array &$errors): void
    {
        $p = $request->prestador;

        if (!in_array($p->regimeTributario, RegimeTributario::valores(), true)) {
            $errors[] = 'opSimpNac inválido - deve ser 1, 2 ou 3 (TSOpSimpNac)';
        }

        if ($p->regApTribSN !== null && !in_array($p->regApTribSN, [1, 2, 3], true)) {
            $errors[] = 'regApTribSN inválido - deve ser 1, 2 ou 3 (TSRegimeApuracaoSimpNac)';
        }

        if ($p->regEspTrib !== null && !in_array((string) $p->regEspTrib, RegimeEspecialTributacao::valores(), true)) {
            $errors[] = 'regEspTrib inválido - deve ser 0, 1, 2, 3, 4, 5, 6 ou 9 (TSRegEspTrib)';
        }

        if (mb_strlen($p->razaoSocial, 'UTF-8') > 300) {
            $errors[] = 'xNome do prestador deve ter no máximo 300 caracteres (TSNomeRazaoSocial)';
        }

        $idCount = 0;
        if ($p->documento !== null) {
            $idCount++;
        }
        if ($p->nif !== null) {
            $idCount++;
        }
        if ($p->codigoNaoNif !== null) {
            $idCount++;
        }

        if ($idCount === 0) {
            $errors[] = 'Prestador deve ter CNPJ, CPF, NIF ou cNaoNIF';
        }
        if ($idCount > 1) {
            $errors[] = 'Prestador: CNPJ, CPF, NIF e cNaoNIF são mutuamente exclusivos';
        }

        if ($p->documento !== null) {
            if ($p->isCnpj === true) {
                if (!preg_match(Cnpj::PADRAO, $p->documento)) {
                    $errors[] = 'CNPJ do prestador deve ter 14 caracteres: 12 de raiz e ordem (dígitos ou letras A-Z) e 2 dígitos verificadores (TSCNPJ)';
                }
            } elseif ($p->isCnpj === false) {
                if (!preg_match('/^[0-9]{11}$/', $p->documento)) {
                    $errors[] = 'CPF do prestador deve ter 11 dígitos numéricos (TSCPF)';
                }
            }
        }

        if ($p->nif !== null && (mb_strlen($p->nif, 'UTF-8') < 1 || mb_strlen($p->nif, 'UTF-8') > 40)) {
            $errors[] = 'NIF do prestador deve ter 1 a 40 caracteres (TSNIF)';
        }

        if ($p->codigoNaoNif !== null && !in_array($p->codigoNaoNif, CausaNaoNif::valores(), true)) {
            $errors[] = 'cNaoNIF inválido - deve ser 0, 1 ou 2 (TSCodNaoNIF)';
        }

        if ($p->caepf !== null && !preg_match('/^[0-9]{14}$/', $p->caepf)) {
            $errors[] = 'CAEPF do prestador deve ter 14 dígitos numéricos (TSCAEPF)';
        }

        if ($p->inscricaoMunicipal !== null && (mb_strlen($p->inscricaoMunicipal, 'UTF-8') < 1 || mb_strlen($p->inscricaoMunicipal, 'UTF-8') > 15)) {
            $errors[] = 'IM do prestador deve ter 1 a 15 caracteres (TSInscMun)';
        }

        // cMun NÃO faz parte do grupo <end>: alimenta cLocEmi e é sempre obrigatório.
        if (!preg_match('/^[0-9]{7}$/', $p->codigoMunicipio)) {
            $errors[] = 'cMun do prestador deve ter 7 dígitos numéricos (TSCodMunIBGE)';
        }


        $temEndereco = $p->logradouro !== null
            || $p->numero !== null
            || $p->complemento !== null
            || $p->bairro !== null
            || $p->uf !== null
            || $p->cep !== null;

        if ($temEndereco) {
            if (!preg_match('/^[0-9]{8}$/', (string) $p->cep)) {
                $errors[] = 'CEP do prestador deve ter 8 dígitos numéricos (TSCEP)';
            }

            if (empty($p->logradouro)) {
                $errors[] = 'xLgr do prestador é obrigatório quando o endereço é informado (TSLogradouro)';
            } elseif (mb_strlen($p->logradouro, 'UTF-8') > 255) {
                $errors[] = 'xLgr do prestador deve ter no máximo 255 caracteres (TSLogradouro)';
            }

            if (empty($p->numero)) {
                $errors[] = 'nro do prestador é obrigatório quando o endereço é informado (TSNumeroEndereco)';
            } elseif (mb_strlen($p->numero, 'UTF-8') > 60) {
                $errors[] = 'nro do prestador deve ter no máximo 60 caracteres (TSNumeroEndereco)';
            }

            if ($p->complemento !== null && (mb_strlen($p->complemento, 'UTF-8') < 1 || mb_strlen($p->complemento, 'UTF-8') > 156)) {
                $errors[] = 'xCpl do prestador deve ter 1 a 156 caracteres (TSComplementoEndereco)';
            }

            if (empty($p->bairro)) {
                $errors[] = 'xBairro do prestador é obrigatório quando o endereço é informado (TSBairro)';
            } elseif (mb_strlen($p->bairro, 'UTF-8') > 60) {
                $errors[] = 'xBairro do prestador deve ter no máximo 60 caracteres (TSBairro)';
            }

            if (!in_array($p->uf, self::UFS, true)) {
                $errors[] = 'UF do prestador inválida (TSUF)';
            }
        }

        if ($p->telefone !== null && !preg_match('/^[0-9]{6,20}$/', $p->telefone)) {
            $errors[] = 'fone do prestador deve ter 6 a 20 dígitos numéricos (TSTelefone)';
        }

        if ($p->email !== null && (mb_strlen($p->email, 'UTF-8') < 1 || mb_strlen($p->email, 'UTF-8') > 80)) {
            $errors[] = 'email do prestador deve ter 1 a 80 caracteres (TSEmail)';
        }
    }

    /** @param array<string> $errors */
    private function validateTomador(DpsRequest $request, array &$errors): void
    {
        $t = $request->tomador;
        if ($t === null) {
            return;
        }

        if (empty($t->razaoSocial)) {
            $errors[] = 'xNome do tomador é obrigatória (TSNomeRazaoSocial)';
        } elseif (mb_strlen($t->razaoSocial, 'UTF-8') > 300) {
            $errors[] = 'xNome do tomador deve ter no máximo 300 caracteres (TSNomeRazaoSocial)';
        }


        $idCount = 0;
        if ($t->documento !== null) {
            $idCount++;
        }
        if ($t->nif !== null) {
            $idCount++;
        }
        if ($t->codigoNaoNif !== null) {
            $idCount++;
        }

        if ($idCount === 0) {
            $errors[] = 'Tomador deve ter CNPJ, CPF, NIF ou cNaoNIF';
        }
        if ($idCount > 1) {
            $errors[] = 'Tomador: CNPJ, CPF, NIF e cNaoNIF são mutuamente exclusivos';
        }

        if ($t->documento !== null) {
            if ($t->isCnpj === true) {
                if (!preg_match(Cnpj::PADRAO, $t->documento)) {
                    $errors[] = 'CNPJ do tomador deve ter 14 caracteres: 12 de raiz e ordem (dígitos ou letras A-Z) e 2 dígitos verificadores (TSCNPJ)';
                }
            } elseif ($t->isCnpj === false) {
                if (!preg_match('/^[0-9]{11}$/', $t->documento)) {
                    $errors[] = 'CPF do tomador deve ter 11 dígitos numéricos (TSCPF)';
                }
            }
        }

        if ($t->nif !== null && (mb_strlen($t->nif, 'UTF-8') < 1 || mb_strlen($t->nif, 'UTF-8') > 40)) {
            $errors[] = 'NIF do tomador deve ter 1 a 40 caracteres (TSNIF)';
        }

        if ($t->codigoNaoNif !== null && !in_array($t->codigoNaoNif, CausaNaoNif::valores(), true)) {
            $errors[] = 'cNaoNIF do tomador inválido - deve ser 0, 1 ou 2 (TSCodNaoNIF)';
        }

        if ($t->caepf !== null && !preg_match('/^[0-9]{14}$/', $t->caepf)) {
            $errors[] = 'CAEPF do tomador deve ter 14 dígitos numéricos (TSCAEPF)';
        }

        if ($t->inscricaoMunicipal !== null && (mb_strlen($t->inscricaoMunicipal, 'UTF-8') < 1 || mb_strlen($t->inscricaoMunicipal, 'UTF-8') > 15)) {
            $errors[] = 'IM do tomador deve ter 1 a 15 caracteres (TSInscMun)';
        }

        // <end> é opcional (TCInfoPessoa/end, minOccurs=0). Só validamos quando algum
        // dado de endereço foi informado. codigoPais setado => endereço exterior (endExt).
        $tomadorExterior = $t->codigoPais !== null;

        $hasAddress = !empty($t->codigoMunicipio) || !empty($t->cep) || !empty($t->logradouro)
            || !empty($t->numero) || !empty($t->complemento) || !empty($t->bairro) || !empty($t->uf)
            || $tomadorExterior;

        if ($hasAddress) {
            // xLgr, nro e xBairro são obrigatórios (minOccurs=1) dentro de <end> - não podem
            // ficar vazios quando o grupo é emitido, senão o XSD rejeita com erro genérico.
            if (empty($t->logradouro)) {
                $errors[] = 'xLgr do tomador é obrigatório quando há endereço (TSLogradouro)';
            } elseif (mb_strlen($t->logradouro, 'UTF-8') > 255) {
                $errors[] = 'xLgr do tomador deve ter no máximo 255 caracteres (TSLogradouro)';
            }

            if (empty($t->numero)) {
                $errors[] = 'nro do tomador é obrigatório quando há endereço - use "S/N" se não houver (TSNumeroEndereco)';
            } elseif (mb_strlen($t->numero, 'UTF-8') > 60) {
                $errors[] = 'nro do tomador deve ter no máximo 60 caracteres (TSNumeroEndereco)';
            }

            if ($t->complemento !== null && (mb_strlen($t->complemento, 'UTF-8') < 1 || mb_strlen($t->complemento, 'UTF-8') > 156)) {
                $errors[] = 'xCpl do tomador deve ter 1 a 156 caracteres (TSComplementoEndereco)';
            }

            if (empty($t->bairro)) {
                $errors[] = 'xBairro do tomador é obrigatório quando há endereço (TSBairro)';
            } elseif (mb_strlen($t->bairro, 'UTF-8') > 60) {
                $errors[] = 'xBairro do tomador deve ter no máximo 60 caracteres (TSBairro)';
            }

            if ($tomadorExterior) {
                // endExt: cPais, cEndPost, xCidade, xEstProvReg são todos obrigatórios (minOccurs=1).
                if (!preg_match('/^[A-Z]{2}$/', $t->codigoPais)) {
                    $errors[] = 'cPais do tomador deve ser 2 letras maiúsculas (TSCodPaisISO)';
                }
                if ($t->codigoPostalExterior === null || mb_strlen($t->codigoPostalExterior, 'UTF-8') < 1 || mb_strlen($t->codigoPostalExterior, 'UTF-8') > 11) {
                    $errors[] = 'cEndPost do tomador é obrigatório e deve ter 1 a 11 caracteres para endereço exterior (TSCodigoEndPostal)';
                }
                if ($t->nomeCidadeExterior === null || mb_strlen($t->nomeCidadeExterior, 'UTF-8') < 1 || mb_strlen($t->nomeCidadeExterior, 'UTF-8') > 60) {
                    $errors[] = 'xCidade do tomador é obrigatória e deve ter 1 a 60 caracteres para endereço exterior (TSCidade)';
                }
                if ($t->estadoProvinciaExterior === null || mb_strlen($t->estadoProvinciaExterior, 'UTF-8') < 1 || mb_strlen($t->estadoProvinciaExterior, 'UTF-8') > 60) {
                    $errors[] = 'xEstProvReg do tomador é obrigatório e deve ter 1 a 60 caracteres para endereço exterior (TSEstadoProvRegiao)';
                }
            } else {
                // endNac: cMun, CEP, UF obrigatórios.
                if ($t->codigoMunicipio === null || !preg_match('/^[0-9]{7}$/', $t->codigoMunicipio)) {
                    $errors[] = 'cMun do tomador deve ter 7 dígitos numéricos (TSCodMunIBGE)';
                }
                if ($t->cep === null || !preg_match('/^[0-9]{8}$/', $t->cep)) {
                    $errors[] = 'CEP do tomador deve ter 8 dígitos numéricos (TSCEP)';
                }
                if (!in_array($t->uf, self::UFS, true)) {
                    $errors[] = 'UF do tomador inválida (TSUF)';
                }
            }
        }

        if ($t->telefone !== null && !preg_match('/^[0-9]{6,20}$/', $t->telefone)) {
            $errors[] = 'fone do tomador deve ter 6 a 20 dígitos numéricos (TSTelefone)';
        }

        if ($t->email !== null && (mb_strlen($t->email, 'UTF-8') < 1 || mb_strlen($t->email, 'UTF-8') > 80)) {
            $errors[] = 'email do tomador deve ter 1 a 80 caracteres (TSEmail)';
        }
    }

    /** @param array<string> $errors */
    private function validateIntermediario(DpsRequest $request, array &$errors): void
    {
        $i = $request->intermediario;
        if ($i === null) {
            return;
        }

        if (empty($i->razaoSocial)) {
            $errors[] = 'xNome do intermediário é obrigatória (TSNomeRazaoSocial)';
        } elseif (mb_strlen($i->razaoSocial, 'UTF-8') > 300) {
            $errors[] = 'xNome do intermediário deve ter no máximo 300 caracteres (TSNomeRazaoSocial)';
        }

        $idCount = 0;
        if ($i->documento !== null) {
            $idCount++;
        }
        if ($i->nif !== null) {
            $idCount++;
        }
        if ($i->codigoNaoNif !== null) {
            $idCount++;
        }

        if ($idCount === 0) {
            $errors[] = 'Intermediário deve ter CNPJ, CPF, NIF ou cNaoNIF';
        }
        if ($idCount > 1) {
            $errors[] = 'Intermediário: CNPJ, CPF, NIF e cNaoNIF são mutuamente exclusivos';
        }

        if ($i->documento !== null) {
            if ($i->isCnpj === true) {
                if (!preg_match(Cnpj::PADRAO, $i->documento)) {
                    $errors[] = 'CNPJ do intermediário deve ter 14 caracteres: 12 de raiz e ordem (dígitos ou letras A-Z) e 2 dígitos verificadores (TSCNPJ)';
                }
            } elseif ($i->isCnpj === false) {
                if (!preg_match('/^[0-9]{11}$/', $i->documento)) {
                    $errors[] = 'CPF do intermediário deve ter 11 dígitos numéricos (TSCPF)';
                }
            }
        }

        if ($i->nif !== null && (mb_strlen($i->nif, 'UTF-8') < 1 || mb_strlen($i->nif, 'UTF-8') > 40)) {
            $errors[] = 'NIF do intermediário deve ter 1 a 40 caracteres (TSNIF)';
        }

        if ($i->codigoNaoNif !== null && !in_array($i->codigoNaoNif, CausaNaoNif::valores(), true)) {
            $errors[] = 'cNaoNIF do intermediário inválido - deve ser 0, 1 ou 2 (TSCodNaoNIF)';
        }

        if ($i->caepf !== null && !preg_match('/^[0-9]{14}$/', $i->caepf)) {
            $errors[] = 'CAEPF do intermediário deve ter 14 dígitos numéricos (TSCAEPF)';
        }

        if ($i->inscricaoMunicipal !== null && (mb_strlen($i->inscricaoMunicipal, 'UTF-8') < 1 || mb_strlen($i->inscricaoMunicipal, 'UTF-8') > 15)) {
            $errors[] = 'IM do intermediário deve ter entre 1 e 15 caracteres (TSInscMun)';
        }

        $intermediarioExterior = $i->codigoPais !== null;

        $hasAddress = !empty($i->codigoMunicipio) || !empty($i->cep) || !empty($i->logradouro)
            || !empty($i->numero) || !empty($i->complemento) || !empty($i->bairro) || !empty($i->uf)
            || $intermediarioExterior;

        if ($hasAddress) {
            // xLgr, nro e xBairro são obrigatórios (minOccurs=1) dentro de <end>.
            if (empty($i->logradouro)) {
                $errors[] = 'xLgr do intermediário é obrigatório quando há endereço (TSLogradouro)';
            } elseif (mb_strlen($i->logradouro, 'UTF-8') > 255) {
                $errors[] = 'xLgr do intermediário deve ter no máximo 255 caracteres (TSLogradouro)';
            }

            if (empty($i->numero)) {
                $errors[] = 'nro do intermediário é obrigatório quando há endereço - use "S/N" se não houver (TSNumeroEndereco)';
            } elseif (mb_strlen($i->numero, 'UTF-8') > 60) {
                $errors[] = 'nro do intermediário deve ter no máximo 60 caracteres (TSNumeroEndereco)';
            }

            if ($i->complemento !== null && mb_strlen($i->complemento, 'UTF-8') > 156) {
                $errors[] = 'xCpl do intermediário deve ter no máximo 156 caracteres (TSComplementoEndereco)';
            }

            if (empty($i->bairro)) {
                $errors[] = 'xBairro do intermediário é obrigatório quando há endereço (TSBairro)';
            } elseif (mb_strlen($i->bairro, 'UTF-8') > 60) {
                $errors[] = 'xBairro do intermediário deve ter no máximo 60 caracteres (TSBairro)';
            }

            // TCEndereco é choice endNac | endExt.
            if ($intermediarioExterior) {
                if (!preg_match('/^[A-Z]{2}$/', $i->codigoPais)) {
                    $errors[] = 'cPais do intermediário deve ser 2 letras maiúsculas (TSCodPaisISO)';
                }
                if ($i->codigoPostalExterior === null || mb_strlen($i->codigoPostalExterior, 'UTF-8') < 1 || mb_strlen($i->codigoPostalExterior, 'UTF-8') > 11) {
                    $errors[] = 'cEndPost do intermediário é obrigatório e deve ter 1 a 11 caracteres para endereço exterior (TSCodigoEndPostal)';
                }
                if ($i->nomeCidadeExterior === null || mb_strlen($i->nomeCidadeExterior, 'UTF-8') < 1 || mb_strlen($i->nomeCidadeExterior, 'UTF-8') > 60) {
                    $errors[] = 'xCidade do intermediário é obrigatória e deve ter 1 a 60 caracteres para endereço exterior (TSCidade)';
                }
                if ($i->estadoProvinciaExterior === null || mb_strlen($i->estadoProvinciaExterior, 'UTF-8') < 1 || mb_strlen($i->estadoProvinciaExterior, 'UTF-8') > 60) {
                    $errors[] = 'xEstProvReg do intermediário é obrigatório e deve ter 1 a 60 caracteres para endereço exterior (TSEstadoProvRegiao)';
                }
            } else {
                if ($i->codigoMunicipio === null || !preg_match('/^[0-9]{7}$/', $i->codigoMunicipio)) {
                    $errors[] = 'cMun do intermediário deve ter 7 dígitos numéricos (TSCodMunIBGE)';
                }
                if ($i->cep === null || !preg_match('/^[0-9]{8}$/', $i->cep)) {
                    $errors[] = 'CEP do intermediário deve ter 8 dígitos numéricos (TSCEP)';
                }
                if (!in_array($i->uf, self::UFS, true)) {
                    $errors[] = 'UF do intermediário inválida (TSUF)';
                }
            }
        }

        if ($i->telefone !== null && !preg_match('/^[0-9]{6,20}$/', $i->telefone)) {
            $errors[] = 'fone do intermediário deve ter 6 a 20 dígitos numéricos (TSTelefone)';
        }

        if ($i->email !== null && (mb_strlen($i->email, 'UTF-8') < 1 || mb_strlen($i->email, 'UTF-8') > 80)) {
            $errors[] = 'email do intermediário deve ter 1 a 80 caracteres (TSEmail)';
        }
    }

    /** @param array<string> $errors */
    private function validateServico(DpsRequest $request, array &$errors): void
    {
        $s = $request->servico;

        if (empty($s->discriminacao)) {
            $errors[] = 'xDescServ é obrigatória (TSDesc2000)';
        } elseif (mb_strlen($s->discriminacao, 'UTF-8') > 2000) {
            $errors[] = 'xDescServ deve ter no máximo 2000 caracteres (TSDesc2000)';
        }

        if (empty($s->codigoTributacao)) {
            $errors[] = 'cTribNac é obrigatório (TSCodTribNac)';
        } elseif (!preg_match('/^[0-9]{6}$/', $s->codigoTributacao)) {
            $errors[] = 'cTribNac deve ter 6 dígitos numéricos (TSCodTribNac)';
        }

        if ($s->codigoTributacaoMunicipal !== null && !preg_match('/^[0-9]{3}$/', $s->codigoTributacaoMunicipal)) {
            $errors[] = 'cTribMun deve ter 3 dígitos numéricos (TCCodTribMun)';
        }

        if ($s->codigoNbs !== null && !preg_match('/^[0-9]{9}$/', $s->codigoNbs)) {
            $errors[] = 'cNBS deve ter 9 dígitos numéricos (TSCodNBS)';
        }

        if ($s->codigoInternoContribuinte !== null && !preg_match('/^[a-zA-Z0-9]{1,20}$/', $s->codigoInternoContribuinte)) {
            $errors[] = 'cIntContrib deve ser alfanumérico de 1 a 20 caracteres (TSCodigoInternoContribuinte)';
        }

        if ($s->valorServicos <= 0) {
            $errors[] = 'vServ deve ser maior que zero (TSDec15V2)';
        }

        if ($s->descontoIncondicionado !== null && $s->descontoIncondicionado < 0) {
            $errors[] = 'vDescIncond não pode ser negativo (TSDec15V2)';
        }

        if ($s->descontoCondicionado !== null && $s->descontoCondicionado < 0) {
            $errors[] = 'vDescCond não pode ser negativo (TSDec15V2)';
        }

        $totalDescontos = ($s->descontoIncondicionado ?? 0.0) + ($s->descontoCondicionado ?? 0.0);
        if ($s->valorServicos > 0 && $totalDescontos >= $s->valorServicos) {
            $errors[] = 'A soma de vDescIncond e vDescCond deve ser menor que vServ (vTotal deve ser positivo)';
        }

        // pAliq é TSDec1V2: 1 dígito inteiro + 2 decimais → máximo 9.99 (cobre o teto legal de ISS de 5%).
        if ($s->aliquotaIss < 0 || $s->aliquotaIss > 9.99) {
            $errors[] = 'pAliq deve estar entre 0 e 9.99 (TSDec1V2)';
        }

        if ($s->valorRecebido !== null && $s->valorRecebido <= 0) {
            $errors[] = 'vReceb deve ser maior que zero (TSDec15V2)';
        }

        $this->validateLocPrest($s, $errors);
        $this->validateObra($s, $errors);
        $this->validateComExterior($s, $errors);
        $this->validateAtvEvento($s, $errors);
        $this->validateInfoCompl($s, $request->tipoEmissao, $errors);
        $this->validateAjusteBC($s, $errors);
        $this->validateTribMun($s, $errors);
        $this->validateTribFed($s, $errors);
        $this->validateTotTrib($s, $errors);
    }

    /** @param array<string> $errors */
    private function validateLocPrest(ServicoRequest $s, array &$errors): void
    {
        $hasLoc = $s->codigoMunicipioPrestacao !== null && $s->codigoMunicipioPrestacao !== '';
        $hasPais = $s->codigoPaisPrestacao !== null && $s->codigoPaisPrestacao !== '';

        if (!$hasLoc && !$hasPais) {
            $errors[] = 'É obrigatório informar cLocPrestacao ou cPaisPrestacao (TCLocPrestacao choice)';
        }
        if ($hasLoc && $hasPais) {
            $errors[] = 'cLocPrestacao e cPaisPrestacao são mutuamente exclusivos (TCLocPrestacao choice)';
        }

        if ($hasLoc && !preg_match('/^[0-9]{7}$/', $s->codigoMunicipioPrestacao)) {
            $errors[] = 'cLocPrestacao deve ter 7 dígitos numéricos (TSCodMunIBGE)';
        }

        if ($hasPais && !preg_match('/^[A-Z]{2}$/', $s->codigoPaisPrestacao)) {
            $errors[] = 'cPaisPrestacao deve ser 2 letras maiúsculas (TSCodPaisISO)';
        }
    }

    /** @param array<string> $errors */
    private function validateObra(ServicoRequest $s, array &$errors): void
    {
        $o = $s->obra;
        if ($o === null) {
            return;
        }

        if ($o->inscImobFisc !== null && (mb_strlen($o->inscImobFisc, 'UTF-8') < 1 || mb_strlen($o->inscImobFisc, 'UTF-8') > 30)) {
            $errors[] = 'inscImobFisc deve ter 1 a 30 caracteres (TSInscImobFisc)';
        }

        $hasCObra = $o->cObra !== null;
        $hasCCIB = $o->cCIB !== null;
        $hasEnd = $o->endereco !== null;
        $choices = ($hasCObra ? 1 : 0) + ($hasCCIB ? 1 : 0) + ($hasEnd ? 1 : 0);

        if ($choices === 0) {
            $errors[] = 'É obrigatório informar cObra, cCIB ou end no grupo obra (TCInfoObra choice)';
        }
        if ($choices > 1) {
            $errors[] = 'cObra, cCIB e end são mutuamente exclusivos no grupo obra (TCInfoObra choice)';
        }

        if ($hasCObra && (mb_strlen($o->cObra, 'UTF-8') < 1 || mb_strlen($o->cObra, 'UTF-8') > 30)) {
            $errors[] = 'cObra deve ter 1 a 30 caracteres (TSCodObra)';
        }

        if ($hasCCIB && mb_strlen($o->cCIB) !== 8) {
            $errors[] = 'cCIB deve ter exatamente 8 caracteres (TSCodCIB)';
        }

        if ($hasEnd) {
            $e = $o->endereco;

            // TCEnderObraEvento abre com xs:choice obrigatória CEP|endExt (exatamente um).
            if ($e->cep !== null && $e->endExt !== null) {
                $errors[] = 'CEP e endExt são mutuamente exclusivos no endereço da obra (choice)';
            }
            if ($e->cep === null && $e->endExt === null) {
                $errors[] = 'É obrigatório informar CEP ou endExt no endereço da obra (choice)';
            }

            if ($e->cep !== null && !preg_match('/^[0-9]{8}$/', $e->cep)) {
                $errors[] = 'CEP do endereço da obra deve ter 8 dígitos numéricos (TSCEP)';
            }

            if ($e->endExt !== null) {
                if (mb_strlen($e->endExt->cEndPost, 'UTF-8') < 1 || mb_strlen($e->endExt->cEndPost, 'UTF-8') > 11) {
                    $errors[] = 'cEndPost do endExt da obra deve ter 1 a 11 caracteres (TSCodigoEndPostal)';
                }
                if (mb_strlen($e->endExt->xCidade, 'UTF-8') < 1 || mb_strlen($e->endExt->xCidade, 'UTF-8') > 60) {
                    $errors[] = 'xCidade do endExt da obra deve ter 1 a 60 caracteres (TSCidade)';
                }
                if (mb_strlen($e->endExt->xEstProvReg, 'UTF-8') < 1 || mb_strlen($e->endExt->xEstProvReg, 'UTF-8') > 60) {
                    $errors[] = 'xEstProvReg do endExt da obra deve ter 1 a 60 caracteres (TSEstadoProvRegiao)';
                }
            }

            if (empty($e->xLgr)) {
                $errors[] = 'xLgr é obrigatório no endereço da obra (TSLogradouro)';
            } elseif (mb_strlen($e->xLgr, 'UTF-8') > 255) {
                $errors[] = 'xLgr deve ter no máximo 255 caracteres (TSLogradouro)';
            }
            if (empty($e->nro)) {
                $errors[] = 'nro é obrigatório no endereço da obra (TSNumeroEndereco)';
            } elseif (mb_strlen($e->nro, 'UTF-8') > 60) {
                $errors[] = 'nro deve ter no máximo 60 caracteres (TSNumeroEndereco)';
            }
            if ($e->xCpl !== null && (mb_strlen($e->xCpl, 'UTF-8') < 1 || mb_strlen($e->xCpl, 'UTF-8') > 156)) {
                $errors[] = 'xCpl deve ter 1 a 156 caracteres (TSComplementoEndereco)';
            }
            if (empty($e->xBairro)) {
                $errors[] = 'xBairro é obrigatório no endereço da obra (TSBairro)';
            } elseif (mb_strlen($e->xBairro, 'UTF-8') > 60) {
                $errors[] = 'xBairro deve ter no máximo 60 caracteres (TSBairro)';
            }
        }
    }

    /** @param array<string> $errors */
    private function validateComExterior(ServicoRequest $s, array &$errors): void
    {
        $ce = $s->comExterior;
        if ($ce === null) {
            return;
        }

        if ($ce->modoPrestacao === null) {
            $errors[] = 'mdPrestacao é obrigatório (TSModoPrestacao)';
        } elseif (!in_array((string) $ce->modoPrestacao, ModoPrestacao::valores(), true)) {
            $errors[] = 'mdPrestacao inválido (TSModoPrestacao)';
        }

        if ($ce->vinculoPrestador === null) {
            $errors[] = 'vincPrest é obrigatório (TSVincPrest)';
        } elseif (!in_array((string) $ce->vinculoPrestador, VinculoPrestador::valores(), true)) {
            $errors[] = 'vincPrest inválido (TSVincPrest)';
        }

        if ($ce->codigoMoeda === null) {
            $errors[] = 'tpMoeda é obrigatório (TSCodMoeda)';
        } elseif (!preg_match('/^[0-9]{3}$/', $ce->codigoMoeda)) {
            $errors[] = 'tpMoeda deve ter 3 dígitos numéricos (TSCodMoeda)';
        }

        if ($ce->valorServicoMoeda === null) {
            $errors[] = 'vServMoeda é obrigatório (TSDec15V2)';
        } elseif ($ce->valorServicoMoeda <= 0) {
            $errors[] = 'vServMoeda deve ser maior que zero (TSDec15V2)';
        }

        if ($ce->mecanismoApoioPrestador === null) {
            $errors[] = 'mecAFComexP é obrigatório (TSMecAFComExPrest)';
        } elseif (!in_array($ce->mecanismoApoioPrestador, MecanismoApoioPrestador::valores(), true)) {
            $errors[] = 'mecAFComexP inválido (TSMecAFComExPrest)';
        }

        if ($ce->mecanismoApoioTomador === null) {
            $errors[] = 'mecAFComexT é obrigatório (TSMecAFComExToma)';
        } elseif (!in_array($ce->mecanismoApoioTomador, MecanismoApoioTomador::valores(), true)) {
            $errors[] = 'mecAFComexT inválido (TSMecAFComExToma)';
        }

        if ($ce->movimentacaoTemporaria === null) {
            $errors[] = 'movTempBens é obrigatório (TSMovTempBens)';
        } elseif (!in_array($ce->movimentacaoTemporaria, MovimentacaoTemporaria::valores(), true)) {
            $errors[] = 'movTempBens inválido (TSMovTempBens)';
        }

        if ($ce->numeroDeclaracaoImportacao !== null && (mb_strlen($ce->numeroDeclaracaoImportacao, 'UTF-8') < 1 || mb_strlen($ce->numeroDeclaracaoImportacao, 'UTF-8') > 12)) {
            $errors[] = 'nDI deve ter 1 a 12 caracteres (TSNumDocImport)';
        }

        if ($ce->numeroRegistroExportacao !== null && (mb_strlen($ce->numeroRegistroExportacao, 'UTF-8') < 1 || mb_strlen($ce->numeroRegistroExportacao, 'UTF-8') > 12)) {
            $errors[] = 'nRE deve ter 1 a 12 caracteres (TSNumRegExport)';
        }

        if ($ce->enviarMDIC === null) {
            $errors[] = 'mdic é obrigatório (TSEnvMDIC)';
        } elseif (!in_array($ce->enviarMDIC, EnviarMdic::valores(), true)) {
            $errors[] = 'mdic inválido (TSEnvMDIC)';
        }
    }

    /** @param array<string> $errors */
    private function validateAtvEvento(ServicoRequest $s, array &$errors): void
    {
        $ae = $s->atvEvento;
        if ($ae === null) {
            return;
        }

        if (empty($ae->descricao)) {
            $errors[] = 'xNome é obrigatório no grupo atvEvento (TSDesc255)';
        } elseif (mb_strlen($ae->descricao, 'UTF-8') > 255) {
            $errors[] = 'xNome deve ter no máximo 255 caracteres (TSDesc255)';
        }

        if (empty($ae->dataInicio)) {
            $errors[] = 'dtIni é obrigatório no grupo atvEvento (TSData)';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ae->dataInicio)) {
            $errors[] = 'dtIni deve estar no formato AAAA-MM-DD (TSData)';
        }

        if (empty($ae->dataFim)) {
            $errors[] = 'dtFim é obrigatório no grupo atvEvento (TSData)';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ae->dataFim)) {
            $errors[] = 'dtFim deve estar no formato AAAA-MM-DD (TSData)';
        }

        if (!empty($ae->dataInicio) && !empty($ae->dataFim) && $ae->dataInicio > $ae->dataFim) {
            $errors[] = 'dtIni não pode ser posterior a dtFim';
        }

        $hasId = $ae->identificacaoEvento !== null && $ae->identificacaoEvento !== '';
        $hasEnd = $ae->endereco !== null;

        if (!$hasId && !$hasEnd) {
            $errors[] = 'É obrigatório informar idAtvEvt ou end no grupo atvEvento (TCAtvEvento choice)';
        }
        if ($hasId && $hasEnd) {
            $errors[] = 'idAtvEvt e end são mutuamente exclusivos no grupo atvEvento (TCAtvEvento choice)';
        }

        if ($hasId && mb_strlen($ae->identificacaoEvento, 'UTF-8') > 30) {
            $errors[] = 'idAtvEvt deve ter 1 a 30 caracteres (TSIdeEvento)';
        }

        if ($hasEnd) {
            $e = $ae->endereco;

            // TCEnderecoSimples abre com xs:choice obrigatória CEP|endExt (exatamente um).
            // O exterior é determinado por codigoPais (vide EmitirDpsService::criarAtvEvento).
            $ehExterior = $e->codigoPais !== null;
            if ($e->cep !== null && $ehExterior) {
                $errors[] = 'CEP e endExt são mutuamente exclusivos no endereço do evento (choice)';
            }
            if ($e->cep === null && !$ehExterior) {
                $errors[] = 'É obrigatório informar CEP ou endExt no endereço do evento (choice)';
            }

            if ($ehExterior) {
                if ($e->codigoPostalExterior === null || mb_strlen($e->codigoPostalExterior, 'UTF-8') < 1 || mb_strlen($e->codigoPostalExterior, 'UTF-8') > 11) {
                    $errors[] = 'cEndPost do endExt do evento deve ter 1 a 11 caracteres (TSCodigoEndPostal)';
                }
                if ($e->nomeCidadeExterior === null || mb_strlen($e->nomeCidadeExterior, 'UTF-8') < 1 || mb_strlen($e->nomeCidadeExterior, 'UTF-8') > 60) {
                    $errors[] = 'xCidade do endExt do evento deve ter 1 a 60 caracteres (TSCidade)';
                }
                if ($e->estadoProvinciaExterior === null || mb_strlen($e->estadoProvinciaExterior, 'UTF-8') < 1 || mb_strlen($e->estadoProvinciaExterior, 'UTF-8') > 60) {
                    $errors[] = 'xEstProvReg do endExt do evento deve ter 1 a 60 caracteres (TSEstadoProvRegiao)';
                }
            }

            if ($e->cep !== null && !preg_match('/^[0-9]{8}$/', $e->cep)) {
                $errors[] = 'CEP do endereço do evento deve ter 8 dígitos numéricos (TSCEP)';
            }
            if (empty($e->logradouro)) {
                $errors[] = 'xLgr é obrigatório no endereço do evento (TSLogradouro)';
            } elseif (mb_strlen($e->logradouro, 'UTF-8') > 255) {
                $errors[] = 'xLgr deve ter no máximo 255 caracteres (TSLogradouro)';
            }
            if (empty($e->numero)) {
                $errors[] = 'nro é obrigatório no endereço do evento (TSNumeroEndereco)';
            } elseif (mb_strlen($e->numero, 'UTF-8') > 60) {
                $errors[] = 'nro deve ter no máximo 60 caracteres (TSNumeroEndereco)';
            }
            if ($e->complemento !== null && (mb_strlen($e->complemento, 'UTF-8') < 1 || mb_strlen($e->complemento, 'UTF-8') > 156)) {
                $errors[] = 'xCpl deve ter 1 a 156 caracteres (TSComplementoEndereco)';
            }
            if (empty($e->bairro)) {
                $errors[] = 'xBairro é obrigatório no endereço do evento (TSBairro)';
            } elseif (mb_strlen($e->bairro, 'UTF-8') > 60) {
                $errors[] = 'xBairro deve ter no máximo 60 caracteres (TSBairro)';
            }
        }
    }

    /** @param array<string> $errors */
    private function validateInfoCompl(ServicoRequest $s, int $tipoEmissao, array &$errors): void
    {
        $ic = $s->infoCompl;

        $emitidaPorTomadorOuInterm = in_array($tipoEmissao, [2, 3], true);
        if ($emitidaPorTomadorOuInterm && ($ic === null || $ic->docReferencia === null || $ic->docReferencia === '')) {
            $errors[] = 'docRef é obrigatório quando a NFS-e é emitida pelo Tomador ou Intermediário (tpEmit=2 ou 3)';
        }

        if ($ic === null) {
            return;
        }

        if ($ic->idDocTecnico !== null && (mb_strlen($ic->idDocTecnico, 'UTF-8') < 1 || mb_strlen($ic->idDocTecnico, 'UTF-8') > 40)) {
            $errors[] = 'idDocTec deve ter 1 a 40 caracteres (TSDRT)';
        }

        if ($ic->docReferencia !== null && (mb_strlen($ic->docReferencia, 'UTF-8') < 1 || mb_strlen($ic->docReferencia, 'UTF-8') > 255)) {
            $errors[] = 'docRef deve ter 1 a 255 caracteres (TSDesc255)';
        }

        if ($ic->numeroPedido !== null && (mb_strlen($ic->numeroPedido, 'UTF-8') < 1 || mb_strlen($ic->numeroPedido, 'UTF-8') > 60)) {
            $errors[] = 'xPed deve ter 1 a 60 caracteres (TSNumeroEndereco)';
        }

        if ($ic->itensPedido !== null) {
            if (count($ic->itensPedido) > 99) {
                $errors[] = 'gItemPed deve conter no máximo 99 itens (xItemPed maxOccurs=99)';
            }
            foreach ($ic->itensPedido as $i => $item) {
                if (mb_strlen($item, 'UTF-8') < 1 || mb_strlen($item, 'UTF-8') > 60) {
                    $errors[] = "xItemPed #{$i} deve ter 1 a 60 caracteres (TSNumeroEndereco)";
                }
            }
        }

        if ($ic->infoComplementar !== null && (mb_strlen($ic->infoComplementar, 'UTF-8') < 1 || mb_strlen($ic->infoComplementar, 'UTF-8') > 2000)) {
            $errors[] = 'xInfComp deve ter 1 a 2000 caracteres (TSDescInfCompl)';
        }
    }

    /**
     * Valida o grupo vAjusteBC (TCInfoAjusteBC, NT 009 item 2.3): ajuste percentual OU por valor
     * do ISSQN (choice opcional) e documentos de ajuste (opcional, 1 a 1000).
     *
     * @param array<string> $errors
     */
    private function validateAjusteBC(ServicoRequest $s, array &$errors): void
    {
        $temPercentual = $s->percentualAjusteBcIssqn !== null;
        $temValor = $s->valorAjusteBcIssqn !== null;
        $docs = $s->documentosAjusteBc;

        if ($temPercentual && $temValor) {
            $errors[] = 'pAjusteBCISSQN e vAjusteBCISSQN são mutuamente exclusivos (TCInfoAjusteBC choice)';
        }

        if ($temPercentual && ($s->percentualAjusteBcIssqn <= 0 || $s->percentualAjusteBcIssqn > 100)) {
            $errors[] = 'E0453: pAjusteBCISSQN deve ser maior que 0 e menor ou igual a 100 (TSDec3V2)';
        }

        if ($temValor && $s->valorAjusteBcIssqn <= 0) {
            $errors[] = 'vAjusteBCISSQN deve ser maior que zero (TSDec15V2)';
        }

        if ($temValor && $s->valorAjusteBcIssqn > $s->valorServicos) {
            $errors[] = 'vAjusteBCISSQN não pode exceder vServ (base de cálculo negativa)';
        }

        if ($docs === null) {
            $this->validateBaseIssqnNaoNegativa($s, 0.0, $errors);

            return;
        }

        if ($docs === []) {
            $errors[] = 'documentos de vAjusteBC deve conter ao menos um docAjusteBC (minOccurs=1)';
            $this->validateBaseIssqnNaoNegativa($s, 0.0, $errors);

            return;
        }

        if (count($docs) > 1000) {
            $errors[] = 'docAjusteBC deve conter no máximo 1000 documentos (maxOccurs=1000)';
        }

        $somaAjusteIssqn = 0.0;

        foreach ($docs as $i => $doc) {
            $pfx = "docAjusteBC #{$i}";
            $tipo = null;

            if ($doc->tpAjusteBC === null || $doc->tpAjusteBC === '') {
                $errors[] = "{$pfx}: tpAjusteBC é obrigatório (TSRTCTpAjusteBC)";
            } else {
                $tipo = TipoAjusteBC::tryFrom($doc->tpAjusteBC);
                if ($tipo === null) {
                    $errors[] = "{$pfx}: tpAjusteBC inválido (TSRTCTpAjusteBC)";
                }
            }

            if ($tipo !== null) {
                if ($tipo->exigeDescricao() && ($doc->xTpAjusteBC === null || trim($doc->xTpAjusteBC) === '')) {
                    $errors[] = "{$pfx}: xTpAjusteBC é obrigatório quando tpAjusteBC = {$tipo->value}";
                }
                if (!$tipo->exigeDescricao() && $doc->xTpAjusteBC !== null) {
                    $errors[] = "{$pfx}: E0464: xTpAjusteBC só pode ser informado quando tpAjusteBC = 99 ou 199";
                }
                if ($tipo->exigeDocumentoFiscal() && $doc->tipoDocumento === DocAjusteBC::DOC_OUTRO) {
                    $errors[] = "{$pfx}: E0479: tpAjusteBC {$tipo->value} exige documento fiscal (dFeNacional ou docFiscalOutro), não docOutro";
                }
                if (($temPercentual || $temValor) && $tipo->exclusivoIssqn()) {
                    $errors[] = "{$pfx}: E0462: tpAjusteBC {$tipo->value} não pode ser informado por documento quando há ajuste por percentual ou valor (pAjusteBCISSQN/vAjusteBCISSQN)";
                }
            }

            if ($doc->xTpAjusteBC !== null && (trim($doc->xTpAjusteBC) === '' || mb_strlen($doc->xTpAjusteBC, 'UTF-8') > 150)) {
                $errors[] = "{$pfx}: xTpAjusteBC deve ter 1 a 150 caracteres (TSDesc150)";
            }

            $totalValido = $this->validateValorDec15V2($doc->vTotDoc, 'vTotDoc', $pfx, $errors);
            $aplicadoValido = $this->validateValorDec15V2($doc->vAjusteAplic, 'vAjusteAplic', $pfx, $errors);

            if ($totalValido && $aplicadoValido) {
                if ((float) $doc->vAjusteAplic > (float) $doc->vTotDoc) {
                    $errors[] = "{$pfx}: vAjusteAplic deve ser menor ou igual a vTotDoc";
                }
                if ((float) $doc->vAjusteAplic > $s->valorServicos) {
                    $errors[] = "{$pfx}: E0467: vAjusteAplic deve ser menor ou igual ao valor do serviço prestado (vServ)";
                }
                if ($tipo !== null && $tipo->ajustaBaseIssqn()) {
                    $somaAjusteIssqn += (float) $doc->vAjusteAplic;
                }
            }

            $this->validateDatasDocAjuste($doc, $pfx, $errors);
            $this->validateDocumentoComprobatorio($doc, $pfx, $errors);
            $this->validateIbsCbsFornecedor($doc->fornec, $pfx, $errors);
        }

        $this->validateBaseIssqnNaoNegativa($s, $somaAjusteIssqn, $errors);
    }

    /**
     * vBC = vServ - vDescIncond - ajustes (RN E1295): os ajustes do ISSQN (percentual ou valor
     * padrão mais os documentos que repercutem no ISSQN) e o desconto incondicional não podem
     * somar mais que vServ, senão a base de cálculo ficaria negativa.
     *
     * @param array<string> $errors
     */
    private function validateBaseIssqnNaoNegativa(ServicoRequest $s, float $somaDocumentosIssqn, array &$errors): void
    {
        $ajuste = $somaDocumentosIssqn;
        if ($s->valorAjusteBcIssqn !== null) {
            $ajuste += $s->valorAjusteBcIssqn;
        }
        if ($s->percentualAjusteBcIssqn !== null) {
            $ajuste += $s->valorServicos * $s->percentualAjusteBcIssqn / 100;
        }

        if ($ajuste + ($s->descontoIncondicionado ?? 0.0) > $s->valorServicos) {
            $errors[] = 'A soma dos ajustes da base de cálculo do ISSQN e do desconto incondicionado não pode exceder vServ (base de cálculo negativa)';
        }
    }

    /**
     * @param array<string> $errors
     * @return bool true quando o valor foi informado e segue o padrão TSDec15V2
     */
    private function validateValorDec15V2(?string $valor, string $campo, string $pfx, array &$errors): bool
    {
        if ($valor === null || $valor === '') {
            $errors[] = "{$pfx}: {$campo} é obrigatório (TSDec15V2)";

            return false;
        }

        if (!self::isDec15V2($valor)) {
            $errors[] = "{$pfx}: {$campo} deve seguir o padrão decimal de 2 casas (TSDec15V2)";

            return false;
        }

        return true;
    }

    /** @param array<string> $errors */
    private function validateDatasDocAjuste(DocAjusteBCRequest $doc, string $pfx, array &$errors): void
    {
        $emissao = null;
        $competencia = null;

        foreach (['dtEmiDoc' => $doc->dtEmiDoc, 'dtCompDoc' => $doc->dtCompDoc] as $campo => $valor) {
            if ($valor === null) {
                continue;
            }
            $data = preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) === 1
                ? \DateTimeImmutable::createFromFormat('!Y-m-d', $valor)
                : false;
            if ($data === false || $data->format('Y-m-d') !== $valor) {
                $errors[] = "{$pfx}: {$campo} deve ser uma data válida no formato AAAA-MM-DD (TSData)";
                continue;
            }
            if ($campo === 'dtEmiDoc') {
                $emissao = $data;
            } else {
                $competencia = $data;
            }
        }

        if ($emissao !== null && $competencia !== null && $emissao < $competencia) {
            $errors[] = "{$pfx}: E0472: dtEmiDoc deve ser igual ou posterior a dtCompDoc";
        }

        if ($doc->tipoDocumento === DocAjusteBC::DOC_FISCAL_OUTRO) {
            $referencia = $competencia ?? $emissao;
            if ($referencia !== null && $referencia >= new \DateTimeImmutable('2026-01-01')) {
                $errors[] = "{$pfx}: E0476: docFiscalOutro só pode ser informado se dtCompDoc for anterior a 01/01/2026 (sem dtCompDoc, vale a data de emissão)";
            }
        }
    }

    /**
     * Valida o documento que comprova o ajuste: exatamente um entre dFeNacional,
     * docFiscalOutro e docOutro (choice de TCDocAjusteBC).
     *
     * @param array<string> $errors
     */
    private function validateDocumentoComprobatorio(DocAjusteBCRequest $doc, string $pfx, array &$errors): void
    {
        if (!in_array($doc->tipoDocumento, [DocAjusteBC::DOC_DFE_NACIONAL, DocAjusteBC::DOC_FISCAL_OUTRO, DocAjusteBC::DOC_OUTRO], true)) {
            $errors[] = "{$pfx}: tipoDocumento inválido (use dFeNacional, docFiscalOutro ou docOutro)";

            return;
        }

        if ($doc->tipoDocumento === DocAjusteBC::DOC_DFE_NACIONAL) {
            if (!in_array($doc->tipoChaveDFe, TipoChaveDocumentoFiscal::valores(), true)) {
                $errors[] = "{$pfx}: tipoChaveDFe inválido (TSRTCTipoChaveDFe)";
            }
            if (empty($doc->chaveDFe)) {
                $errors[] = "{$pfx}: chaveDFe é obrigatória para dFeNacional (TSRTCChaveDFe)";
            } elseif (mb_strlen($doc->chaveDFe, 'UTF-8') > 50) {
                $errors[] = "{$pfx}: chaveDFe deve ter no máximo 50 caracteres (TSRTCChaveDFe)";
            } elseif ($doc->tipoChaveDFe === '1' && !preg_match(ChaveAcesso::PADRAO, $doc->chaveDFe)) {
                $errors[] = "{$pfx}: E0460: chaveDFe de NFS-e deve ter 50 posições, com letras A-Z somente na inscrição federal";
            } elseif (in_array($doc->tipoChaveDFe, ['2', '3'], true) && !preg_match('/^[0-9]{6}[0-9A-Z]{14}[0-9]{24}$/', $doc->chaveDFe)) {
                $errors[] = "{$pfx}: E0460: chaveDFe de NF-e/CT-e deve ter 44 posições, com letras A-Z somente na inscrição federal (TSChaveNFe)";
            }
            if ($doc->tipoChaveDFe === '9' && empty($doc->xTipoChaveDFe)) {
                $errors[] = "{$pfx}: E0470: xTipoChaveDFe é obrigatório quando tipoChaveDFe=9 (TSDesc255)";
            }
            if ($doc->xTipoChaveDFe !== null && (mb_strlen($doc->xTipoChaveDFe, 'UTF-8') < 1 || mb_strlen($doc->xTipoChaveDFe, 'UTF-8') > 255)) {
                $errors[] = "{$pfx}: xTipoChaveDFe deve ter 1 a 255 caracteres (TSDesc255)";
            }
        }

        if ($doc->tipoDocumento === DocAjusteBC::DOC_FISCAL_OUTRO) {
            if (!preg_match('/^\d{7}$/', $doc->cMunDocFiscal ?? '')) {
                $errors[] = "{$pfx}: cMunDocFiscal deve ter 7 dígitos numéricos (TSCodMunIBGE)";
            }
            foreach (['nDocFiscal' => $doc->nDocFiscal, 'xDocFiscal' => $doc->xDocFiscal] as $campo => $valor) {
                if ($valor === null || $valor === '') {
                    $errors[] = "{$pfx}: {$campo} é obrigatório para docFiscalOutro (TSDesc255)";
                } elseif (mb_strlen($valor, 'UTF-8') > 255) {
                    $errors[] = "{$pfx}: {$campo} deve ter no máximo 255 caracteres (TSDesc255)";
                }
            }
        }

        if ($doc->tipoDocumento === DocAjusteBC::DOC_OUTRO) {
            foreach (['nDoc' => $doc->nDoc, 'xDoc' => $doc->xDoc] as $campo => $valor) {
                if ($valor === null || $valor === '') {
                    $errors[] = "{$pfx}: {$campo} é obrigatório para docOutro (TSDesc255)";
                } elseif (mb_strlen($valor, 'UTF-8') > 255) {
                    $errors[] = "{$pfx}: {$campo} deve ter no máximo 255 caracteres (TSDesc255)";
                }
            }
        }
    }

    /**
     * Valida o padrão oficial TSDec15V2: 1 a 15 dígitos inteiros mais 2 casas decimais
     * exatas (ou inteiro sem decimais). Pattern idêntico ao do XSD.
     */
    private static function isDec15V2(string $value): bool
    {
        return preg_match('/^(0|0\.[0-9]{2}|[1-9][0-9]{0,14}(\.[0-9]{2})?)$/', $value) === 1;
    }

    /** @param array<string> $errors */
    private function validateTribMun(ServicoRequest $s, array &$errors): void
    {
        if ($s->tribISSQN === null) {
            $errors[] = 'tribISSQN é obrigatório (TSTribISSQN)';
        } elseif (!in_array($s->tribISSQN, TributacaoIssqn::valores(), true)) {
            $errors[] = 'tribISSQN inválido (TSTribISSQN)';
        }

        if ($s->codigoPaisResultado !== null && !preg_match('/^[A-Z]{2}$/', $s->codigoPaisResultado)) {
            $errors[] = 'cPaisResult deve ser 2 letras maiúsculas (TSCodPaisISO)';
        }

        if ($s->tipoImunidade !== null && !in_array($s->tipoImunidade, [0,1,2,3,4,5], true)) {
            $errors[] = 'tpImunidade inválido (TSTipoImunidadeISSQN)';
        }

        if ($s->exigSusp !== null) {
            $es = $s->exigSusp;
            if ($es->tipoSuspensao === null) {
                $errors[] = 'tpSusp é obrigatório (TSOpExigSuspensa)';
            } elseif (!in_array($es->tipoSuspensao, [1,2], true)) {
                $errors[] = 'tpSusp inválido (TSOpExigSuspensa)';
            }
            if ($es->numeroProcesso === null || $es->numeroProcesso === '') {
                $errors[] = 'nProcesso é obrigatório (TSNumProcExigSuspensa)';
            } elseif (!preg_match('/^[0-9]{30}$/', $es->numeroProcesso)) {
                $errors[] = 'nProcesso deve ter 30 dígitos numéricos (TSNumProcExigSuspensa)';
            }
        }

        if ($s->beneficioMunicipal !== null) {
            $bm = $s->beneficioMunicipal;
            if (empty($bm->numeroBeneficio)) {
                $errors[] = 'nBM é obrigatório (TSNumBeneficioMunicipal)';
            } elseif (!preg_match('/^[0-9]{14}$/', $bm->numeroBeneficio)) {
                $errors[] = 'nBM deve ter 14 dígitos numéricos (TSNumBeneficioMunicipal)';
            }

            $hasVRed = $bm->valorReducaoBC !== null;
            $hasPRed = $bm->percentualReducaoBC !== null;

            if ($hasVRed && $hasPRed) {
                $errors[] = 'vRedBCBM e pRedBCBM são mutuamente exclusivos (TCBM choice)';
            }

            if ($hasVRed && $bm->valorReducaoBC <= 0) {
                $errors[] = 'vRedBCBM deve ser maior que zero (TSDec15V2)';
            }

            if ($hasPRed && ($bm->percentualReducaoBC < 0 || $bm->percentualReducaoBC > 100)) {
                $errors[] = 'pRedBCBM deve estar entre 0 e 100 (TSDec3V2)';
            }

            if ($hasVRed && $bm->valorReducaoBC > $s->valorServicos) {
                $errors[] = 'vRedBCBM não pode exceder vServ (base de cálculo negativa)';
            }
        }

        if ($s->tpRetISSQN === null) {
            $errors[] = 'tpRetISSQN é obrigatório (TSTipoRetISSQN)';
        } elseif (!in_array($s->tpRetISSQN, TipoRetencaoIssqn::valores(), true)) {
            $errors[] = 'tpRetISSQN inválido (TSTipoRetISSQN)';
        }
    }

    /** @param array<string> $errors */
    private function validateTribFed(ServicoRequest $s, array &$errors): void
    {
        $tf = $s->tribFederal;
        if ($tf === null) {
            return;
        }

        if ($tf->pisCofinsCst !== null && !in_array($tf->pisCofinsCst, self::CST_PISCOFINS, true)) {
            $errors[] = 'CST do PIS/COFINS inválido - deve ser um dos códigos do domínio oficial (TSTipoCST)';
        }

        // pAliqPis/pAliqCofins são TSDec2V2: até 2 dígitos inteiros + 2 decimais → máximo 99.99.
        if ($tf->pisCofinsAliquotaPis !== null && ($tf->pisCofinsAliquotaPis < 0 || $tf->pisCofinsAliquotaPis > 99.99)) {
            $errors[] = 'pAliqPis deve estar entre 0 e 99.99 (TSDec2V2)';
        }

        if ($tf->pisCofinsAliquotaCofins !== null && ($tf->pisCofinsAliquotaCofins < 0 || $tf->pisCofinsAliquotaCofins > 99.99)) {
            $errors[] = 'pAliqCofins deve estar entre 0 e 99.99 (TSDec2V2)';
        }

        // CST é minOccurs=1 no grupo piscofins (TCTribOutrosPisCofins). O builder emite o grupo quando
        // CST OU pAliqPis está presente - espelhamos essa condição para exigir CST sempre que o grupo sair.
        if ($tf->pisCofinsAliquotaPis !== null && $tf->pisCofinsCst === null) {
            $errors[] = 'CST do PIS/COFINS é obrigatório quando pAliqPis é informado (TCTribOutrosPisCofins)';
        }

        if ($tf->pisCofinsCst !== null && $tf->pisCofinsTipo === null) {
            $errors[] = 'tipo do PIS/COFINS é obrigatório quando CST é informado (TSTipoRetPISCofins)';
        }

        if ($tf->pisCofinsTipo !== null && !in_array($tf->pisCofinsTipo, ['0','1','2','3','4','5','6','7','8','9'], true)) {
            $errors[] = 'tipo do PIS/COFINS inválido (TSTipoRetPISCofins)';
        }
    }

    /** @param array<string> $errors */
    private function validateTotTrib(ServicoRequest $s, array &$errors): void
    {
        $validTypes = ['vTotTrib', 'pTotTrib', 'indTotTrib', 'pTotTribSN'];
        if ($s->totTribTipo === null || !in_array($s->totTribTipo, $validTypes, true)) {
            $errors[] = 'totTribTipo é obrigatório - deve ser vTotTrib, pTotTrib, indTotTrib ou pTotTribSN (TCTribTotal choice)';

            return;
        }

        if ($s->totTribTipo === 'vTotTrib') {
            if ($s->vTotTribFed === null) {
                $errors[] = 'vTotTribFed é obrigatório quando totTribTipo=vTotTrib (TSDec15V2)';
            }
            if ($s->vTotTribEst === null) {
                $errors[] = 'vTotTribEst é obrigatório quando totTribTipo=vTotTrib (TSDec15V2)';
            }
            if ($s->vTotTribMun === null) {
                $errors[] = 'vTotTribMun é obrigatório quando totTribTipo=vTotTrib (TSDec15V2)';
            }
            foreach (['vTotTribFed' => $s->vTotTribFed, 'vTotTribEst' => $s->vTotTribEst, 'vTotTribMun' => $s->vTotTribMun] as $campo => $valor) {
                if ($valor !== null && $valor < 0) {
                    $errors[] = "{$campo} não pode ser negativo (TSDec15V2)";
                }
            }
        }

        if ($s->totTribTipo === 'pTotTrib') {
            if ($s->pTotTribFed === null) {
                $errors[] = 'pTotTribFed é obrigatório quando totTribTipo=pTotTrib (TSDec3V2)';
            }
            if ($s->pTotTribEst === null) {
                $errors[] = 'pTotTribEst é obrigatório quando totTribTipo=pTotTrib (TSDec3V2)';
            }
            if ($s->pTotTribMun === null) {
                $errors[] = 'pTotTribMun é obrigatório quando totTribTipo=pTotTrib (TSDec3V2)';
            }
            // pTotTribFed/Est/Mun são TSDec3V2 → máximo 999.99.
            foreach (['pTotTribFed' => $s->pTotTribFed, 'pTotTribEst' => $s->pTotTribEst, 'pTotTribMun' => $s->pTotTribMun] as $campo => $valor) {
                if ($valor !== null && ($valor < 0 || $valor > 999.99)) {
                    $errors[] = "{$campo} deve estar entre 0 e 999.99 (TSDec3V2)";
                }
            }
        }

        if ($s->totTribTipo === 'indTotTrib' && $s->indTotTrib === null) {
            $errors[] = 'indTotTrib é obrigatório quando totTribTipo=indTotTrib (TSTipoIndTotTrib)';
        }

        if ($s->totTribTipo === 'pTotTribSN') {
            if ($s->pTotTribSN === null) {
                $errors[] = 'pTotTribSN é obrigatório quando totTribTipo=pTotTribSN (TSDec2V2)';
            } elseif ($s->pTotTribSN < 0 || $s->pTotTribSN > 99.99) {
                // pTotTribSN é TSDec2V2 → máximo 99.99.
                $errors[] = 'pTotTribSN deve estar entre 0 e 99.99 (TSDec2V2)';
            }
        }
    }

    /** @param array<string> $errors */
    private function validateIbsCbs(DpsRequest $request, array &$errors): void
    {
        $req = $request->ibscbs;
        if ($req === null) {
            return;
        }

        $dataCompetencia = new \DateTimeImmutable($request->dataCompetencia);
        $dataLimite = new \DateTimeImmutable('2026-01-01');
        if ($dataCompetencia < $dataLimite) {
            $errors[] = 'E0850: IBS/CBS permitido somente a partir de 01/01/2026';
        }

        if (empty($request->servico->codigoNbs)) {
            $errors[] = 'E1508: Código NBS é obrigatório quando IBS/CBS é declarado';
        }

        // ind_gIBSCBS (NT 009, item 2.16): sem grupo de tributação, cIndOp e gIBSCBS são vedados
        // (RN E0859 e E0960/E0961); com grupo, cIndOp é obrigatório (RN E0860).
        $temGrupoTributacao = $req->informarGIBSCBS ?? !in_array($req->cst, IbsCbsInfo::CST_SEM_GRUPO_TRIBUTACAO, true);
        $temCIndOp = $req->cIndOp !== null && $req->cIndOp !== '';

        if ($temCIndOp && !preg_match('/^[0-9]{6}$/', $req->cIndOp)) {
            $errors[] = 'cIndOp deve ter 6 dígitos numéricos (TSRTCCodIndOp)';
        }
        if ($temGrupoTributacao && !$temCIndOp) {
            $errors[] = 'E0860: cIndOp é obrigatório quando há tributação IBS/CBS (TSRTCCodIndOp)';
        }
        if (!$temGrupoTributacao) {
            if ($temCIndOp) {
                $errors[] = 'E0859: cIndOp não deve ser informado quando não há tributação IBS/CBS (CST ' . $req->cst . ')';
            }
            if ($req->cCredPres !== null || $req->tribRegular !== null || $req->diferimento !== null) {
                $errors[] = 'E0961: cCredPres, gTribRegular e gDif não devem ser informados quando não há tributação IBS/CBS (CST ' . $req->cst . ')';
            }
        }

        if ($req->indDest === '') {
            $errors[] = 'indDest é obrigatório (TSRTCIndDest)';
        } elseif (!in_array($req->indDest, IndicadorDestinacao::valores(), true)) {
            $errors[] = 'indDest inválido (TSRTCIndDest)';
        }

        if ($req->cst === '') {
            $errors[] = 'CST é obrigatório (TSRTCCodSitTrib)';
        } elseif (!preg_match('/^[0-9]{3}$/', $req->cst)) {
            $errors[] = 'CST deve ter 3 dígitos numéricos (TSRTCCodSitTrib)';
        }

        if ($req->cClassTrib === '') {
            $errors[] = 'cClassTrib é obrigatório (TSRTCCodClassTrib)';
        } elseif (!preg_match('/^[0-9]{6}$/', $req->cClassTrib)) {
            $errors[] = 'cClassTrib deve ter 6 dígitos numéricos (TSRTCCodClassTrib)';
        }

        if ($req->cst !== '' && $req->cClassTrib !== '' && substr($req->cClassTrib, 0, 3) !== $req->cst) {
            $errors[] = 'E0959: 3 primeiros dígitos de cClassTrib devem ser iguais ao CST';
        }

        if ($req->indFinal !== null && $req->indFinal !== '' && !in_array($req->indFinal, IndicadorFinal::valores(), true)) {
            $errors[] = 'indFinal inválido (TSRTCIndFinal)';
        }

        if ($req->tpOper !== null && $req->tpOper !== '' && !in_array($req->tpOper, TipoOperacao::valores(), true)) {
            $errors[] = 'tpOper inválido (TSRTCTpOper)';
        }

        if ($req->tpEnteGov !== null && $req->tpEnteGov !== '' && !in_array($req->tpEnteGov, TipoEnteGovernamental::valores(), true)) {
            $errors[] = 'tpEnteGov inválido (TSRTCTpEnteGov)';
        }

        if ($req->cCredPres !== null && !preg_match('/^[0-9]{2}$/', $req->cCredPres)) {
            $errors[] = 'cCredPres deve ter 2 dígitos numéricos (TSRTCCodCredPres)';
        }

        if ($req->tribRegular !== null) {
            if (substr($req->tribRegular->cClassTribReg, 0, 3) !== $req->tribRegular->cstReg) {
                $errors[] = 'E0970: 3 primeiros dígitos de cClassTribReg devem ser iguais ao CSTReg';
            }
        }

        if ($req->indDest === '0' && $req->dest !== null) {
            $errors[] = 'E0910: destinatário não deve ser informado quando indDest=0';
        }
        if ($req->indDest === '1' && $req->dest === null) {
            $errors[] = 'E0910: destinatário deve ser informado quando indDest=1';
        }

        $this->validateIbsCbsDest($req, $errors);
        $this->validateIbsCbsImovel($request, $errors);
        $this->validateIbsCbsBensMoveis($request, $errors);
        $this->validateIbsCbsRefNFSe($req, $errors);
        $this->validateIbsCbsDiferimento($req, $errors);
        $this->validateIbsCbsTribRegular($req, $errors);
        $this->validateIbsCbsCstClassTrib($req, $errors);
    }

    /** @param array<string> $errors */
    private function validateIbsCbsDest(IbsCbsRequest $req, array &$errors): void
    {
        $dest = $req->dest;
        if ($dest === null) {
            return;
        }

        if (empty($dest->xNome)) {
            $errors[] = 'xNome do destinatário é obrigatório (TSDesc150)';
        } elseif (mb_strlen($dest->xNome, 'UTF-8') > 150) {
            $errors[] = 'xNome do destinatário deve ter no máximo 150 caracteres (TSDesc150)';
        }

        $idCount = 0;
        if ($dest->cnpj !== null) {
            $idCount++;
        }
        if ($dest->cpf !== null) {
            $idCount++;
        }
        if ($dest->nif !== null) {
            $idCount++;
        }
        if ($dest->codigoNaoNif !== null) {
            $idCount++;
        }

        if ($idCount === 0) {
            $errors[] = 'Destinatário deve ter CNPJ, CPF, NIF ou cNaoNIF (TCRTCInfoDest choice)';
        }
        if ($idCount > 1) {
            $errors[] = 'Destinatário: CNPJ, CPF, NIF e cNaoNIF são mutuamente exclusivos';
        }

        if ($dest->cnpj !== null && !preg_match(Cnpj::PADRAO, $dest->cnpj)) {
            $errors[] = 'CNPJ do destinatário deve ter 14 caracteres: 12 de raiz e ordem (dígitos ou letras A-Z) e 2 dígitos verificadores (TSCNPJ)';
        }
        if ($dest->cpf !== null && !preg_match('/^[0-9]{11}$/', $dest->cpf)) {
            $errors[] = 'CPF do destinatário deve ter 11 dígitos numéricos (TSCPF)';
        }
        if ($dest->nif !== null && (mb_strlen($dest->nif, 'UTF-8') < 1 || mb_strlen($dest->nif, 'UTF-8') > 40)) {
            $errors[] = 'NIF do destinatário deve ter 1 a 40 caracteres (TSNIF)';
        }
        if ($dest->codigoNaoNif !== null && !in_array($dest->codigoNaoNif, CausaNaoNif::valores(), true)) {
            $errors[] = 'cNaoNIF do destinatário inválido (TSCodNaoNIF)';
        }

        $destEhExterior = $dest->codigoPais !== null
            || $dest->codigoPostalExterior !== null
            || $dest->nomeCidadeExterior !== null
            || $dest->estadoProvinciaExterior !== null;

        $temEnderecoNac = $dest->codigoMunicipio !== null
            || $dest->uf !== null
            || $dest->cep !== null;

        $temEnderecoDest = $dest->logradouro !== null
            || $dest->numero !== null
            || $dest->complemento !== null
            || $dest->bairro !== null
            || $temEnderecoNac
            || $destEhExterior;

        if ($temEnderecoDest) {
            // Comuns ao TCEndereco (válidos para endNac e endExt).
            if ($dest->logradouro === null || mb_strlen($dest->logradouro, 'UTF-8') < 1 || mb_strlen($dest->logradouro, 'UTF-8') > 255) {
                $errors[] = 'xLgr do destinatário é obrigatório quando o endereço é informado e deve ter 1 a 255 caracteres (TSLogradouro)';
            }
            if ($dest->numero === null || mb_strlen($dest->numero, 'UTF-8') < 1 || mb_strlen($dest->numero, 'UTF-8') > 60) {
                $errors[] = 'nro do destinatário é obrigatório quando o endereço é informado e deve ter 1 a 60 caracteres (TSNumeroEndereco)';
            }
            if ($dest->complemento !== null && (mb_strlen($dest->complemento, 'UTF-8') < 1 || mb_strlen($dest->complemento, 'UTF-8') > 156)) {
                $errors[] = 'xCpl do destinatário deve ter 1 a 156 caracteres (TSComplementoEndereco)';
            }
            if ($dest->bairro === null || mb_strlen($dest->bairro, 'UTF-8') < 1 || mb_strlen($dest->bairro, 'UTF-8') > 60) {
                $errors[] = 'xBairro do destinatário é obrigatório quando o endereço é informado e deve ter 1 a 60 caracteres (TSBairro)';
            }

            if ($destEhExterior) {
                if ($temEnderecoNac) {
                    $errors[] = 'Destinatário: endereço nacional (cMun/CEP/UF) e exterior são mutuamente exclusivos (TCEndereco choice)';
                }
                if ($dest->codigoPais === null || !preg_match('/^[A-Z]{2}$/', $dest->codigoPais)) {
                    $errors[] = 'cPais do destinatário é obrigatório no endExt e deve ter 2 letras maiúsculas (TSCodPaisISO)';
                }
                if ($dest->codigoPostalExterior === null || mb_strlen($dest->codigoPostalExterior, 'UTF-8') < 1 || mb_strlen($dest->codigoPostalExterior, 'UTF-8') > 11) {
                    $errors[] = 'cEndPost do destinatário é obrigatório no endExt e deve ter 1 a 11 caracteres (TSCodigoEndPostal)';
                }
                if ($dest->nomeCidadeExterior === null || mb_strlen($dest->nomeCidadeExterior, 'UTF-8') < 1 || mb_strlen($dest->nomeCidadeExterior, 'UTF-8') > 60) {
                    $errors[] = 'xCidade do destinatário é obrigatório no endExt e deve ter 1 a 60 caracteres (TSCidade)';
                }
                if ($dest->estadoProvinciaExterior === null || mb_strlen($dest->estadoProvinciaExterior, 'UTF-8') < 1 || mb_strlen($dest->estadoProvinciaExterior, 'UTF-8') > 60) {
                    $errors[] = 'xEstProvReg do destinatário é obrigatório no endExt e deve ter 1 a 60 caracteres (TSEstadoProvRegiao)';
                }
            } else {
                // Ramo endNac (TCEnderNac): cMun + CEP obrigatórios.
                if ($dest->codigoMunicipio === null || !preg_match('/^[0-9]{7}$/', $dest->codigoMunicipio)) {
                    $errors[] = 'cMun do destinatário é obrigatório no endNac e deve ter 7 dígitos numéricos (TSCodMunIBGE)';
                }
                if ($dest->cep === null || !preg_match('/^[0-9]{8}$/', $dest->cep)) {
                    $errors[] = 'CEP do destinatário é obrigatório no endNac e deve ter 8 dígitos numéricos (TSCEP)';
                }
                if ($dest->uf !== null && !in_array($dest->uf, self::UFS, true)) {
                    $errors[] = 'UF do destinatário inválida (TSUF)';
                }
            }
        }
        if ($dest->fone !== null && !preg_match('/^[0-9]{6,20}$/', $dest->fone)) {
            $errors[] = 'fone do destinatário deve ter 6 a 20 dígitos numéricos (TSTelefone)';
        }
        if ($dest->email !== null && (mb_strlen($dest->email, 'UTF-8') < 1 || mb_strlen($dest->email, 'UTF-8') > 80)) {
            $errors[] = 'email do destinatário deve ter 1 a 80 caracteres (TSEmail)';
        }
    }

    /**
     * Valida o grupo IBSCBS/imovel (TCRTCInfoImovel, NT 009 item 2.6): cMun, gLocacao (apenas
     * subitem 99.03) e até 99 unidades imobiliárias (gUnidImob), cada uma com CIB ou endereço.
     *
     * @param array<string> $errors
     */
    private function validateIbsCbsImovel(DpsRequest $request, array &$errors): void
    {
        $req = $request->ibscbs;
        if ($req === null) {
            return;
        }

        $s = $request->servico;
        $im = $req->imovel;
        $ehLocacao = str_starts_with($s->codigoTributacao, '9903');
        $exigeUnidade = in_array($req->cIndOp, ['020101', '020201', '020301'], true);

        if ($im === null) {
            if ($ehLocacao) {
                $errors[] = 'E0937: o grupo imovel/gLocacao deve ser informado para o subitem 99.03 do código de tributação nacional';
            }
            if ($exigeUnidade) {
                $errors[] = 'E0938: o grupo imovel/gUnidImob é obrigatório para o cIndOp informado';
            }

            return;
        }

        if (!preg_match('/^[0-9]{7}$/', $im->cMun)) {
            $errors[] = 'cMun do imóvel deve ter 7 dígitos numéricos (TSCodMunIBGE)';
        }

        $this->validateIbsCbsLocacao($request, $ehLocacao, $errors);

        if (count($im->unidades) > IbsCbsImovel::MAX_UNIDADES) {
            $errors[] = 'gUnidImob deve conter no máximo ' . IbsCbsImovel::MAX_UNIDADES . ' unidades (maxOccurs=99)';
        }
        if ($exigeUnidade && $im->unidades === []) {
            $errors[] = 'E0938: o grupo imovel/gUnidImob é obrigatório para o cIndOp informado';
        }

        foreach ($im->unidades as $i => $unidade) {
            $this->validateIbsCbsUnidadeImobiliaria($request, $unidade, "gUnidImob #{$i}", $ehLocacao, $errors);
        }
    }

    /** @param array<string> $errors */
    private function validateIbsCbsLocacao(DpsRequest $request, bool $ehLocacao, array &$errors): void
    {
        $loc = $request->ibscbs?->imovel?->locacao;
        $s = $request->servico;

        if ($loc === null) {
            return;
        }

        if (!$ehLocacao) {
            $errors[] = 'E0936: o grupo gLocacao só pode ser informado para o subitem 99.03 do código de tributação nacional';
        }

        $pValido = false;
        if (!preg_match('/^(0|0\.[0-9]{2}|[1-9][0-9]{0,2}(\.[0-9]{2})?)$/', $loc->pCopropriedade)) {
            $errors[] = 'pCopropriedade deve seguir o padrão decimal de 2 casas (TSDec3V2)';
        } elseif ((float) $loc->pCopropriedade > 100) {
            $errors[] = 'E0940: pCopropriedade deve ser maior ou igual a 0 e menor ou igual a 100';
        } else {
            $pValido = true;
        }

        $totalValido = false;
        if (!self::isDec15V2($loc->vTotOper)) {
            $errors[] = 'vTotOper deve seguir o padrão decimal de 2 casas (TSDec15V2)';
        } elseif ((float) $loc->vTotOper <= 0) {
            $errors[] = 'vTotOper deve ser maior que zero (TSDec15V2)';
        } else {
            $totalValido = true;
        }

        foreach (['vDescIncondTot' => $loc->vDescIncondTot, 'vDescCondTot' => $loc->vDescCondTot] as $campo => $valor) {
            if ($valor !== null && !self::isDec15V2($valor)) {
                $errors[] = "{$campo} deve seguir o padrão decimal de 2 casas (TSDec15V2)";
            }
        }

        if ($loc->dVencOrig !== null) {
            $data = preg_match('/^\d{4}-\d{2}-\d{2}$/', $loc->dVencOrig) === 1
                ? \DateTimeImmutable::createFromFormat('!Y-m-d', $loc->dVencOrig)
                : false;
            if ($data === false || $data->format('Y-m-d') !== $loc->dVencOrig) {
                $errors[] = 'dVencOrig deve ser uma data válida no formato AAAA-MM-DD (TSData)';
            }
        }

        if (!$pValido || !$totalValido) {
            return;
        }

        // vServ, vDescIncond e vDescCond derivam do percentual de copropriedade (NT 009 item 2.6).
        $fator = (float) $loc->pCopropriedade / 100;
        $this->validateValorDerivadoLocacao('vServ', $s->valorServicos, round((float) $loc->vTotOper * $fator, 2), $errors);

        foreach ([
            'vDescIncond' => [$s->descontoIncondicionado, $loc->vDescIncondTot],
            'vDescCond' => [$s->descontoCondicionado, $loc->vDescCondTot],
        ] as $campo => [$informado, $total]) {
            if ($total !== null && self::isDec15V2($total)) {
                $this->validateValorDerivadoLocacao($campo, $informado ?? 0.0, round((float) $total * $fator, 2), $errors);
            }
        }
    }

    /** @param array<string> $errors */
    private function validateValorDerivadoLocacao(string $campo, float $informado, float $esperado, array &$errors): void
    {
        if (abs($informado - $esperado) > 0.01) {
            $errors[] = sprintf(
                '%s deve ser igual a pCopropriedade x total da operação para o subitem 99.03 (esperado %.2f, informado %.2f)',
                $campo,
                $esperado,
                $informado
            );
        }
    }

    /** @param array<string> $errors */
    private function validateIbsCbsUnidadeImobiliaria(
        DpsRequest $request,
        IbsCbsUnidadeImobiliariaRequest $u,
        string $pfx,
        bool $ehLocacao,
        array &$errors
    ): void {
        if ($u->inscImobFisc !== null && (mb_strlen($u->inscImobFisc, 'UTF-8') < 1 || mb_strlen($u->inscImobFisc, 'UTF-8') > 30)) {
            $errors[] = "{$pfx}: inscImobFisc deve ter 1 a 30 caracteres (TSInscImobFisc)";
        }

        if ($u->cCIB !== null && $u->endereco !== null) {
            $errors[] = "{$pfx}: cCIB e end são mutuamente exclusivos (TCRTCUnidadesImobiliarias choice)";
        }
        if ($u->cCIB === null && $u->endereco === null) {
            $errors[] = "{$pfx}: é obrigatório informar cCIB ou end (TCRTCUnidadesImobiliarias choice)";
        }
        if ($u->cCIB !== null && mb_strlen($u->cCIB) !== 8) {
            $errors[] = "{$pfx}: cCIB deve ter exatamente 8 caracteres (TSCodCIB)";
        }

        if ($u->endereco !== null) {
            $e = $u->endereco;
            if (!preg_match('/^[0-9]{8}$/', $e->cep)) {
                $errors[] = "{$pfx}: CEP do endereço do imóvel deve ter 8 dígitos numéricos (TSCEP)";
            }
            if ($e->xLgr === '' || mb_strlen($e->xLgr, 'UTF-8') > 255) {
                $errors[] = "{$pfx}: xLgr deve ter 1 a 255 caracteres (TSLogradouro)";
            }
            if ($e->nro === '' || mb_strlen($e->nro, 'UTF-8') > 60) {
                $errors[] = "{$pfx}: nro deve ter 1 a 60 caracteres (TSNumeroEndereco)";
            }
            if ($e->xCpl !== null && (mb_strlen($e->xCpl, 'UTF-8') < 1 || mb_strlen($e->xCpl, 'UTF-8') > 156)) {
                $errors[] = "{$pfx}: xCpl deve ter 1 a 156 caracteres (TSComplementoEndereco)";
            }
            if ($e->xBairro !== null && (mb_strlen($e->xBairro, 'UTF-8') < 1 || mb_strlen($e->xBairro, 'UTF-8') > 60)) {
                $errors[] = "{$pfx}: xBairro deve ter 1 a 60 caracteres (TSBairro)";
            }
        }

        if ($u->ajustes === []) {
            return;
        }

        if (!$ehLocacao) {
            $errors[] = "{$pfx}: E0939: gAjusteBCLocImoveis só pode ser informado para o subitem 99.03 do código de tributação nacional";
        }
        if (count($u->ajustes) > 1000) {
            $errors[] = "{$pfx}: gAjusteBCLocImoveis deve conter no máximo 1000 ocorrências (maxOccurs=1000)";
        }

        $vTotOper = $request->ibscbs?->imovel?->locacao?->vTotOper;
        foreach ($u->ajustes as $j => $ajuste) {
            $apfx = "{$pfx} gAjusteBCLocImoveis #{$j}";
            $tipo = TipoAjusteBCLocImoveis::tryFrom($ajuste->tpAjusteBCLocImoveis);

            if ($tipo === null) {
                $errors[] = "{$apfx}: tpAjusteBCLocImoveis inválido (TSAjusteBCLocImoveis)";
            } else {
                $temDescricao = $ajuste->xTpAjusteBCLocImoveis !== null && trim($ajuste->xTpAjusteBCLocImoveis) !== '';
                if ($tipo->exigeDescricao() && !$temDescricao) {
                    $errors[] = "{$apfx}: E0949: xTpAjusteBCLocImoveis é obrigatório quando tpAjusteBCLocImoveis = 99";
                }
                if (!$tipo->exigeDescricao() && $ajuste->xTpAjusteBCLocImoveis !== null) {
                    $errors[] = "{$apfx}: E0954: xTpAjusteBCLocImoveis só pode ser informado quando tpAjusteBCLocImoveis = 99";
                }
                if ($tipo === TipoAjusteBCLocImoveis::REDUTOR_SOCIAL && $request->servico->codigoNbs !== '110021000') {
                    $errors[] = "{$apfx}: E0944: o redutor social (04) só se aplica à locação de imóvel residencial (cNBS 1.1002.10.00)";
                }
            }

            if ($ajuste->xTpAjusteBCLocImoveis !== null && mb_strlen($ajuste->xTpAjusteBCLocImoveis, 'UTF-8') > 150) {
                $errors[] = "{$apfx}: xTpAjusteBCLocImoveis deve ter no máximo 150 caracteres (TSDesc150)";
            }

            if (!self::isDec15V2($ajuste->vAjusteBCLocImoveis)) {
                $errors[] = "{$apfx}: vAjusteBCLocImoveis deve seguir o padrão decimal de 2 casas (TSDec15V2)";
            } elseif ($vTotOper !== null && self::isDec15V2($vTotOper)
                && (float) $ajuste->vAjusteBCLocImoveis > (float) $vTotOper) {
                $errors[] = "{$apfx}: E0966: vAjusteBCLocImoveis deve ser menor ou igual a vTotOper";
            }
        }
    }

    /** @param array<string> $errors */
    private function validateIbsCbsBensMoveis(DpsRequest $request, array &$errors): void
    {
        $req = $request->ibscbs;
        $bens = $req?->bensMoveis;
        $ehLocacaoBensMoveis = $request->servico->codigoTributacao === '990401';

        if ($bens === null || $bens === []) {
            if ($ehLocacaoBensMoveis) {
                $errors[] = 'E0963: o grupo bensMoveis deve ser informado para o código de tributação nacional 99.04.01';
            }

            return;
        }

        if (!$ehLocacaoBensMoveis) {
            $errors[] = 'E0967: o grupo bensMoveis só pode ser informado para o código de tributação nacional 99.04.01';
        }

        if (count($bens) > IbsCbsInfo::MAX_BENS_MOVEIS) {
            $errors[] = 'bensMoveis deve conter no máximo ' . IbsCbsInfo::MAX_BENS_MOVEIS . ' ocorrências (maxOccurs=1000)';
        }

        foreach ($bens as $i => $bem) {
            $pfx = "bensMoveis #{$i}";

            if (!preg_match('/^[0-9]{8}$/', $bem->cNCMBemMovel)) {
                $errors[] = "{$pfx}: cNCMBemMovel deve ter 8 dígitos numéricos (TSNCM)";
            }
            if (trim($bem->xNCMBemMovel) === '' || mb_strlen($bem->xNCMBemMovel, 'UTF-8') > 150) {
                $errors[] = "{$pfx}: xNCMBemMovel deve ter 1 a 150 caracteres (TSDesc150)";
            }
            if (!preg_match('/^[0-9]{1,3}$/', $bem->qtdNCMBemMovel)) {
                $errors[] = "{$pfx}: qtdNCMBemMovel deve ter de 1 a 3 dígitos numéricos (TSNum3Dig)";
            } elseif ((int) $bem->qtdNCMBemMovel <= 0) {
                $errors[] = "{$pfx}: E0974: qtdNCMBemMovel deve ser superior a zero";
            }
        }
    }

    /** @param array<string> $errors */
    private function validateIbsCbsRefNFSe(IbsCbsRequest $req, array &$errors): void
    {
        if ($req->tpOper !== null && $req->tpOper !== '') {
            $tpOper = (int) $req->tpOper;
            $hasRef = $req->refNFSeList !== null && $req->refNFSeList !== [];

            if (($tpOper === 2 || $tpOper === 3) && !$hasRef) {
                $errors[] = 'gRefNFSe deve ser informado quando tpOper=2 ou 3';
            }
            if (!($tpOper === 2 || $tpOper === 3) && $hasRef) {
                $errors[] = 'gRefNFSe não pode ser informado para o tpOper informado';
            }
        } elseif ($req->refNFSeList !== null && $req->refNFSeList !== []) {
            $errors[] = 'gRefNFSe não pode ser informado se tpOper não foi informado';
        }

        if ($req->refNFSeList !== null) {
            if (count($req->refNFSeList) > 99) {
                $errors[] = 'gRefNFSe deve conter no máximo 99 refNFSe (maxOccurs=99)';
            }
            foreach ($req->refNFSeList as $i => $chave) {
                if (!preg_match(ChaveAcesso::PADRAO, $chave)) {
                    $errors[] = "E0907: refNFSe #{$i} deve ter 50 posições, com letras A-Z somente na inscrição federal (TSChaveNFSe)";
                }
            }
        }
    }

    /**
     * Valida o grupo fornec (TCRTCListaDocFornec) de um documento de reembolso/
     * repasse/ressarcimento. O grupo é opcional (0-1); quando informado, exige
     * exatamente um identificador (choice CNPJ|CPF|NIF|cNaoNIF) e xNome (1-1, 1-150).
     *
     * @param array<string> $errors
     */
    private function validateIbsCbsFornecedor(
        ?IbsCbsFornecedorRequest $fornec,
        string $pfx,
        array &$errors
    ): void {
        if ($fornec === null) {
            return;
        }

        $idCount = 0;
        if ($fornec->cnpj !== null) {
            $idCount++;
            if (!preg_match(Cnpj::PADRAO, $fornec->cnpj)) {
                $errors[] = "{$pfx}: CNPJ do fornecedor deve ter 14 caracteres: 12 de raiz e ordem (dígitos ou letras A-Z) e 2 dígitos verificadores (TSCNPJ)";
            }
        }
        if ($fornec->cpf !== null) {
            $idCount++;
            if (!preg_match('/^[0-9]{11}$/', $fornec->cpf)) {
                $errors[] = "{$pfx}: CPF do fornecedor deve ter 11 dígitos numéricos (TSCPF)";
            }
        }
        if ($fornec->nif !== null) {
            $idCount++;
            if (mb_strlen($fornec->nif, 'UTF-8') < 1 || mb_strlen($fornec->nif, 'UTF-8') > 40) {
                $errors[] = "{$pfx}: NIF do fornecedor deve ter 1 a 40 caracteres (TSNIF)";
            }
        }
        if ($fornec->codigoNaoNif !== null) {
            $idCount++;
            if (!in_array($fornec->codigoNaoNif, CausaNaoNif::valores(), true)) {
                $errors[] = "{$pfx}: cNaoNIF do fornecedor inválido - deve ser 0, 1 ou 2 (TSCodNaoNIF)";
            }
        }

        if ($idCount === 0) {
            $errors[] = "{$pfx}: fornecedor deve ter CNPJ, CPF, NIF ou cNaoNIF (choice obrigatório)";
        }
        if ($idCount > 1) {
            $errors[] = "{$pfx}: fornecedor - CNPJ, CPF, NIF e cNaoNIF são mutuamente exclusivos";
        }

        if (mb_strlen($fornec->xNome, 'UTF-8') < 1 || mb_strlen($fornec->xNome, 'UTF-8') > 150) {
            $errors[] = "{$pfx}: xNome do fornecedor deve ter 1 a 150 caracteres (TSDesc150)";
        }
    }

    /** @param array<string> $errors */
    private function validateIbsCbsDiferimento(IbsCbsRequest $req, array &$errors): void
    {
        $dif = $req->diferimento;
        if ($dif === null) {
            return;
        }

        if ($dif->pDifUF < 0 || $dif->pDifUF > 100) {
            $errors[] = 'pDifUF deve estar entre 0 e 100 (TSDec3V2)';
        }
        if ($dif->pDifMun < 0 || $dif->pDifMun > 100) {
            $errors[] = 'pDifMun deve estar entre 0 e 100 (TSDec3V2)';
        }
        if ($dif->pDifCBS < 0 || $dif->pDifCBS > 100) {
            $errors[] = 'pDifCBS deve estar entre 0 e 100 (TSDec3V2)';
        }

        if ($dif->pDifUF <= 0 && $dif->pDifMun <= 0 && $dif->pDifCBS <= 0) {
            $errors[] = 'gDif informado mas todos os percentuais (pDifUF/pDifMun/pDifCBS) são zero - omita o grupo de diferimento';
        }
    }

    /** @param array<string> $errors */
    private function validateIbsCbsTribRegular(IbsCbsRequest $req, array &$errors): void
    {
        $tr = $req->tribRegular;
        if ($tr === null) {
            return;
        }

        if (!preg_match('/^[0-9]{3}$/', $tr->cstReg)) {
            $errors[] = 'CSTReg deve ter 3 dígitos numéricos (TSRTCCodSitTrib)';
        }
        if (!preg_match('/^[0-9]{6}$/', $tr->cClassTribReg)) {
            $errors[] = 'cClassTribReg deve ter 6 dígitos numéricos (TSRTCCodClassTrib)';
        }
    }

    /** @param array<string> $errors */
    private function validateIbsCbsCstClassTrib(IbsCbsRequest $req, array &$errors): void
    {
        if ($this->cstClassTribRepository === null || $req->cClassTrib === '') {
            return;
        }

        $props = $this->cstClassTribRepository->findByCode($req->cClassTrib);

        if ($props === null) {
            $errors[] = "cClassTrib '{$req->cClassTrib}' não encontrado na tabela oficial";
            return;
        }

        if (!$props->isValidoParaNfse()) {
            $errors[] = "cClassTrib '{$req->cClassTrib}' não é suportado para NFS-e";
        }

        if ($props->isPermiteDiferimento() && $req->diferimento === null) {
            $errors[] = 'gDif deve ser informado para o cClassTrib indicado';
        }
        if (!$props->isPermiteDiferimento() && $req->diferimento !== null) {
            $errors[] = 'gDif não deve ser informado para o cClassTrib indicado';
        }

        if ($props->isExigeGrupoTributacaoRegular() && $req->tribRegular === null) {
            $errors[] = 'gTribRegular deve ser informado para o cClassTrib indicado';
        }
        if (!$props->isExigeGrupoTributacaoRegular() && $req->tribRegular !== null) {
            $errors[] = 'gTribRegular não deve ser informado para o cClassTrib indicado';
        }

        if ($req->tribRegular !== null && $req->tribRegular->cClassTribReg !== '') {
            $propsReg = $this->cstClassTribRepository->findByCode($req->tribRegular->cClassTribReg);
            if ($propsReg === null) {
                $errors[] = "cClassTribReg '{$req->tribRegular->cClassTribReg}' não encontrado na tabela oficial";
            } elseif (!$propsReg->isValidoParaNfse()) {
                $errors[] = "cClassTribReg '{$req->tribRegular->cClassTribReg}' não é suportado para NFS-e";
            }
        }
    }

    /** @param array<string> $errors */
    private function validateSubstituicao(DpsRequest $request, array &$errors): void
    {
        $s = $request->substituicao;
        if ($s === null) {
            return;
        }

        if (!preg_match(ChaveAcesso::PADRAO, $s->chaveSubstituida)) {
            $errors[] = 'chSubstda deve ter 50 dígitos numéricos (TSChaveNFSe)';
        }

        if (!in_array($s->codigoMotivo, MotivoSubstituicao::valores(), true)) {
            $errors[] = 'cMotivo inválido (TSCodJustSubst)';
        }

        if ($s->codigoMotivo === '99') {
            if ($s->descricaoMotivo === null || trim($s->descricaoMotivo) === '') {
                $errors[] = 'xMotivo é obrigatório quando cMotivo=99 (TSMotivo)';
            }
        }

        if ($s->descricaoMotivo !== null) {
            $len = mb_strlen(trim($s->descricaoMotivo));
            if ($len > 0 && $len < 15) {
                $errors[] = 'xMotivo deve ter no mínimo 15 caracteres (TSMotivo)';
            }
            if ($len > 255) {
                $errors[] = 'xMotivo deve ter no máximo 255 caracteres (TSMotivo)';
            }
        }
    }
}
