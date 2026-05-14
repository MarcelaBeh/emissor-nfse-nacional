<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Presentation\Factory;

use emissorNfseNacional\NfseNacional\Application\Service\EmitirDpsService;
use emissorNfseNacional\NfseNacional\Application\Service\ConsultarNfseService;
use emissorNfseNacional\NfseNacional\Application\Service\CancelarNfseService;
use emissorNfseNacional\NfseNacional\Application\Validator\DpsValidator;
use emissorNfseNacional\NfseNacional\Application\Validator\EventoValidator;
use emissorNfseNacional\NfseNacional\Application\Validator\ConsultaValidator;
use emissorNfseNacional\NfseNacional\Infrastructure\Config\Configuration;
use emissorNfseNacional\NfseNacional\Infrastructure\Config\ApiEndpoints;
use emissorNfseNacional\NfseNacional\Infrastructure\Http\ApiConnector;
use emissorNfseNacional\NfseNacional\Infrastructure\Http\Client\CurlHttpClient;
use emissorNfseNacional\NfseNacional\Infrastructure\Http\RequestBuilder;
use emissorNfseNacional\NfseNacional\Infrastructure\Security\CertificateManager;
use emissorNfseNacional\NfseNacional\Infrastructure\Security\XmlSigner;
use emissorNfseNacional\NfseNacional\Infrastructure\Xml\Builder\DpsXmlBuilder;
use emissorNfseNacional\NfseNacional\Infrastructure\Xml\Builder\EventoXmlBuilder;
use emissorNfseNacional\NfseNacional\Infrastructure\Xml\Validator\XsdValidator;
use emissorNfseNacional\NfseNacional\Infrastructure\Xml\Parser\NfseXmlParser;
use NFePHP\Common\Certificate;

class ServiceFactory
{
    private Configuration $configuration;
    private CertificateManager $certificateManager;
    private ApiConnector $apiConnector;
    private ApiEndpoints $apiEndpoints;
    private RequestBuilder $requestBuilder;
    private XsdValidator $xsdValidator;
    private XmlSigner $xmlSigner;

    public function __construct(array $config, Certificate $certificate)
    {
        $this->configuration = new Configuration($config);
        $this->certificateManager = new CertificateManager($certificate);
        $this->xmlSigner = new XmlSigner($this->certificateManager->getCertificate());
        $this->apiConnector = $this->createApiConnector();
        $this->apiEndpoints = new ApiEndpoints($this->configuration);
        $this->requestBuilder = new RequestBuilder();
        $this->xsdValidator = new XsdValidator();
    }

    private function createApiConnector(): ApiConnector
    {
        $certFiles = $this->certificateManager->saveTemporaryFiles();

        $httpClient = new CurlHttpClient(
            timeout: 60,
            connectTimeout: 10,
            certPath: $certFiles['cert'],
            privateKeyPath: $certFiles['private'],
            keyPassword: null,
        );

        return new ApiConnector(
            $this->configuration,
            $httpClient,
        );
    }

    public function createEmitirDpsService(): EmitirDpsService
    {
        return new EmitirDpsService(
            apiConnector: $this->apiConnector,
            xmlBuilder: new DpsXmlBuilder(),
            xmlSigner: $this->xmlSigner,
            xsdValidator: $this->xsdValidator,
            validator: new DpsValidator(),
            requestBuilder: $this->requestBuilder,
        );
    }

    public function createConsultarNfseService(): ConsultarNfseService
    {
        return new ConsultarNfseService(
            apiConnector: $this->apiConnector,
            apiEndpoints: $this->apiEndpoints,
            validator: new ConsultaValidator(),
            nfseXmlParser: new NfseXmlParser(),
        );
    }

    public function createCancelarNfseService(): CancelarNfseService
    {
        return new CancelarNfseService(
            apiConnector: $this->apiConnector,
            xmlBuilder: new EventoXmlBuilder(),
            xmlSigner: $this->xmlSigner,
            xsdValidator: $this->xsdValidator,
            validator: new EventoValidator(),
            requestBuilder: $this->requestBuilder,
            apiEndpoints: $this->apiEndpoints,
        );
    }
}
