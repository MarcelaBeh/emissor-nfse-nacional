<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Presentation\Factory;

class NfseFactory
{
    public static function createFromArray(array $data): \emissorNfseNacional\NfseNacional\Domain\Entity\Nfse
    {
        return new \emissorNfseNacional\NfseNacional\Domain\Entity\Nfse(
            chaveAcesso: $data['chaveAcesso'] ?? '',
            numero: $data['numero'] ?? '',
            codigoVerificacao: $data['codigoVerificacao'] ?? '',
            serie: $data['serie'] ?? '',
            dataEmissao: $data['dataEmissao'] ?? '',
            prestadorCnpj: $data['prestador']['cnpj'] ?? '',
            prestadorNome: $data['prestador']['nome'] ?? '',
            tomadorNome: $data['tomador']['nome'] ?? '',
            valorServicos: $data['valorServicos'] ?? '0',
            valorIss: $data['valorIss'] ?? '0',
            xml: $data['xml'] ?? null,
        );
    }
}
