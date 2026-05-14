<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\Service;

use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\DpsRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Response\NfseResponse;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ServiceException;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ValidationException;
use MarcelaBeh\EmissorNfseNacional\Application\Validator\DpsValidator;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Dps;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Endereco;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsDest;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsDiferimento;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsInfo;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsTribRegular;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Prestador;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Servico;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Substituicao;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Tomador;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\FinalidadeNfse;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\IndicadorDestinacao;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\IndicadorFinal;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoAmbiente;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEmissao;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEnteGovernamental;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoOperacao;
use MarcelaBeh\EmissorNfseNacional\Domain\Exception\DomainException;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cep;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\ChaveAcesso;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cnpj;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoClassificacaoTributaria;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoCreditoPresumido;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoIndicadorOperacao;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoMunicipio;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoSituacaoTributaria;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cpf;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Money;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\ApiConnector;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\Exception\HttpException;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\RequestBuilder;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Security\XmlSigner;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Builder\DpsXmlBuilder;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Validator\XsdValidator;

class EmitirDpsService
{
    public function __construct(
        private ApiConnector $apiConnector,
        private DpsXmlBuilder $xmlBuilder,
        private XmlSigner $xmlSigner,
        private XsdValidator $xsdValidator,
        private DpsValidator $validator,
        private RequestBuilder $requestBuilder,
    ) {
    }

