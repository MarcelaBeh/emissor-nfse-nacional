# Arquitetura - Emissor NFSe Nacional

**Versão:** 2.0
**Data:** 14 de maio de 2026

---

## 🎯 Visão Geral

Este documento descreve a arquitetura do pacote `emissor-nfse-nacional`, uma biblioteca PHP para integração com a API Nacional de NFS-e.

### Princípios

1. **Clean Architecture** - Separação clara de responsabilidades
2. **SOLID** - Princípios de design orientado a objetos
3. **PSR Standards** - PSR-4 (autoload), PSR-12 (estilo)
4. **Imutabilidade** - Value Objects são `readonly` e `final`

---

## 🏗️ Estrutura de Camadas

```
src/
├── Domain/                    # 🔵 Núcleo (sem dependências)
│   ├── Entity/                 # Entidades de negócio
│   ├── ValueObject/           # Objetos de valor imutáveis
│   ├── Enum/                  # Enumerações
│   ├── Service/               # Serviços de domínio (ex: DpsIdService)
│   └── Contract/              # Interfaces (Ports, ex: CstClassTribRepository)
│
├── Application/               # 🟢 Casos de uso
│   ├── Service/               # Services de aplicação
│   ├── DTO/                   # Data Transfer Objects
│   │   ├── Request/           # DTOs de entrada
│   │   └── Response/          # DTOs de saída
│   └── Validator/             # Validadores
│
├── Infrastructure/            # 🔴 Implementações externas
│   ├── Http/                  # Comunicação HTTP
│   │   ├── Client/            # Implementações de cliente HTTP
│   │   └── Exception/         # Exceções HTTP
│   ├── Xml/                   # Processamento XML
│   │   ├── Builder/           # Construtores de XML
│   │   ├── Parser/            # Parsers de XML
│   │   └── Validator/         # Validação XSD
│   ├── Security/             # Segurança, certificados e logging
│   │   ├── Contract/          # Interfaces (LoggerInterface, XmlSignerInterface)
│   │   ├── NullLogger.php     # Logger no-op (padrão)
│   │   ├── SanitizedLogger.php
│   │   ├── SensitiveDataSanitizer.php
│   │   └── Exception/         # Exceções
│   ├── Repository/           # Repositórios
│   └── Config/               # Configuração
│
└── Presentation/              # 🟡 API pública
    ├── Facade/               # Facade principal
    └── Factory/              # Factories
```

---

## 🔵 Domain Layer (Núcleo)

**Responsabilidade:** Regras de negócio puras, sem dependências externas.

### Características

- Zero dependências de outras camadas
- Classes `readonly` e `final`
- Validação no construtor
- Exceções específicas para erros de negócio

### Entity vs Value Object

| Entity | Value Object |
|--------|--------------|
| Identidade única | Imutável por valor |
| Mutável | `readonly` |
| Ex: `Dps`, `Evento`, `Prestador` | Ex: `Cnpj`, `Cpf`, `Money` |

### Exemplo - Cnpj Value Object

```php
final readonly class Cnpj
{
    public function __construct(string $numero)
    {
        $numero = preg_replace('/[^0-9]/', '', $numero);

        if (strlen($numero) !== 14) {
            throw new InvalidCnpjException('CNPJ deve ter 14 dígitos');
        }

        if (!$this->validarDigitoVerificador($numero)) {
            throw new InvalidCnpjException('CNPJ com dígito verificador inválido');
        }

        $this->numero = $numero;
    }
}
```

---

## 🟢 Application Layer

**Responsabilidade:** Orquestrar fluxo de dados entre Domain e Infrastructure.

### Service Pattern

- Recebe DTO de Request
- Coordena Domain e Infrastructure
- Retorna DTO de Response
- Sem lógica de negócio (delega para Domain)

### Exemplo - EmitirDpsService

```php
class EmitirDpsService
{
    public function __construct(
        private ApiConnectorInterface $apiConnector,
        private XmlBuilderInterface $xmlBuilder,
        private XmlSignerInterface $xmlSigner,
        private XsdValidatorInterface $xsdValidator,
        private DpsValidator $validator,
        private RequestBuilder $requestBuilder,
        private NfseXmlParser $nfseXmlParser,
        private IbscbsResponseValidator $ibscbsResponseValidator,
        private ApiEndpoints $apiEndpoints,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function executar(DpsRequest $request): NfseResponse
    {
        // 1. Validar input (void; lança exceção em caso de erro)
        $this->validator->validate($request);

        // 2. Construir a entidade Dps a partir do request e gerar a chave
        $dps = $this->criarDpsFromRequest($request);
        $dps->gerarChaveAcesso();

        // 3. Construir XML
        $xml = $this->xmlBuilder->build($dps);

        // 4. Validar XSD (ANTES de assinar)
        $this->xsdValidator->validate($xml, 'DPS');

        // 5. Assinar XML
        $xmlAssinado = $this->xmlSigner->sign($xml, 'infDPS', 'DPS');

        // 6. Montar payload e enviar para a API
        $payload = $this->requestBuilder->buildDpsPayload($xmlAssinado);
        $response = $this->apiConnector->post('nfse', $payload);

        // 7. Processar resposta
        return $this->processarResposta($response, $dps);
    }
}
```

