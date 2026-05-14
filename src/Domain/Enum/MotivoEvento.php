<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Enum;

enum MotivoEvento: string
{
    case ERRO_EMISSAO = '01';
    case SERVICO_NAO_PRESTADO = '02';
    case DESENQUADRAMENTO_SIMPLES = '03';
    case ENQUADRAMENTO_SIMPLES = '04';
    case INCLUSAO_IMUNIDADE = '05';
    case EXCLUSAO_IMUNIDADE = '06';
    case REJEICAO_TOMADOR = '07';
    case OUTROS = '99';

    public function descricao(): string
    {
        return match ($this) {
            self::ERRO_EMISSAO => 'Erro na Emissão',
            self::SERVICO_NAO_PRESTADO => 'Serviço não Prestado',
            self::DESENQUADRAMENTO_SIMPLES => 'Desenquadramento de NFS-e do Simples Nacional',
            self::ENQUADRAMENTO_SIMPLES => 'Enquadramento de NFS-e no Simples Nacional',
            self::INCLUSAO_IMUNIDADE => 'Inclusão Retroativa de Imunidade/Isenção',
            self::EXCLUSAO_IMUNIDADE => 'Exclusão Retroativa de Imunidade/Isenção',
            self::REJEICAO_TOMADOR => 'Rejeição pelo tomador/intermediário',
            self::OUTROS => 'Outros',
        };
    }
}
