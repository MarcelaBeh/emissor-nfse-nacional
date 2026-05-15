# Emissor NFSe Nacional - v2.0.0

Biblioteca PHP para integração com a API Nacional de NFS-e.

---

## Arquitetura

Clean Architecture com 4 camadas:

| Camada | Responsabilidade |
|--------|-----------------|
| `Domain/` | Entidades, Value Objects, Enums, Contracts |
| `Application/` | Services, DTOs, Validators |
| `Infrastructure/` | HTTP, XML, Security, Repository |
| `Presentation/` | Facade, Factories |

---

## Componentes

### Value Objects (10)

| Classe | Descrição |
|--------|-----------|
| `Cnpj` | CNPJ com validação de dígito verificador |
| `Cpf` | CPF com validação de dígito verificador |
| `ChaveAcesso` | Chave de acesso NFS-e (50 caracteres) |
| `CodigoMunicipio` | Código IBGE de município (7 dígitos) |
| `Cep` | CEP brasileiro |
| `Email` | Email com validação |
| `Telefone` | Telefone brasileiro |
| `Money` | Valor monetário (armazenado em centavos) |
| `InscricaoMunicipal` | Inscrição municipal |
| `Nif` | Número de Identificação Fiscal |

### Entities (8)

`Dps`, `Nfse`, `Prestador`, `Tomador`, `Intermediario`, `Servico`, `Evento`, `Endereco`

### Services (3)

| Service | Operação |
|---------|----------|
| `EmitirDpsService` | Emissão de DPS |
| `ConsultarNfseService` | Consulta de NFS-e |
| `CancelarNfseService` | Cancelamento |

### Infrastructure

**HTTP:** `CurlHttpClient`, `ApiConnector`, `RequestBuilder`, `ResponseParser`

**XML:** `DpsXmlBuilder`, `EventoXmlBuilder`, `NfseXmlParser`, `DpsXmlParser`, `ErrorXmlParser`, `XsdValidator`

**Security:** `CertificateManager`, `CertificateValidator`, `XmlSigner`

**Repository:** `CstClassTribRepository` (3 implementações)

### Presentation

`NfseNacionalFacade`, `ServiceFactory`, `ConfigFactory`, `DpsFactory`, `NfseFactory`

---

## Métricas

| Métrica | Valor |
|---------|-------|
| PHPStan | Level 8 (0 erros) |
| Testes | 481 passando |
| Code Style | PSR-12 |
| PHP | 8.3+ |

---

## Comandos

```bash
composer test      # PHPUnit
composer cs        # CS-Fixer dry-run
composer cs:fix    # CS-Fixer apply
composer stan      # PHPStan nível 8
composer check     # Tudo junto
```

---

**Última atualização:** 15/05/2026
