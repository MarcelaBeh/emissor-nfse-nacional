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
| Testes | 631 passando |
| Code Style | PSR-12 |
| PHP | 8.3+ |

---

## Atualizações v2.0.1 (16/05/2026)

### Validador DpsValidator
- Validação completa contra XSDs oficiais v1.01
- tpEmit obrigatório, cMotivoEmisTI opcional
- Prestador xNome opcional (minOccurs="0")
- Tomador/Intermediário: documento + xNome obrigatórios quando bloco existe
- comExterior: tpMoeda + vServMoeda obrigatórios quando existe
- atvEvento: dtIni + dtFim obrigatórios quando existe
- dedução/redução: choice só valida se bloco existir
- totTrib: bloco opcional

### DTOs
- ServicoRequest: campos opcionais agora são nullable conforme XSD
- valorDeducoes, descontoIncondicionado, descontoCondicionado: ?float
- aliquotaIss: ?float
- tribISSQN, tpRetISSQN: ?string (obrigatórios no validador)

### Entity Servico
- Aceita Money|null para descontoIncondicionado, descontoCondicionado, valorDeducoes
- aliquotaIss agora é ?float
- Getters atualizados para retornar tipos nullable

### Services e XML
- EmitirDpsService trata nulos ao criar Money
- DpsXmlBuilder trata nulos ao gerar XML

### Exemplos
- MakeDps.php atualizado com tribISSQN e tpRetISSQN
- MakeDpsComSubstituicao.php atualizado

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

---

## v2.0.3 (17/05/2026)

### Novos Endpoints da API Sefin Nacional

- **HEAD /dps/{id}** - Verificar se NFS-e foi gerada a partir do DPS
  - `$facade->verificarDpsExiste(string $id): bool`
- **POST /decisao-judicial/nfse** - Emitir NFS-e por decisão judicial
  - `$facade->emitirPorDecisaoJudicial(string $nfseXml): NfseResponse`

### Alterações

- HttpClientInterface: novo método `head()`
- CurlHttpClient: implementação de HEAD request
- ApiConnector: novo método `head()`
- ApiEndpoints: métodos `verificarDps()` e `decisaoJudicialNfse()`
- ConsultarNfseService: `verificarDpsExiste()`
- EmitirDpsService: `executarPorDecisaoJudicial()` com validação XSD NFSe v1.01
- prefeituras.json: operações `verificar_dps` e `decisao_judicial_nfse`

---

**Última atualização:** 17/05/2026
