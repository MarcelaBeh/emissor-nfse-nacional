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

$chave = '12345678901234567890123456789012345678901234567890';

try {
    $dados = $facade->consultarDpsPorChave($chave);
    echo 'Resposta:' . PHP_EOL;
    print_r($dados);
} catch (\Throwable $e) {
    echo 'ERRO: ' . $e->getMessage() . PHP_EOL;
}
