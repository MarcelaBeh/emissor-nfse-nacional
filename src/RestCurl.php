<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional;

use Exception;
use MarcelaBeh\EmissorNfseNacional\Common\RestBase;
use NFePHP\Common\Certificate;
use NFePHP\Common\Exception\SoapException;
use NFePHP\Common\Signer;
use RuntimeException;
use stdClass;

class RestCurl extends RestBase
{
    private const DEFAULT_URLS = [
        'sefin_homologacao' => 'https://sefin.producaorestrita.nfse.gov.br/SefinNacional',
        'sefin_producao' => 'https://sefin.nfse.gov.br/sefinnacional',
        'adn_homologacao' => 'https://adn.producaorestrita.nfse.gov.br',
        'adn_producao' => 'https://adn.nfse.gov.br',
        'nfse_homologacao' => 'https://www.producaorestrita.nfse.gov.br/EmissorNacional',
        'nfse_producao' => 'https://www.nfse.gov.br/EmissorNacional',
    ];

    private const DEFAULT_OPERATIONS = [
        'consultar_nfse' => 'nfse/{chave}',
        'consultar_dps' => 'dps/{chave}',
        'consultar_eventos' => 'nfse/{chave}/eventos/{tipoEvento}/{nSequencial}',
        'consultar_danfse' => 'danfse/{chave}',
        'consultar_danfse_nfse_certificado' => 'Certificado',
        'consultar_danfse_nfse_download' => 'Notas/Download/DANFSe/{chave}',
        'emitir_nfse' => 'nfse',
        'cancelar_nfse' => 'nfse/{chave}/eventos',
    ];

    private array $urls = [];
    private array $operations = [];
    private stdClass $config;
    private string $url_api;
    private int $connection_timeout = 30;
    private int $timeout = 60; // Aumentado para 60s - APIs do governo são lentas
    private $httpver = null;
    public string $soaperror = '';
    public int $soaperror_code = 0;
    public array $soapinfo = [];
    public string $responseHead = '';
    public string $responseBody = '';
    private string $cookies = '';

    protected array $canonical = [true, false, null, null];

    public function __construct(string $config, Certificate $cert)
    {
        parent::__construct($cert);
        $this->config = json_decode($config);
        $this->certificate = $cert;
        $configFile = __DIR__ . '/../storage/prefeituras.json';
        $this->loadConfigOverrides($configFile, $this->config->prefeitura ?? null);
    }

    private function loadConfigOverrides(string $jsonFile, ?string $context): void
    {
        $content = file_get_contents($jsonFile);
        $json = $content ? json_decode($content, true) : null;

        if (!is_array($json)) {
            throw new RuntimeException("JSON invalido em $jsonFile");
        }

        $contextData = $context && isset($json[$context]) ? $json[$context] : [];

        $this->urls = array_merge(self::DEFAULT_URLS, $contextData['urls'] ?? []);
        $this->operations = array_merge(self::DEFAULT_OPERATIONS, $contextData['operations'] ?? []);
    }

    public function getOperation(string $operation): string
    {
        return $this->operations[$operation] ?? '';
    }

    public function getData(string $operacao, ?string $data = null, int $origem = 1): mixed
    {
        $this->resolveUrl($origem);
        $this->saveTemporarilyKeyFiles();

        try {
            return $this->executeRequest($operacao, $data, $origem);
        } catch (Exception $e) {
            throw SoapException::unableToLoadCurl($e->getMessage());
        }
    }

    public function postData(string $operacao, string $data, int $origem = 1): mixed
    {
        $this->resolveUrl($origem);
        $this->saveTemporarilyKeyFiles();

        try {
            return $this->executeRequest($operacao, $data, $origem, true);
        } catch (Exception $e) {
            throw SoapException::unableToLoadCurl($e->getMessage());
        }
    }

