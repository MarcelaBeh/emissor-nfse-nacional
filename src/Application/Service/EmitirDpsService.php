<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\Service;

use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\DpsRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Response\NfseResponse;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ServiceException;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ValidationException;
use MarcelaBeh\EmissorNfseNacional\Application\Validator\DpsValidator;
use MarcelaBeh\EmissorNfseNacional\Application\Validator\IbscbsResponseValidator;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Dps;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Endereco;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsDest;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsDiferimento;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsDocumentoReeRepRes;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsEnderecoExterior;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsEnderecoObra;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsFornecedor;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsImovel;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsInfo;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsReeRepRes;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsTribRegular;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Intermediario;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Obra;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Prestador;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Servico;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Substituicao;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Tomador;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\FinalidadeNfse;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\IndicadorDestinacao;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\IndicadorFinal;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\MotivoEmissaoTI;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoAmbiente;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEmitente;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEnteGovernamental;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoOperacao;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoRetencaoIssqn;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TributacaoIssqn;
use MarcelaBeh\EmissorNfseNacional\Domain\Exception\DomainException;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cep;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\ChaveAcesso;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cnpj;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoCIB;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoClassificacaoTributaria;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoCreditoPresumido;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoIndicadorOperacao;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoMunicipio;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoSituacaoTributaria;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cpf;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Money;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Config\ApiEndpoints;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\Contract\ApiConnectorInterface;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\Exception\HttpException;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\RequestBuilder;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Security\Contract\LoggerInterface;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Security\Contract\XmlSignerInterface;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Security\NullLogger;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Builder\Contract\XmlBuilderInterface;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Parser\NfseXmlParser;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Validator\Contract\XsdValidatorInterface;