---

## 🔴 Infrastructure Layer

**Responsabilidade:** Implementações de interfaces definidas no Domain. Adapta bibliotecas externas.

### Componentes

#### HTTP (ApiConnector, CurlHttpClient)
- Comunicação com API governamental
- Tratamento de erros de conexão
- Timeouts e retry

#### XML (Builders, Parsers)
- Construção de XML conforme schemas NFS-e v1.01
- Parsing de respostas
- Validação XSD

#### Security (CertificateManager, XmlSigner)
- Gerenciamento de certificados digitais
- Assinatura XML com algoritmos adequados
- Validação de expiração

### Repository Pattern

O contrato (interface) `CstClassTribRepository` vive na camada **Domain**
(`src/Domain/Contract/CstClassTribRepository.php`). Apenas as **implementações**
ficam na camada **Infrastructure** (`src/Infrastructure/Repository/`).

```php
// src/Domain/Contract/CstClassTribRepository.php (Domain)
interface CstClassTribRepository
{
    public function findByCode(string $cClassTrib): ?CstClassTribProperties;
    public function findByCst(string $cst): array;
}

// src/Infrastructure/Repository/ (Infrastructure)
class InMemoryCstClassTribRepository implements CstClassTribRepository { }
class FileCstClassTribRepository implements CstClassTribRepository { }
class CachedCstClassTribRepository implements CstClassTribRepository { }
```

---

## 🟡 Presentation Layer

**Responsabilidade:** API pública simples para consumidores da biblioteca.

### Facade Pattern

```php
class NfseNacionalFacade
{
    private function __construct(
        private Configuration|array $config,
        private Certificate $certificado,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public static function create(
        Configuration|array $config,
        Certificate $certificado,
        LoggerInterface $logger = new NullLogger(),
    ): self {
        return new self($config, $certificado, $logger);
    }

    public function emitirDps(DpsRequest $request): NfseResponse
    {
        return $this->emitirDpsService->executar($request);
    }

    public function consultarPorChave(string $chave, bool $encoding = false): ?NfseResponse
    {
        return $this->consultarNfseService->consultarPorChave($chave, $encoding);
    }
}
```

### Factory Pattern

```php
class ServiceFactory
{
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
}
```

---

## 🔄 Fluxo de Dados

```
Cliente (ex: Laravel App)
    │
    ▼
NfseNacionalFacade (Presentation)
    │
    ├──► ServiceFactory (cria dependências)
    │
    ▼
EmitirDpsService (Application)
    │
    ├──► DpsValidator (valida DpsRequest)
    │         │
    │         ▼
    │     Dps (Domain Entity)
    │
    ├──► DpsXmlBuilder (Infrastructure)
    │         │
    │         ▼
    │     XML string
    │
    ├──► XmlSigner (Infrastructure)
    │         │
    │         ▼
    │     XML assinado
    │
    ├──► XsdValidator (Infrastructure)
    │
    ├──► ApiConnector (Infrastructure)
    │         │
    │         ▼
    │     Response HTTP
    │
    └──► NfseXmlParser (Infrastructure)
              │
              ▼
         NfseResponse (Application DTO)
```

---

## 📦 Dependencies

### Externas (vendor)

- `nfephp-org/sped-common` - Certificado digital e assinatura (NFePHP)

### Internas (src/)

```
src/Domain/           ← Sem dependências
src/Application/      ← Depende de Domain
src/Infrastructure/  ← Depende de Domain e Application
src/Presentation/     ← Depende de todas
```

---

## 🔒 Segurança

### Certificados

- Armazenamento temporário com permissões 0600
- Cleanup automático após uso
- Validação de expiração

### Dados Sensíveis

- CNPJ/CPF nunca logados integralmente
- Emails ofuscados em logs
- Chaves privadas nunca em logs

### Validação

- Todos inputs validados antes de uso
- XML validado contra XSD antes de envio
- Encoding ISO-8859-1/UTF-8 tratado

---

## 📊 Métricas

| Métrica | Valor |
|---------|-------|
| PHPStan Level | 8 (0 erros) |
| Testes | 481 passando |
| Code Coverage | Em progresso |
| PSR-12 | ✅ Conformante |

---

## 📚 Referências

- [Clean Architecture](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)
- [PSR-4](https://www.php-fig.org/psr/psr-4/)
- [PSR-12](https://www.php-fig.org/psr/psr-12/)
- [Symfony DI](https://symfony.com/doc/current/service_container.html)