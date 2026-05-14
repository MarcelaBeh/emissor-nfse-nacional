# Changelog

Todas as alterações notáveis deste projeto serão documentadas neste arquivo.

Este projeto segue [Semantic Versioning](https://semver.org/) e [Keep a Changelog](https://keepachangelog.com/).

## [Unreleased]

### Adicionado
- PHPStan nível 8 com 0 erros
- 481 testes unitários
- Estrutura Clean Architecture completa

### Alterado
- Melhoria na tipagem de todos os arquivos do `src/`

---

## [2.0.0] - 2026-05-14

### Added

#### Arquitetura
- **Clean Architecture** implementada com 4 camadas:
  - `Domain/` - Entidades, Value Objects, Enums, Contracts
  - `Application/` - Services, DTOs, Validators
  - `Infrastructure/` - HTTP, XML, Security, Repository
  - `Presentation/` - Facade, Factories

#### Value Objects (10)
- `Cnpj` - CNPJ com validação de dígito verificador
- `Cpf` - CPF com validação de dígito verificador
- `ChaveAcesso` - Chave de acesso NFS-e (50 caracteres)
- `CodigoMunicipio` - Código IBGE de município (7 dígitos)
- `Cep` - CEP brasileiro
- `Email` - Email com validação
- `Telefone` - Telefone brasileiro
- `Money` - Valor monetário (sem float)
- `InscricaoMunicipal` - Inscrição municipal
- `Nif` - Número de Identificação Fiscal

#### Entities (8)
- `Dps` - Documento Principal de Serviços
- `Nfse` - Nota Fiscal de Serviço Eletrônica
- `Prestador` - Prestador de serviço
- `Tomador` - Tomador do serviço
- `Intermediario` - Intermediário (opcional)
- `Servico` - Detalhes do serviço
- `Evento` - Evento da NFS-e
- `Endereco` - Endereço brasileiro

#### Services (3)
- `EmitirDpsService` - Emissão de DPS
- `ConsultarNfseService` - Consulta de NFS-e
- `CancelarNfseService` - Cancelamento

#### Infrastructure
- `CurlHttpClient` - Cliente HTTP com cURL
- `ApiConnector` - Conector de API
- `RequestBuilder` - Construtor de requests
- `ResponseParser` - Parser de responses
- `DpsXmlBuilder` - Builder de XML DPS
- `EventoXmlBuilder` - Builder de XML Evento
- `NfseXmlParser` - Parser de XML NFS-e
- `DpsXmlParser` - Parser de XML DPS
- `ErrorXmlParser` - Parser de erros
- `XsdValidator` - Validação XSD
- `CertificateManager` - Gerenciamento de certificados
- `CertificateValidator` - Validação de certificados
- `XmlSigner` - Assinatura XML

#### Presentation
- `NfseNacionalFacade` - API unificada
- `ServiceFactory` - Factory de serviços
- `ConfigFactory` - Factory de configuração
- `DpsFactory` - Factory de DPS
- `NfseFactory` - Factory de NFS-e

### Changed
- Código completamente reestruturado
- PHPStan nível 5 → 8
- Testes de 25 → 481

### Deprecated
- APIs legadas mantidas para compatibilidade (v1)

---

## [1.x.x] - Releases anteriores

### Legacy (v1)
- API original baseada em componentes NFePHP
- Estrutura monolith
- 25 testes unitários
- PHPStan nível 5

---

## Formato

```
## [VERSION] - YYYY-MM-DD

### Added     - Novas funcionalidades
### Changed   - Alterações em funcionalidades existentes
### Deprecated - Funcionalidades que serão removidas
### Fixed      - Correções de bugs
### Security   - Correções de segurança
### Removed    - Funcionalidades removidas
```

---

## Links

- [GitHub Releases](https://github.com/marcelabeh/emissor-nfse-nacional/releases)
- [Compare Versions](https://github.com/marcelabeh/emissor-nfse-nacional/compare)