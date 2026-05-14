<?php

// Configurações apenas para exemplo (remover em produção)
error_reporting(E_ALL);
ini_set('display_errors', 'On');

date_default_timezone_set('America/Manaus');
include __DIR__ . '/../vendor/autoload.php';

try {
    $config = new stdClass();
    $config->tpamb = 1; //1 - Produção, 2 - Homologação
    $configJson = json_encode($config);
    $content = file_get_contents('certificado.pfx');
    $password = 'senha_certificado';
    $cert = \NFePHP\Common\Certificate::readPfx($content, $password);
    $tools = new \MarcelaBeh\EmissorNfseNacional\Tools($configJson, $cert);

    //Informar chave da NFSe, retorna XML
    $response = $tools->consultarNfseChave('00000000000000000000000000000000000000000000000000');
    dd($response);

} catch (Exception $e) {
    dd($e->getMessage(), $e);
}