<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Presentation\Factory;

use MarcelaBeh\EmissorNfseNacional\Application\Service\CancelarNfseService;
use MarcelaBeh\EmissorNfseNacional\Application\Service\ConsultarNfseService;
use MarcelaBeh\EmissorNfseNacional\Application\Service\EmitirDpsService;
use MarcelaBeh\EmissorNfseNacional\Application\Validator\ConsultaValidator;
use MarcelaBeh\EmissorNfseNacional\Application\Validator\DpsValidator;
use MarcelaBeh\EmissorNfseNacional\Application\Validator\EventoValidator;
use MarcelaBeh\EmissorNfseNacional\Application\Validator\IbscbsResponseValidator;
use MarcelaBeh\EmissorNfseNacional\Domain\Contract\CstClassTribRepository;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Config\ApiEndpoints;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Config\Configuration;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\ApiConnector;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\Client\CurlHttpClient;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\RequestBuilder;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Repository\CachedCstClassTribRepository;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Repository\FileCstClassTribRepository;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Security\CertificateManager;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Security\XmlSigner;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Builder\DpsXmlBuilder;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Builder\EventoXmlBuilder;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Parser\NfseXmlParser;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Validator\XsdValidator;
use NFePHP\Common\Certificate;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ServiceFactory
{
    private Configuration $configuration;
    private CertificateManager $certificateManager;
    private ApiConnector $apiConnector;
    private ApiEndpoints $apiEndpoints;
    private RequestBuilder $requestBuilder;
    private XsdValidator $xsdValidator;
    private XmlSigner $xmlSigner;
    private LoggerInterface $logger;

    /**
     * @param Configuration|array<string, mixed> $config
     * @param LoggerInterface $logger logger injetado nos serviços. Padrão: NullLogger (sem saída).
     *        Para rastreabilidade em produção sem vazar dados sensíveis, passe um SanitizedLogger.
     */
    public function __construct(
        Configuration|array $config,
        Certificate $certificate,
        LoggerInterface $logger = new NullLogger(),
    ) {
        $this->logger = $logger;
        $this->configuration = $config instanceof Configuration ? $config : new Configuration($config);
        $this->certificateManager = new CertificateManager($certificate);
        $this->xmlSigner = new XmlSigner($this->certificateManager->getCertificate());
        $this->apiConnector = $this->createApiConnector();
        $this->apiEndpoints = new ApiEndpoints($this->configuration);
        $this->requestBuilder = new RequestBuilder();
        $this->xsdValidator = new XsdValidator();
    }

    private function createApiConnector(): ApiConnector
    {
        // O cliente HTTP passa a possuir o CertificateManager: ele é o único
        // consumidor dos PEMs (lidos pelo cURL a cada request) e, possuindo o
        // manager, mantém os arquivos vivos enquanto existir. Posse e consumo
        // ficam no mesmo objeto, sem keep-alive externo dependente do GC.
        $httpClient = new CurlHttpClient(
            timeout: 60,
            connectTimeout: 10,
            certificateManager: $this->certificateManager,
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
            validator: new DpsValidator($this->createCstClassTribRepository()),
            requestBuilder: $this->requestBuilder,
            nfseXmlParser: new NfseXmlParser(),
            ibscbsResponseValidator: new IbscbsResponseValidator(),
            apiEndpoints: $this->apiEndpoints,
            logger: $this->logger,
        );
    }

    /**
     * Repositório da tabela oficial CST x cClassTrib (regras de negócio do IBS/CBS da reforma
     * tributária). Sem ele, validateIbsCbsCstClassTrib do DpsValidator fica inativo.
     */
    private function createCstClassTribRepository(): CstClassTribRepository
    {
        $tabela = __DIR__ . '/../../../storage/cClassTrib.json';

        return new CachedCstClassTribRepository(
            new FileCstClassTribRepository($tabela),
        );
    }

    public function createConsultarNfseService(): ConsultarNfseService
    {
        return new ConsultarNfseService(
            apiConnector: $this->apiConnector,
            apiEndpoints: $this->apiEndpoints,
            validator: new ConsultaValidator(),
            nfseXmlParser: new NfseXmlParser(),
            logger: $this->logger,
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
            logger: $this->logger,
        );
    }
}