class EmitirDpsService
{
    public function __construct(
        private ApiConnectorInterface $apiConnector,
        private XmlBuilderInterface $xmlBuilder,
        private XmlSignerInterface $xmlSigner,
        private XsdValidatorInterface $xsdValidator,
        private DpsValidator $validator,
        private RequestBuilder $requestBuilder,
        private NfseXmlParser $nfseXmlParser,
        private IbscbsResponseValidator $ibscbsResponseValidator,
        private ApiEndpoints $apiEndpoints,
        private LoggerInterface $logger = new NullLogger(),
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
            $this->logger->warning('Validação DPS falhou: {msg}', $e->getMessage());
            throw new ValidationException(
                "Dados inválidos: {$e->getMessage()}",
                0,
                $e
            );
        } catch (HttpException $e) {
            $this->logger->error('Falha HTTP ao emitir DPS: {msg}', $e->getMessage());
            throw new ServiceException(
                "Falha ao comunicar com API: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    public function executarPorDecisaoJudicial(string $nfseXml): NfseResponse
    {
        try {
            $this->xsdValidator->validate($nfseXml, 'NFSe');

            $xmlAssinado = $this->xmlSigner->sign($nfseXml, 'infNFSe', 'NFSe');
            $xmlAssinado = '<?xml version="1.0" encoding="UTF-8"?>' . $xmlAssinado;

            $gzipBase64 = $this->compactarXml($xmlAssinado);

            $payload = ['xmlGZipB64' => $gzipBase64];

            $endpoint = $this->apiEndpoints->decisaoJudicialNfse();
            $response = $this->apiConnector->post($endpoint, $payload);

            return $this->processarRespostaDecisaoJudicial($response);

        } catch (DomainException $e) {
            $this->logger->warning('Validação decisão judicial falhou: {msg}', $e->getMessage());
            throw new ValidationException(
                "Dados inválidos: {$e->getMessage()}",
                0,
                $e
            );
        } catch (HttpException $e) {
            $this->logger->error('Falha HTTP ao emitir por decisão judicial: {msg}', $e->getMessage());
            throw new ServiceException(
                "Falha ao comunicar com API: {$e->getMessage()}",
                0,
                $e
            );
        } catch (\RuntimeException $e) {
            $this->logger->error('Falha ao processar XML para decisão judicial: {msg}', $e->getMessage());
            throw new ServiceException(
                "Falha ao processar XML: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    private function compactarXml(string $xml): string
    {
        $compressed = gzencode($xml);
        if ($compressed === false) {
            throw new ServiceException('Falha ao compactar XML');
        }

        return base64_encode($compressed);
    }

    /**
     * @param array<string, mixed> $response
     */
    private function processarRespostaDecisaoJudicial(array $response): NfseResponse
    {
        if (!$response['success']) {
            $data = $response['data'] ?? [];
            $erros = $data['erros'] ?? $data['erro'] ?? [['descricao' => 'Erro desconhecido']];

            return new NfseResponse(
                success: false,
                mensagem: $erros[0]['descricao'] ?? 'Falha na emissão por decisão judicial',
            );
        }

        $data = $response['data'] ?? [];
        $nfseXmlGzip = $data['nfseXmlGZipB64'] ?? null;

        if ($nfseXmlGzip) {
            $xml = $this->descompactarXml($nfseXmlGzip);
            $parsed = $this->nfseXmlParser->parse($xml);

            return new NfseResponse(
                success: true,
                dados: $parsed[0] ?? null,
                xml: $xml,
            );
        }

        return new NfseResponse(
            success: true,
            dados: $data,
        );
    }

    private function descompactarXml(string $gzipBase64): string
    {
        $decoded = base64_decode($gzipBase64, true);
        if ($decoded === false) {
            throw new ServiceException('Falha ao decodificar base64');
        }

        $uncompressed = gzdecode($decoded);
        if ($uncompressed === false) {
            throw new ServiceException('Falha ao descompactar gzip');
        }

        return $uncompressed;
    }

    private function criarDpsFromRequest(DpsRequest $request): Dps
    {
        $documentoPrestador = null;
        if ($request->prestador->documento !== null && $request->prestador->isCnpj !== null) {
            $documentoPrestador = $request->prestador->isCnpj
                ? new Cnpj($request->prestador->documento)
                : new Cpf($request->prestador->documento);
        }
        $enderecoPrestador = null;
        if ($request->prestador->logradouro !== null && $request->prestador->cep !== null) {
            $enderecoPrestador = $this->criarEndereco(
                $request->prestador->logradouro,
                $request->prestador->numero ?? '',
                $request->prestador->complemento,
                $request->prestador->bairro ?? '',
                $request->prestador->codigoMunicipio,
                $request->prestador->uf ?? '',
                $request->prestador->cep
            );
        }

        $prestador = new Prestador(
            documento: $documentoPrestador,
            inscricaoMunicipal: $request->prestador->inscricaoMunicipal,
            razaoSocial: $request->prestador->razaoSocial,
            telefone: $request->prestador->telefone ? new \MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Telefone($request->prestador->telefone) : null,
            email: $request->prestador->email ? new \MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Email($request->prestador->email) : null,
            endereco: $enderecoPrestador,
            regimeTributario: \MarcelaBeh\EmissorNfseNacional\Domain\Enum\RegimeTributario::from($request->prestador->regimeTributario),
            regimeEspecialTributacao: $request->prestador->regEspTrib !== null
                ? \MarcelaBeh\EmissorNfseNacional\Domain\Enum\RegimeEspecialTributacao::from((string) $request->prestador->regEspTrib)
                : \MarcelaBeh\EmissorNfseNacional\Domain\Enum\RegimeEspecialTributacao::NENHUM,
            nif: $request->prestador->nif,
            caepf: $request->prestador->caepf,
            codigoNaoNif: $request->prestador->codigoNaoNif,
            regimeApuracaoSimplesNacional: $request->prestador->regApTribSN,
        );

        $tomador = null;
        if ($request->tomador !== null) {
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
                    $request->tomador->cep,
                    $request->tomador->codigoPais,
                    $request->tomador->codigoPostalExterior,
                    $request->tomador->nomeCidadeExterior,
                    $request->tomador->estadoProvinciaExterior,
                ),
                nif: $request->tomador->nif,
                inscricaoMunicipal: $request->tomador->inscricaoMunicipal,
                codigoNaoNif: $request->tomador->codigoNaoNif,
                caepf: $request->tomador->caepf,
            );
        }

        $intermediario = null;
        if ($request->intermediario !== null) {
            $i = $request->intermediario;
            $documentoIntermediario = null;
            if ($i->documento) {
                $documentoIntermediario = $i->isCnpj
                    ? new Cnpj($i->documento)
                    : new Cpf($i->documento);
            }

            $intermediario = new Intermediario(
                documento: $documentoIntermediario,
                razaoSocial: $i->razaoSocial,
                inscricaoMunicipal: $i->inscricaoMunicipal,
                telefone: $i->telefone ? new \MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Telefone($i->telefone) : null,
                email: $i->email ? new \MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Email($i->email) : null,
                endereco: $this->criarEndereco(
                    $i->logradouro,
                    $i->numero,
                    $i->complemento,
                    $i->bairro,
                    $i->codigoMunicipio,
                    $i->uf,
                    $i->cep,
                    $i->codigoPais,
                    $i->codigoPostalExterior,
                    $i->nomeCidadeExterior,
                    $i->estadoProvinciaExterior,
                ),
                nif: $i->nif,
                codigoNaoNif: $i->codigoNaoNif,
                caepf: $i->caepf,
            );
        }

        $obra = null;
        if ($request->servico->obra !== null) {
            $o = $request->servico->obra;
            $endObra = null;
            if ($o->endereco !== null) {
                $e = $o->endereco;
                $endExt = null;
                if ($e->endExt !== null) {
                    $endExt = new \MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsEnderecoExterior(
                        cEndPost: $e->endExt->cEndPost,
                        xCidade: $e->endExt->xCidade,
                        xEstProvReg: $e->endExt->xEstProvReg,
                    );
                }
                $endObra = new \MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsEnderecoObra(
                    cep: $e->cep,
                    endExt: $endExt,
                    xLgr: $e->xLgr,
                    nro: $e->nro,
                    xCpl: $e->xCpl,
                    xBairro: $e->xBairro,
                );
            }
            $obra = new Obra(
                inscImobFisc: $o->inscImobFisc,
                cObra: $o->cObra,
                cCIB: $o->cCIB !== null ? new CodigoCIB($o->cCIB) : null,
                endereco: $endObra,
            );
        }

        $servico = new Servico(
            discriminacao: $request->servico->discriminacao,
            codigoTributacao: $request->servico->codigoTributacao,
            valorServicos: new Money($request->servico->valorServicos),
            valorDeducoes: $request->servico->valorDeducoes !== null ? new Money($request->servico->valorDeducoes) : null,
            descontoIncondicionado: $request->servico->descontoIncondicionado !== null ? new Money($request->servico->descontoIncondicionado) : null,
            descontoCondicionado: $request->servico->descontoCondicionado !== null ? new Money($request->servico->descontoCondicionado) : null,
            aliquotaIss: $request->servico->aliquotaIss,
            localPrestacao: $request->servico->codigoMunicipioPrestacao !== null ? new CodigoMunicipio($request->servico->codigoMunicipioPrestacao) : null,
            codigoNbs: $request->servico->codigoNbs,
            codigoCnae: $request->servico->codigoCnae,
            obra: $obra,
            tribISSQN: $request->servico->tribISSQN !== null ? TributacaoIssqn::from($request->servico->tribISSQN) : TributacaoIssqn::OPERACAO_TRIBUTAVEL,
            tpRetISSQN: $request->servico->tpRetISSQN !== null ? TipoRetencaoIssqn::from($request->servico->tpRetISSQN) : TipoRetencaoIssqn::NAO_RETIDO,
            codigoPaisPrestacao: $request->servico->codigoPaisPrestacao,
            codigoPaisResultado: $request->servico->codigoPaisResultado,
            codigoTributacaoMunicipal: $request->servico->codigoTributacaoMunicipal,
            codigoInternoContribuinte: $request->servico->codigoInternoContribuinte,
            valorRecebido: $request->servico->valorRecebido,
            comExterior: $this->criarComExterior($request->servico->comExterior),
            atvEvento: $this->criarAtvEvento($request->servico->atvEvento),
            infoCompl: $this->criarInfoCompl($request->servico->infoCompl),
            documentosDeducao: $this->criarDocumentosDeducao($request->servico->documentosDeducao),
            percentualDeducao: $request->servico->percentualDeducao,
            valorDeducaoPadrao: $request->servico->valorDeducaoPadrao,
            tipoImunidade: $request->servico->tipoImunidade,
            exigSusp: $this->criarExigSusp($request->servico->exigSusp),
            beneficioMunicipal: $this->criarBeneficioMunicipal($request->servico->beneficioMunicipal),
            tribFederal: $this->criarTribFederal($request->servico->tribFederal),
            totTribTipo: $request->servico->totTribTipo,
            pTotTribFed: $request->servico->pTotTribFed,
            pTotTribEst: $request->servico->pTotTribEst,
            pTotTribMun: $request->servico->pTotTribMun,
            indTotTrib: $request->servico->indTotTrib,
            pTotTribSN: $request->servico->pTotTribSN,
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
                $destEhExterior = $d->codigoPais !== null;
                if ($destEhExterior && $d->logradouro !== null && $d->numero !== null && $d->bairro !== null) {
                    $endDest = $this->criarEndereco(
                        $d->logradouro,
                        $d->numero,
                        $d->complemento,
                        $d->bairro,
                        $d->codigoMunicipio ?? '0000000',
                        $d->uf ?? '',
                        $d->cep ?? '00000000',
                        $d->codigoPais,
                        $d->codigoPostalExterior,
                        $d->nomeCidadeExterior,
                        $d->estadoProvinciaExterior,
                    );
                } elseif (
                    $d->logradouro !== null && $d->numero !== null && $d->bairro !== null
                    && $d->codigoMunicipio !== null && $d->cep !== null
                ) {
                    $endDest = new Endereco(
                        logradouro: $d->logradouro,
                        numero: $d->numero,
                        complemento: $d->complemento,
                        bairro: $d->bairro,
                        codigoMunicipio: new CodigoMunicipio($d->codigoMunicipio),
                        uf: $d->uf ?? '',
                        cep: new Cep($d->cep),
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

            $refNFSeList = null;
            if ($req->refNFSeList !== null) {
                $refNFSeList = array_map(
                    fn (string $chave) => new ChaveAcesso($chave),
                    $req->refNFSeList,
                );
            }

            $imovel = null;
            if ($req->imovel !== null) {
                $im = $req->imovel;
                $endObra = null;
                if ($im->endereco !== null) {
                    $e = $im->endereco;
                    $endExt = null;
                    if ($e->endExt !== null) {
                        $endExt = new IbsCbsEnderecoExterior(
                            cEndPost: $e->endExt->cEndPost,
                            xCidade: $e->endExt->xCidade,
                            xEstProvReg: $e->endExt->xEstProvReg,
                        );
                    }
                    $endObra = new IbsCbsEnderecoObra(
                        cep: $e->cep,
                        endExt: $endExt,
                        xLgr: $e->xLgr,
                        nro: $e->nro,
                        xCpl: $e->xCpl,
                        xBairro: $e->xBairro,
                    );
                }
                $imovel = new IbsCbsImovel(
                    inscImobFisc: $im->inscImobFisc,
                    cCIB: $im->cCIB !== null ? new CodigoCIB($im->cCIB) : null,
                    endereco: $endObra,
                );
            }

            $reeRepRes = null;
            if ($req->reeRepRes !== null) {
                $docs = array_map(
                    fn ($dReq) => $this->criarDocumentoReeRepRes($dReq),
                    $req->reeRepRes->documentos,
                );
                $reeRepRes = new IbsCbsReeRepRes($docs);
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
                refNFSeList: $refNFSeList,
                imovel: $imovel,
                reeRepRes: $reeRepRes,
            );
        }

        return new Dps(
            tipoAmbiente: TipoAmbiente::from($request->tipoAmbiente),
            dataEmissao: new \DateTimeImmutable($request->dataEmissao),
            versaoAplicacao: $request->versaoAplicacao,
            serie: $request->serie,
            numero: $request->numero,
            dataCompetencia: new \DateTimeImmutable($request->dataCompetencia),
            tipoEmissao: TipoEmitente::from($request->tipoEmissao),
            codigoMunicipioEmissor: new CodigoMunicipio($request->codigoMunicipioEmissor),
            prestador: $prestador,
            tomador: $tomador,
            servico: $servico,
            intermediario: $intermediario,
            substituicao: $substituicao,
            ibscbs: $ibscbs,
            cMotivoEmisTI: $request->cMotivoEmisTI !== null ? MotivoEmissaoTI::from($request->cMotivoEmisTI) : null,
            chNFSeRej: $request->chNFSeRej !== null ? new ChaveAcesso($request->chNFSeRej) : null,
        );
    }

    private function criarDocumentoReeRepRes(\MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsDocumentoReeRepResRequest $dReq): IbsCbsDocumentoReeRepRes
    {
        $fornec = null;
        if ($dReq->fornec !== null) {
            $f = $dReq->fornec;
            $fornec = new IbsCbsFornecedor(
                cnpj: $f->cnpj !== null ? new Cnpj($f->cnpj) : null,
                cpf: $f->cpf !== null ? new Cpf($f->cpf) : null,
                nif: $f->nif !== null ? new \MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Nif($f->nif) : null,
                codigoNaoNif: $f->codigoNaoNif,
                xNome: $f->xNome,
            );
        }

        return new IbsCbsDocumentoReeRepRes(
            tipo: $dReq->tipoDocumento,
            dtEmiDoc: new \DateTimeImmutable($dReq->dtEmiDoc),
            dtCompDoc: new \DateTimeImmutable($dReq->dtCompDoc),
            tpReeRepRes: \MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoReembolsoRepasseRessarcimento::from($dReq->tpReeRepRes),
            vlrReeRepRes: (string) $dReq->vlrReeRepRes,
            fornec: $fornec,
            xTpReeRepRes: $dReq->xTpReeRepRes,
            tipoChaveDFe: $dReq->tipoChaveDFe,
            xTipoChaveDFe: $dReq->xTipoChaveDFe,
            chaveDFe: $dReq->chaveDFe,
            cMunDocFiscal: $dReq->cMunDocFiscal,
            nDocFiscal: $dReq->nDocFiscal,
            xDocFiscal: $dReq->xDocFiscal,
            nDoc: $dReq->nDoc,
            xDoc: $dReq->xDoc,
        );
    }

    private function criarComExterior(?\MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\ComExteriorRequest $req): ?\MarcelaBeh\EmissorNfseNacional\Domain\Entity\ComExterior
    {
        if ($req === null) {
            return null;
        }

        return new \MarcelaBeh\EmissorNfseNacional\Domain\Entity\ComExterior(
            modoPrestacao: $req->modoPrestacao ?? 0,
            vinculoPrestador: $req->vinculoPrestador ?? 0,
            codigoMoeda: $req->codigoMoeda ?? 'BRL',
            valorServicoMoeda: $req->valorServicoMoeda ?? 0.0,
            mecanismoApoioPrestador: $req->mecanismoApoioPrestador ?? '00',
            mecanismoApoioTomador: $req->mecanismoApoioTomador ?? '00',
            movimentacaoTemporaria: $req->movimentacaoTemporaria ?? '0',
            enviarMDIC: $req->enviarMDIC ?? '0',
            numeroDeclaracaoImportacao: $req->numeroDeclaracaoImportacao,
            numeroRegistroExportacao: $req->numeroRegistroExportacao,
        );
    }

    private function criarAtvEvento(?\MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\AtvEventoRequest $req): ?\MarcelaBeh\EmissorNfseNacional\Domain\Entity\AtvEvento
    {
        if ($req === null) {
            return null;
        }

        $endereco = null;
        if ($req->endereco !== null) {
            $endExt = null;
            if ($req->endereco->codigoPais !== null) {
                $endExt = new \MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsEnderecoExterior(
                    cEndPost: $req->endereco->codigoPostalExterior ?? '',
                    xCidade: $req->endereco->nomeCidadeExterior ?? '',
                    xEstProvReg: $req->endereco->estadoProvinciaExterior ?? '',
                );
            }
            $endereco = new \MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsEnderecoObra(
                cep: $req->endereco->codigoPais === null ? ($req->endereco->cep ?? null) : null,
                endExt: $endExt,
                xLgr: $req->endereco->logradouro ?? '',
                nro: $req->endereco->numero ?? '',
                xBairro: $req->endereco->bairro ?? '',
                xCpl: $req->endereco->complemento,
            );
        }

        if ($req->identificacaoEvento === null && $endereco === null) {
            throw new \InvalidArgumentException('Atividade/Evento deve informar identificacaoEvento ou endereco');
        }

        return new \MarcelaBeh\EmissorNfseNacional\Domain\Entity\AtvEvento(
            descricao: $req->descricao ?? '',
            dataInicio: new \DateTimeImmutable($req->dataInicio ?? 'now'),
            dataFim: new \DateTimeImmutable($req->dataFim ?? 'now'),
            identificacaoEvento: $req->identificacaoEvento,
            endereco: $endereco,
        );
    }

    private function criarInfoCompl(?\MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\InfoComplRequest $req): ?\MarcelaBeh\EmissorNfseNacional\Domain\Entity\InfoCompl
    {
        if ($req === null) {
            return null;
        }

        return new \MarcelaBeh\EmissorNfseNacional\Domain\Entity\InfoCompl(
            idDocTecnico: $req->idDocTecnico,
            docReferencia: $req->docReferencia,
            numeroPedido: $req->numeroPedido,
            itensPedido: $req->itensPedido,
            infoComplementar: $req->infoComplementar,
        );
    }

    /**
     * @param \MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\DocDedRedRequest[]|null $reqs
     * @return array<int, \MarcelaBeh\EmissorNfseNacional\Domain\Entity\DocDedRed>|null
     */
    private function criarDocumentosDeducao(?array $reqs): ?array
    {
        if ($reqs === null) {
            return null;
        }

        return array_map(
            fn ($d) => new \MarcelaBeh\EmissorNfseNacional\Domain\Entity\DocDedRed(
                tipoDocumento: $d->tipoDocumento ?? 'nDoc',
                chaveNFSe: $d->chaveNFSe,
                chaveNFe: $d->chaveNFe,
                codigoMunicipioNFSe: $d->codigoMunicipioNFSe,
                numeroNFSe: $d->numeroNFSe,
                codigoVerificacaoNFSe: $d->codigoVerificacaoNFSe,
                numeroNFS: $d->numeroNFS,
                modeloNFS: $d->modeloNFS,
                serieNFS: $d->serieNFS,
                numeroDocFiscal: $d->numeroDocFiscal,
                numeroDoc: $d->numeroDoc,
                tipoDeducaoReducao: $d->tipoDeducaoReducao ?? '99',
                descricaoOutrasDeducoes: $d->descricaoOutrasDeducoes,
                dataEmissaoDoc: new \DateTimeImmutable($d->dataEmissaoDoc ?? 'now'),
                valorDedutivel: $d->valorDedutivel ?? '0.00',
                valorDeducao: $d->valorDeducao ?? '0.00',
                fornecedor: $d->fornecedor !== null
                    ? new \MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsFornecedor(
                        cnpj: $d->fornecedor->cnpj !== null ? new Cnpj($d->fornecedor->cnpj) : null,
                        cpf: $d->fornecedor->cpf !== null ? new Cpf($d->fornecedor->cpf) : null,
                        nif: $d->fornecedor->nif !== null ? new \MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Nif($d->fornecedor->nif) : null,
                        codigoNaoNif: $d->fornecedor->codigoNaoNif,
                        xNome: $d->fornecedor->xNome,
                    )
                    : null,
            ),
            $reqs,
        );
    }

    private function criarExigSusp(?\MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\ExigSuspRequest $req): ?\MarcelaBeh\EmissorNfseNacional\Domain\Entity\ExigSusp
    {
        if ($req === null) {
            return null;
        }

        return new \MarcelaBeh\EmissorNfseNacional\Domain\Entity\ExigSusp(
            tipoSuspensao: $req->tipoSuspensao,
            numeroProcesso: $req->numeroProcesso,
        );
    }

    private function criarBeneficioMunicipal(?\MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\BeneficioMunicipalRequest $req): ?\MarcelaBeh\EmissorNfseNacional\Domain\Entity\BeneficioMunicipal
    {
        if ($req === null) {
            return null;
        }

        return new \MarcelaBeh\EmissorNfseNacional\Domain\Entity\BeneficioMunicipal(
            numeroBeneficio: $req->numeroBeneficio,
            valorReducaoBC: $req->valorReducaoBC,
            percentualReducaoBC: $req->percentualReducaoBC,
        );
    }

    private function criarTribFederal(?\MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\TribFederalRequest $req): ?\MarcelaBeh\EmissorNfseNacional\Domain\Entity\TribFederal
    {
        if ($req === null) {
            return null;
        }

        return new \MarcelaBeh\EmissorNfseNacional\Domain\Entity\TribFederal(
            pisCofinsCst: $req->pisCofinsCst,
            pisCofinsTipo: $req->pisCofinsTipo,
            pisCofinsAliquotaPis: $req->pisCofinsAliquotaPis,
            pisCofinsAliquotaCofins: $req->pisCofinsAliquotaCofins,
            valorRetidoCP: $req->valorRetidoCP,
            valorRetidoIRRF: $req->valorRetidoIRRF,
            valorRetidoCSLL: $req->valorRetidoCSLL,
        );
    }

    private function criarEndereco(
        string $logradouro,
        string $numero,
        ?string $complemento,
        string $bairro,
        string $codigoMunicipio,
        string $uf,
        string $cep,
        ?string $codigoPais = null,
        ?string $codigoPostalExterior = null,
        ?string $nomeCidadeExterior = null,
        ?string $estadoProvinciaExterior = null,
    ): Endereco {
        return new Endereco(
            logradouro: $logradouro,
            numero: $numero,
            complemento: $complemento,
            bairro: $bairro,
            codigoMunicipio: new CodigoMunicipio($codigoMunicipio),
            uf: $uf,
            cep: new Cep($cep),
            codigoPais: $codigoPais,
            codigoPostalExterior: $codigoPostalExterior,
            nomeCidadeExterior: $nomeCidadeExterior,
            estadoProvinciaExterior: $estadoProvinciaExterior,
        );
    }

    /**
     * @param array<string, mixed> $response
     */
    private function processarResposta(array $response, Dps $dps): NfseResponse
    {
        if (!$response['success']) {
            return new NfseResponse(
                success: false,
                mensagem: is_array($response['data']) ? ($response['data']['mensagem'] ?? 'Erro ao emitir DPS') : 'Erro ao emitir DPS',
                dados: is_array($response['data']) ? $response['data'] : null,
            );
        }

        $data = $response['data'];
        $xmlParsed = null;

        if (is_string($data) && !empty($data)) {
            try {
                $parsedList = $this->nfseXmlParser->parse($data);
                if (!empty($parsedList)) {
                    $xmlParsed = $parsedList[0];

                    if ($dps->getIbscbs() !== null) {
                        // A DPS enviou IBS/CBS: a resposta DEVE trazer o grupo IBSCBS. Ausência
                        // (chave presente com valor null, ou ausente) é divergência, não silêncio.
                        $respIbscbs = $xmlParsed['ibscbs'] ?? null;
                        if (!is_array($respIbscbs)) {
                            throw new ServiceException(
                                'Resposta da SEFIN não contém o grupo IBSCBS apesar de a DPS tê-lo enviado'
                            );
                        }
                        $this->ibscbsResponseValidator->validate(
                            $this->buildIbsDataFromDps($dps),
                            $respIbscbs,
                        );
                    }
                }
            } catch (\Throwable $e) {
                throw new ServiceException("Erro ao processar resposta: {$e->getMessage()}", 0, $e);
            }
        }

        return new NfseResponse(
            success: true,
            chaveAcesso: $dps->getChaveAcesso()?->getChave(),
            dados: is_array($data) ? $data : null,
            xml: is_string($data) ? $data : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildIbsDataFromDps(Dps $dps): array
    {
        $ibscbs = $dps->getIbscbs();
        if ($ibscbs === null) {
            return [];
        }

        $data = [
            'tpEnteGov' => $ibscbs->getTpEnteGov()?->value,
            'cClassTrib' => $ibscbs->getCClassTrib()->getCodigo(),
            'cCredPres' => $ibscbs->getCCredPres()?->getCodigo(),
            'vServ' => (string) $dps->getServico()->getValorTotal()->getValue(),
        ];

        if ($ibscbs->getDiferimento() !== null) {
            $data['diferimento'] = [
                'pDifUF' => $ibscbs->getDiferimento()->getPDifUF(),
                'pDifMun' => $ibscbs->getDiferimento()->getPDifMun(),
                'pDifCBS' => $ibscbs->getDiferimento()->getPDifCBS(),
            ];
        }

        if ($ibscbs->hasRefNFSe()) {
            $refList = $ibscbs->getRefNFSeList();
            if ($refList !== null) {
                $data['refNFSeList'] = array_map(
                    fn ($chave) => $chave->getChave(),
                    $refList,
                );
            }
        }

        return $data;
    }
}
