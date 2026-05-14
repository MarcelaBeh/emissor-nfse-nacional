<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Presentation\Factory;

use emissorNfseNacional\NfseNacional\Domain\Entity\Dps;
use emissorNfseNacional\NfseNacional\Domain\Entity\Prestador;
use emissorNfseNacional\NfseNacional\Domain\Entity\Tomador;
use emissorNfseNacional\NfseNacional\Domain\Entity\Servico;
use emissorNfseNacional\NfseNacional\Domain\Entity\Endereco;
use emissorNfseNacional\NfseNacional\Domain\Entity\Evento;
use emissorNfseNacional\NfseNacional\Domain\Enum\TipoAmbiente;
use emissorNfseNacional\NfseNacional\Domain\Enum\TipoEmissao;
use emissorNfseNacional\NfseNacional\Domain\Enum\RegimeTributario;
use emissorNfseNacional\NfseNacional\Domain\Enum\TipoEvento;
use emissorNfseNacional\NfseNacional\Domain\ValueObject\Cnpj;
use emissorNfseNacional\NfseNacional\Domain\ValueObject\Cpf;
use emissorNfseNacional\NfseNacional\Domain\ValueObject\Money;
use emissorNfseNacional\NfseNacional\Domain\ValueObject\CodigoMunicipio;
use emissorNfseNacional\NfseNacional\Domain\ValueObject\Cep;

class DpsFactory
{
    public static function create(
        array $data,
    ): Dps {
        $prestadorData = $data['prestador'];
        $tomadorData = $data['tomador'];
        $servicoData = $data['servico'];

        $prestador = new Prestador(
            documento: isset($prestadorData['cnpj']) ? new Cnpj($prestadorData['cnpj']) : new Cpf($prestadorData['cpf']),
            inscricaoMunicipal: $prestadorData['inscricaoMunicipal'] ?? null,
            razaoSocial: $prestadorData['razaoSocial'],
            nomeFantasia: $prestadorData['nomeFantasia'] ?? null,
            telefone: null,
            email: null,
            endereco: self::createEndereco($prestadorData['endereco']),
            regimeTributario: RegimeTributario::from($prestadorData['regimeTributario']),
            nif: $prestadorData['nif'] ?? null,
            caepf: $prestadorData['caepf'] ?? null,
        );

        $tomador = new Tomador(
            documento: isset($tomadorData['cnpj']) ? new Cnpj($tomadorData['cnpj']) : (isset($tomadorData['cpf']) ? new Cpf($tomadorData['cpf']) : null),
            razaoSocial: $tomadorData['razaoSocial'],
            nomeFantasia: $tomadorData['nomeFantasia'] ?? null,
            telefone: null,
            email: null,
            endereco: self::createEndereco($tomadorData['endereco']),
            nif: $tomadorData['nif'] ?? null,
            inscricaoMunicipal: $tomadorData['inscricaoMunicipal'] ?? null,
        );

        $servico = new Servico(
            discriminacao: $servicoData['discriminacao'],
            codigoTributacao: $servicoData['codigoTributacao'],
            localPrestacao: new CodigoMunicipio($servicoData['codigoMunicipioPrestacao']),
            valorServicos: new Money($servicoData['valorServicos']),
            valorDeducoes: new Money($servicoData['valorDeducoes'] ?? 0),
            descontoIncondicionado: new Money($servicoData['descontoIncondicionado'] ?? 0),
            descontoCondicionado: new Money($servicoData['descontoCondicionado'] ?? 0),
            aliquotaIss: $servicoData['aliquotaIss'],
            codigoNbs: $servicoData['codigoNbs'] ?? null,
        );

        return new Dps(
            tipoAmbiente: TipoAmbiente::from($data['tipoAmbiente']),
            dataEmissao: new \DateTimeImmutable($data['dataEmissao']),
            versaoAplicacao: $data['versaoAplicacao'],
            serie: $data['serie'],
            numero: $data['numero'],
            dataCompetencia: new \DateTimeImmutable($data['dataCompetencia']),
            tipoEmissao: TipoEmissao::from($data['tipoEmissao']),
            codigoMunicipioEmissor: new CodigoMunicipio($data['codigoMunicipioEmissor']),
            prestador: $prestador,
            tomador: $tomador,
            servico: $servico,
        );
    }

    private static function createEndereco(array $data): Endereco
    {
        return new Endereco(
            logradouro: $data['logradouro'],
            numero: $data['numero'],
            complemento: $data['complemento'] ?? null,
            bairro: $data['bairro'],
            codigoMunicipio: new CodigoMunicipio($data['codigoMunicipio']),
            uf: $data['uf'],
            cep: new Cep($data['cep']),
        );
    }
}
