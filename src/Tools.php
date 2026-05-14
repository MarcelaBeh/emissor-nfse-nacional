<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional;

use MarcelaBeh\EmissorNfseNacional\Validators\ChaveAcessoValidator;
use MarcelaBeh\EmissorNfseNacional\Validators\XsdValidator;
use NFePHP\Common\Certificate;
use RuntimeException;
use stdClass;

class Tools extends RestCurl
{
    public function __construct(string $config, Certificate $cert)
    {
        parent::__construct($config, $cert);
    }

    public function consultarNfseChave(string $chave, bool $encoding = true): mixed
    {
        ChaveAcessoValidator::validate($chave);

        $operacao = str_replace('{chave}', $chave, $this->getOperation('consultar_nfse'));
        $retorno = $this->getData($operacao);

        if (isset($retorno['erro'])) {
            return $retorno;
        }
        if ($retorno && isset($retorno['nfseXmlGZipB64'])) {
            $base_decode = base64_decode($retorno['nfseXmlGZipB64'], true);
            if ($base_decode === false) {
                throw new RuntimeException('Falha ao decodificar base64 da resposta NFSe');
            }
            $gz_decode = gzdecode($base_decode);
            if ($gz_decode === false) {
                throw new RuntimeException('Falha ao descomprimir dados GZip da resposta NFSe');
            }
            return $encoding ? mb_convert_encoding($gz_decode, 'ISO-8859-1') : $gz_decode;
        }
        return null;
    }

    public function consultarDpsChave(string $chave): mixed
    {
        ChaveAcessoValidator::validate($chave);

        $operacao = str_replace('{chave}', $chave, $this->getOperation('consultar_dps'));
        return $this->getData($operacao);
    }

    public function consultarNfseEventos(string $chave, ?string $tipoEvento = null, ?string $nSequencial = null): mixed
    {
        ChaveAcessoValidator::validate($chave);

        $operacao = str_replace('{chave}', $chave, $this->getOperation('consultar_eventos'));

        if ($tipoEvento === null || $tipoEvento === '') {
            $operacao = str_replace('/{tipoEvento}/{nSequencial}', '', $operacao);
        } else {
            $operacao = str_replace('{tipoEvento}', $tipoEvento, $operacao);
        }

        if ($nSequencial === null || $nSequencial === '') {
            $operacao = str_replace('/{nSequencial}', '', $operacao);
        } else {
            $operacao = str_replace('{nSequencial}', $nSequencial, $operacao);
        }

        return $this->getData($operacao);
    }

    public function consultarDanfse(string $chave): mixed
    {
        ChaveAcessoValidator::validate($chave);

        $operacao = str_replace('{chave}', $chave, $this->getOperation('consultar_danfse'));
        $retorno = $this->getData($operacao, null, 2);

        if (isset($retorno['erro'])) {
            return $retorno;
        }
        if (!empty($retorno)) {
            return $retorno;
        }
        return $this->consultarDanfseNfse($chave);
    }

    public function consultarDanfseNfse(string $chave): mixed
    {
        ChaveAcessoValidator::validate($chave);

        $operacao = $this->getOperation('consultar_danfse_nfse_certificado');
        $retorno = $this->getData($operacao, null, 3);

        if (is_array($retorno) && ($retorno['sucesso'] ?? false)) {
            $operacao = str_replace('{chave}', $chave, $this->getOperation('consultar_danfse_nfse_download'));
            $retorno = $this->getData($operacao, null, 3);
        }

        if (isset($retorno['erro'])) {
            return $retorno;
        }
        return $retorno;
    }

    public function enviaDps(string $content): mixed
    {
        XsdValidator::validate($content, 'DPS');

        $content = $this->sign($content, 'infDPS', '', 'DPS');
        $content = '<?xml version="1.0" encoding="UTF-8"?>' . $content;
        $gz = gzencode($content);
        $data = base64_encode($gz);
        $dados = ['dpsXmlGZipB64' => $data];
        $operacao = $this->getOperation('emitir_nfse');
        return $this->postData($operacao, json_encode($dados));
    }

    public function cancelaNfse(stdClass $std): mixed
    {
        $dps = new Dps($std);
        $content = $dps->renderEvento($std);

        XsdValidator::validate($content, 'pedRegEvento');

        $content = $this->sign($content, 'infPedReg', '', 'pedRegEvento');
        $content = '<?xml version="1.0" encoding="UTF-8"?>' . $content;
        $gz = gzencode($content);
        $data = base64_encode($gz);
        $dados = ['pedidoRegistroEventoXmlGZipB64' => $data];
        $operacao = str_replace('{chave}', $std->infPedReg->chNFSe, $this->getOperation('cancelar_nfse'));
        return $this->postData($operacao, json_encode($dados));
    }
}
