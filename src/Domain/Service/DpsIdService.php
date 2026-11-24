<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Service;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Dps;

/**
 * Domain Service para geração do identificador da DPS.
 *
 * Regra oficial TSIdDPS (XSD v1.01):
 * "DPS" + Cód.Mun (7) + Tipo de Inscrição Federal (1)
 * + Inscrição Federal (14) + Série (5) + Núm. DPS (15)
 * Total: 45 caracteres
 *
 * Tipo de Inscrição Federal:
 *  1 = CPF (inscrição com 11 dígitos + 3 zeros à esquerda = 14)
 *  2 = CNPJ (inscrição com 14 dígitos)
 */
final readonly class DpsIdService
{
    /**
     * Tipo de inscrição federal (1 CPF, 2 CNPJ) e a inscrição com 14 posições, na forma do TSIdDPS (CPF completado
     * com zeros à esquerda).
     *
     * @return array{string, string}
     */
    public static function inscricaoFederal(bool $isCnpj, string $numero): array
    {
        return $isCnpj ? ['2', $numero] : ['1', str_pad($numero, 14, '0', STR_PAD_LEFT)];
    }

    public function generate(Dps $dps): string
    {
        $codigoMunicipio = $dps->getCodigoMunicipioEmissor()->getCodigo();
        $prestador = $dps->getPrestador();

        if ($prestador->getCnpj() !== null) {
            [$tipoInscricao, $inscricaoFederal] = self::inscricaoFederal(true, $prestador->getCnpj()->getNumero());
        } elseif ($prestador->getCpf() !== null) {
            [$tipoInscricao, $inscricaoFederal] = self::inscricaoFederal(false, $prestador->getCpf()->getNumero());
        } else {
            throw new \RuntimeException('Prestador deve ter CNPJ ou CPF para gerar Id da DPS');
        }

        $serie = str_pad((string) $dps->getSerie(), 5, '0', STR_PAD_LEFT);
        $numero = str_pad((string) $dps->getNumero(), 15, '0', STR_PAD_LEFT);

        return 'DPS'
            . $codigoMunicipio
            . $tipoInscricao
            . $inscricaoFederal
            . $serie
            . $numero;
    }
}