    public function executar(DpsRequest $request): NfseResponse
    {
        try {
            $this->validator->validate($request);

            $dps = $this->criarDpsFromRequest($request);
            $dps->gerarChaveAcesso();

            $xml = $this->xmlBuilder->build($dps);

            $this->xsdValidator->validate($xml, 'DPS');

            $xmlAssinado = $this->xmlSigner->sign($xml, 'infDPS', 'DPS');
            $xmlAssinado = '<?xml version="1.0" encoding="UTF-8"?>' . $xmlAssinado;

            $payload = $this->requestBuilder->buildDpsPayload($xmlAssinado);

            $response = $this->apiConnector->post('nfse', $payload);

            return $this->processarResposta($response, $dps);

        } catch (DomainException $e) {
            throw new ValidationException(
                "Dados inválidos: {$e->getMessage()}",
                0,
                $e
            );
        } catch (HttpException $e) {
            throw new ServiceException(
                "Falha ao comunicar com API: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    private function criarDpsFromRequest(DpsRequest $request): Dps
    {
        $documentoPrestador = $request->prestador->isCnpj
            ? new Cnpj($request->prestador->documento)
            : new Cpf($request->prestador->documento);

        $prestador = new Prestador(
            documento: $documentoPrestador,
            inscricaoMunicipal: $request->prestador->inscricaoMunicipal,
            razaoSocial: $request->prestador->razaoSocial,
            nomeFantasia: $request->prestador->nomeFantasia,
            telefone: $request->prestador->telefone ? new \MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Telefone($request->prestador->telefone) : null,
            email: $request->prestador->email ? new \MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Email($request->prestador->email) : null,
            endereco: $this->criarEndereco(
                $request->prestador->logradouro,
                $request->prestador->numero,
                $request->prestador->complemento,
                $request->prestador->bairro,
                $request->prestador->codigoMunicipio,
                $request->prestador->uf,
                $request->prestador->cep
            ),
            regimeTributario: \MarcelaBeh\EmissorNfseNacional\Domain\Enum\RegimeTributario::from($request->prestador->regimeTributario),
            nif: $request->prestador->nif,
            caepf: $request->prestador->caepf,
        );

        $documentoTomador = null;
        if ($request->tomador->documento) {
            $documentoTomador = $request->tomador->isCnpj
                ? new Cnpj($request->tomador->documento)
                : new Cpf($request->tomador->documento);
        }

        $tomador = new Tomador(
            documento: $documentoTomador,
            razaoSocial: $request->tomador->razaoSocial,
            nomeFantasia: $request->tomador->nomeFantasia,
            telefone: $request->tomador->telefone ? new \MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Telefone($request->tomador->telefone) : null,
            email: $request->tomador->email ? new \MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Email($request->tomador->email) : null,
            endereco: $this->criarEndereco(
                $request->tomador->logradouro,
                $request->tomador->numero,
                $request->tomador->complemento,
                $request->tomador->bairro,
                $request->tomador->codigoMunicipio,
                $request->tomador->uf,
                $request->tomador->cep
            ),
            nif: $request->tomador->nif,
            inscricaoMunicipal: $request->tomador->inscricaoMunicipal,
        );

        $servico = new Servico(
            discriminacao: $request->servico->discriminacao,
            codigoTributacao: $request->servico->codigoTributacao,
            localPrestacao: new CodigoMunicipio($request->servico->codigoMunicipioPrestacao),
            valorServicos: new Money($request->servico->valorServicos),
            valorDeducoes: new Money($request->servico->valorDeducoes),
            descontoIncondicionado: new Money($request->servico->descontoIncondicionado),
            descontoCondicionado: new Money($request->servico->descontoCondicionado),
            aliquotaIss: $request->servico->aliquotaIss,
            codigoNbs: $request->servico->codigoNbs,
            codigoCnae: $request->servico->codigoCnae,
        );

        $substituicao = null;
        if ($request->substituicao !== null) {
            $substituicao = new Substituicao(
                chaveSubstituida: new ChaveAcesso($request->substituicao->chaveSubstituida),
                codigoMotivo: $request->substituicao->codigoMotivo,
                descricaoMotivo: $request->substituicao->descricaoMotivo,
            );
        }

        $ibscbs = null;
        if ($request->ibscbs !== null) {
            $req = $request->ibscbs;

            $dest = null;
            if ($req->dest !== null) {
                $d = $req->dest;
                $docDest = null;
                if ($d->cnpj) {
                    $docDest = new Cnpj($d->cnpj);
                } elseif ($d->cpf) {
                    $docDest = new Cpf($d->cpf);
                }

                $endDest = null;
                if ($d->logradouro && $d->codigoMunicipio) {
                    $endDest = new Endereco(
                        logradouro: $d->logradouro,
                        numero: $d->numero ?? '',
                        complemento: $d->complemento,
                        bairro: $d->bairro ?? '',
                        codigoMunicipio: new CodigoMunicipio($d->codigoMunicipio),
                        uf: $d->uf ?? '',
                        cep: $d->cep ? new Cep($d->cep) : new Cep('00000000'),
                    );
                }

                $dest = new IbsCbsDest(
                    cnpj: $docDest instanceof Cnpj ? $docDest : null,
                    cpf: $docDest instanceof Cpf ? $docDest : null,
                    nif: $d->nif ? new \MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Nif($d->nif) : null,
                    codigoNaoNif: $d->codigoNaoNif,
                    xNome: $d->xNome,
                    endereco: $endDest,
                    fone: $d->fone,
                    email: $d->email,
                );
            }

            $tribRegular = null;
            if ($req->tribRegular !== null) {
                $tribRegular = new IbsCbsTribRegular(
                    cstReg: new CodigoSituacaoTributaria($req->tribRegular->cstReg),
                    cClassTribReg: new CodigoClassificacaoTributaria($req->tribRegular->cClassTribReg),
                );
            }

            $diferimento = null;
            if ($req->diferimento !== null) {
                $diferimento = new IbsCbsDiferimento(
                    pDifUF: $req->diferimento->pDifUF,
                    pDifMun: $req->diferimento->pDifMun,
                    pDifCBS: $req->diferimento->pDifCBS,
                );
            }

            $ibscbs = new IbsCbsInfo(
                finNFSe: FinalidadeNfse::from($req->finNFSe),
                cIndOp: new CodigoIndicadorOperacao($req->cIndOp),
                indDest: IndicadorDestinacao::from($req->indDest),
                cst: new CodigoSituacaoTributaria($req->cst),
                cClassTrib: new CodigoClassificacaoTributaria($req->cClassTrib),
                indFinal: $req->indFinal !== null ? IndicadorFinal::from($req->indFinal) : null,
                tpOper: $req->tpOper !== null ? TipoOperacao::from($req->tpOper) : null,
                tpEnteGov: $req->tpEnteGov !== null ? TipoEnteGovernamental::from($req->tpEnteGov) : null,
                cCredPres: $req->cCredPres !== null ? new CodigoCreditoPresumido($req->cCredPres) : null,
                dest: $dest,
                tribRegular: $tribRegular,
                diferimento: $diferimento,
            );
        }

        return new Dps(
            tipoAmbiente: TipoAmbiente::from($request->tipoAmbiente),
            dataEmissao: new \DateTimeImmutable($request->dataEmissao),
            versaoAplicacao: $request->versaoAplicacao,
            serie: $request->serie,
            numero: $request->numero,
            dataCompetencia: new \DateTimeImmutable($request->dataCompetencia),
            tipoEmissao: TipoEmissao::from($request->tipoEmissao),
            codigoMunicipioEmissor: new CodigoMunicipio($request->codigoMunicipioEmissor),
            prestador: $prestador,
            tomador: $tomador,
            servico: $servico,
            substituicao: $substituicao,
            ibscbs: $ibscbs,
        );
    }

    private function criarEndereco(
        string $logradouro,
        string $numero,
        ?string $complemento,
        string $bairro,
        string $codigoMunicipio,
        string $uf,
        string $cep
    ): Endereco {
        return new Endereco(
            logradouro: $logradouro,
            numero: $numero,
            complemento: $complemento,
            bairro: $bairro,
            codigoMunicipio: new CodigoMunicipio($codigoMunicipio),
            uf: $uf,
            cep: new Cep($cep)
        );
    }

    private function processarResposta(array $response, Dps $dps): NfseResponse
    {
        if (!$response['success']) {
            return new NfseResponse(
                success: false,
                mensagem: $response['data']['mensagem'] ?? 'Erro ao emitir DPS',
                dados: $response['data'] ?? null,
            );
        }

        $data = $response['data'];

        return new NfseResponse(
            success: true,
            chaveAcesso: $dps->getChaveAcesso()?->getChave(),
            dados: $data,
        );
    }
}
