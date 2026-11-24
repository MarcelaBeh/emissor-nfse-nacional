<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 'On');
date_default_timezone_set('America/Manaus');

require __DIR__ . '/../vendor/autoload.php';

use MarcelaBeh\EmissorNfseNacional\Presentation\Facade\NfseNacionalFacade;

$config = [
    'tpAmb' => 2,
    'prefeitura' => '3501608',
];

$certContent = file_get_contents('certificado.pfx');
$certPassword = 'senha_certificado';
$cert = \NFePHP\Common\Certificate::readPfx($certContent, $certPassword);

$facade = NfseNacionalFacade::create($config, $cert);

// Id da DPS (TSIdDPS): "DPS" + 42 dígitos. É o Id que a lib gerou ao emitir.
$idDps = 'DPS355030821144477700016100001000000000000123';

try {
    $chave = $facade->consultarChavePorDps($idDps);
    echo $chave === null
        ? 'Nenhuma NFS-e foi gerada com esse Id de DPS.' . PHP_EOL
        : 'Chave de acesso da NFS-e: ' . $chave . PHP_EOL;
} catch (\Throwable $e) {
    echo 'ERRO: ' . $e->getMessage() . PHP_EOL;
}