    private function executeRequest(string $operacao, ?string $data, int $origem, bool $forcePost = false): mixed
    {
        $msgSize = $data ? strlen($data) : 0;
        $parameters = [
            'Content-Type: application/json;charset=utf-8;',
            'Content-length: ' . $msgSize,
        ];

        $oCurl = curl_init();
        $api_url = $this->url_api;

        if ($operacao !== '') {
            $api_url .= '/' . $operacao;
        }

        curl_setopt_array($oCurl, [
            CURLOPT_URL => $api_url,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_CONNECTTIMEOUT => $this->connection_timeout,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HEADER => true,
            CURLOPT_HTTP_VERSION => $this->httpver ?? CURL_HTTP_VERSION_NONE,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSLVERSION => CURL_SSLVERSION_DEFAULT,
            CURLOPT_SSLCERT => $this->tempdir . $this->certfile,
            CURLOPT_SSLKEY => $this->tempdir . $this->prifile,
            CURLOPT_RETURNTRANSFER => true,
        ]);

        if (!empty($this->temppass)) {
            curl_setopt($oCurl, CURLOPT_KEYPASSWD, $this->temppass);
        }

        $isPost = $forcePost || ($data !== null && $data !== '');
        if ($isPost) {
            curl_setopt($oCurl, CURLOPT_POST, true);
            curl_setopt($oCurl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($oCurl, CURLOPT_HTTPHEADER, $parameters);
        } elseif ($origem === 3 && $this->cookies !== '') {
            $parameters[] = 'Cookie: ' . $this->cookies;
            curl_setopt($oCurl, CURLOPT_HTTPHEADER, $parameters);
        }

        $response = curl_exec($oCurl);

        $this->soaperror = curl_error($oCurl);
        $this->soaperror_code = curl_errno($oCurl);
        $ainfo = curl_getinfo($oCurl);
        if (is_array($ainfo)) {
            $this->soapinfo = $ainfo;
        }

        $headsize = (int)curl_getinfo($oCurl, CURLINFO_HEADER_SIZE);
        $httpcode = (int)curl_getinfo($oCurl, CURLINFO_HTTP_CODE);
        $contentType = (string)curl_getinfo($oCurl, CURLINFO_CONTENT_TYPE);
        $this->responseHead = trim(substr($response, 0, $headsize));
        $this->responseBody = trim(substr($response, $headsize));

        if ($origem === 3 && $httpcode === 302) {
            $this->captureCookies($this->responseHead);
            return ['sucesso' => true];
        }

        if (str_contains($contentType, 'application/pdf')) {
            return $this->responseBody;
        }

        return json_decode($this->responseBody, true);
    }

    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    public function setConnectionTimeout(int $connection_timeout): void
    {
        $this->connection_timeout = $connection_timeout;
    }

    public function sign(string $content, string $tagname, ?string $mark, string $rootname): string
    {
        if (empty($mark)) {
            $mark = 'Id';
        }
        return Signer::sign(
            $this->certificate,
            $content,
            $tagname,
            $mark,
            OPENSSL_ALGO_SHA1,
            $this->canonical,
            $rootname
        );
    }

    private function resolveUrl(int $origem = 0): void
    {
        $this->url_api = match ($origem) {
            1 => $this->config->tpamb === 1
                ? $this->urls['sefin_producao']
                : $this->urls['sefin_homologacao'],
            2 => $this->config->tpamb === 1
                ? $this->urls['adn_producao']
                : $this->urls['adn_homologacao'],
            3 => $this->config->tpamb === 1
                ? $this->urls['nfse_producao']
                : $this->urls['nfse_homologacao'],
            default => $this->urls['sefin_homologacao'],
        };
    }

    private function captureCookies(string $headers): void
    {
        if (!preg_match_all('/^Set-Cookie:\s*([^;\r\n]*)/mi', $headers, $matches)) {
            return;
        }
        $cookies = array_map('trim', $matches[1]);
        if ($cookies !== []) {
            $this->cookies = implode('; ', $cookies);
        }
    }
}
