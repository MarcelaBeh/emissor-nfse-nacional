<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Application\Service;

use emissorNfseNacional\NfseNacional\Application\DTO\Request\DpsRequest;
use emissorNfseNacional\NfseNacional\Application\DTO\Response\NfseResponse;
use emissorNfseNacional\NfseNacional\Application\Exception\ServiceException;
use emissorNfseNacional\NfseNacional\Application\Exception\ValidationException;
use emissorNfseNacional\NfseNacional\Application\Validator\DpsValidator;
use emissorNfseNacional\NfseNacional\Domain\Entity\Dps;
use emissorNfseNacional\NfseNacional\Domain\Entity\Prestador;
use emissorNfseNacional\NfseNacional\Domain\Entity\Tomador;
use emissorNfseNacional\NfseNacional\Domain\Entity\Servico;
use emissorNfseNacional\NfseNacional\Domain\Entity\Endereco;
use emissorNfseNacional\NfseNacional\Domain\Entity\Substituicao;
use emissorNfseNacional\NfseNacional\Domain\Enum\TipoAmbiente;
use emissorNfseNacional\NfseNacional\Domain\Enum\TipoEmissao;
use emissorNfseNacional\NfseNacional\Domain\ValueObject\Cnpj;
use emissorNfseNacional\NfseNacional\Domain\ValueObject\Cpf;
use emissorNfseNacional\NfseNacional\Domain\ValueObject\Money;
use emissorNfseNacional\NfseNacional\Domain\ValueObject\CodigoMunicipio;
use emissorNfseNacional\NfseNacional\Domain\ValueObject\Cep;
use emissorNfseNacional\NfseNacional\Domain\ValueObject\ChaveAcesso;
use emissorNfseNacional\NfseNacional\Infrastructure\Http\ApiConnector;
use emissorNfseNacional\NfseNacional\Infrastructure\Http\RequestBuilder;
use emissorNfseNacional\NfseNacional\Infrastructure\Security\XmlSigner;
use emissorNfseNacional\NfseNacional\Infrastructure\Xml\Builder\DpsXmlBuilder;
use emissorNfseNacional\NfseNacional\Infrastructure\Xml\Validator\XsdValidator;
use emissorNfseNacional\NfseNacional\Infrastructure\Http\Exception\HttpException;
use emissorNfseNacional\NfseNacional\Domain\Exception\DomainException;

class EmitirDpsService
{
    public function __construct(
        private ApiConnector $apiConnector,
        private DpsXmlBuilder $xmlBuilder,
        private XmlSigner $xmlSigner,
        private XsdValidator $xsdValidator,
        private DpsValidator $validator,
        private RequestBuilder $requestBuilder,
    ) {}

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
            telefone: $request->prestador->telefone ? new \emissorNfseNacional\NfseNacional\Domain\ValueObject\Telefone($request->prestador->telefone) : null,
            email: $request->prestador->email ? new \emissorNfseNacional\NfseNacional\Domain\ValueObject\Email($request->prestador->email) : null,
            endereco: $this->criarEndereco(
                $request->prestador->logradouro,
                $request->prestador->numero,
                $request->prestador->complemento,
                $request->prestador->bairro,
                $request->prestador->codigoMunicipio,
                $request->prestador->uf,
                $request->prestador->cep
            ),
            regimeTributario: \emissorNfseNacional\NfseNacional\Domain\Enum\RegimeTributario::from($request->prestador->regimeTributario),
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
            telefone: $request->tomador->telefone ? new \emissorNfseNacional\NfseNacional\Domain\ValueObject\Telefone($request->tomador->telefone) : null,
            email: $request->tomador->email ? new \emissorNfseNacional\NfseNacional\Domain\ValueObject\Email($request->tomador->email) : null,
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
