# Changelog

Todas as mudanças notáveis neste projeto são documentadas aqui.

Formato baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Versionamento Semântico](https://semver.org/lang/pt-BR/).

---

## [Unreleased]

---

## [v2.0.4] - 2026-05-22

### Security
- SHA-1 substituído por SHA-256 na assinatura XML (`XmlSigner`)
- Proteção XXE em todos os parsers XML (`DpsXmlParser`, `ErrorXmlParser`, `NfseXmlParser`) via `LIBXML_NONET | LIBXML_NOENT`
- Temporários de certificado reescritos com `tempnam()` eliminando vulnerabilidade TOCTOU; conteúdo zerado antes de `unlink`
- Expiração de certificado agora lança `CertificateExpiringException` em vez de `trigger_error`

### Added
- `CertificateExpiringException` — exceção dedicada para certificados próximos do vencimento
- `ApiConnectorInterface` — contrato para o conector HTTP
- `XsdValidatorInterface` — contrato para o validador XSD
- `Servico`: campos `percentualDeducao` (`pDR`) e `valorDeducaoPadrao` (`vDR`) conforme XSD `TCInfoDedRed xs:choice`

### Changed
- `Servico`: parâmetros `tribISSQN`/`tpRetISSQN` migrados de `string` para enums tipados (`TributacaoIssqn`, `TipoRetencaoIssqn`)
- `Servico`: validação `xs:choice` para `locPrest` — obrigatório informar `cLocPrestacao` ou `cPaisPrestacao`
- `Telefone`: validação ampliada para 6–20 dígitos conforme XSD `TSTelefone` (suporte a números internacionais)
- `EmitirDpsService`, `CancelarNfseService`, `ConsultarNfseService`: type hints migrados para interfaces (DIP)
- `XsdValidator`: suporte a múltiplas versões de schema (v1.00 e v1.01) via enum `VersaoSchema`
- `DpsXmlBuilder`: emite `<pDR>` e `<vDR>` no bloco `<vDedRed>` quando informados
- CI: matrix agora testa PHP 8.3 e 8.4; PHP-CS-Fixer bloqueia o pipeline em vez de continuar com erro
- Release workflow: extensions PHP declaradas, extrai release notes apenas da seção da tag atual

### Fixed
- `EventoValidator`: data do evento agora é validada como ISO 8601 antes de instanciar `DateTimeImmutable`, evitando `DateMalformedStringException` não capturada
- `DpsXmlBuilder`: `tribISSQN`/`tpRetISSQN` agora usam `.value` do enum corretamente

### Removed
- Dependência `tecnickcom/tcpdf` — nunca utilizada
- Dependência dev `symfony/var-dumper` — nunca utilizada
- `Helpers.php` e entrada `autoload.files` — função `now()` nunca chamada
- `DpsInterface` — interface órfã sem nenhuma implementação

---

## [v2.0.3] - 2026-05-16

### Added
- Endpoint `HEAD /dps/{id}` — verificar se NFS-e foi gerada a partir do DPS (`$facade->verificarDpsExiste()`)
- Endpoint `POST /decisao-judicial/nfse` — emitir NFS-e por decisão judicial (`$facade->emitirPorDecisaoJudicial()`)
- `HttpClientInterface`: método `head()`
- `CurlHttpClient`: implementação de HEAD request
- `ApiConnector`: método `head()`
- `ApiEndpoints`: métodos `verificarDps()` e `decisaoJudicialNfse()`
- `prefeituras.json`: operações `verificar_dps` e `decisao_judicial_nfse`

### Changed
- `ConsultarNfseService`: novo método `verificarDpsExiste()`
- `EmitirDpsService`: novo método `executarPorDecisaoJudicial()` com validação XSD NFSe v1.01

### Fixed
- Release workflow: dependências dev mantidas para execução de testes no CI

---

## [v2.0.2] - 2026-05-16

### Added
- GitHub Actions: workflow de release automático ao criar tag `v*`
- Guia de integração com ERPs (`docs/`)

### Fixed
- Release workflow: atualização para versão mais recente da action

---

## [v2.0.1] - 2026-05-15

### Added
- `DpsValidator`: validação completa de todos os campos contra XSDs oficiais v1.01
  - `tpEmit` obrigatório
  - `comExterior`: `tpMoeda` + `vServMoeda` obrigatórios quando bloco presente
  - `atvEvento`: `dtIni` + `dtFim` obrigatórios quando bloco presente
  - Tomador/Intermediário: documento + `xNome` obrigatórios quando bloco presente

### Changed
- `ServicoRequest`: campos `valorDeducoes`, `descontoIncondicionado`, `descontoCondicionado`, `aliquotaIss` agora `?float` conforme XSD
- `Servico`: aceita `Money|null` para descontos e deduções
- `EmitirDpsService` e `DpsXmlBuilder`: tratamento de nulos nos campos opcionais

---

## [v2.0.0] - 2026-05-15

### Added
- Lançamento inicial da biblioteca
- Clean Architecture com 4 camadas: Domain, Application, Infrastructure, Presentation
- 10 Value Objects: `Cnpj`, `Cpf`, `ChaveAcesso`, `CodigoMunicipio`, `Cep`, `Email`, `Telefone`, `Money`, `InscricaoMunicipal`, `Nif`
- Entidades de domínio: `Dps`, `Nfse`, `Prestador`, `Tomador`, `Intermediario`, `Servico`, `Evento`, `Endereco`
- Services: `EmitirDpsService`, `ConsultarNfseService`, `CancelarNfseService`
- Builders XML: `DpsXmlBuilder`, `EventoXmlBuilder`, `EventoEnvelopeBuilder`
- Validador XSD contra schemas oficiais v1.00 e v1.01
- Enums tipados: `TipoAmbiente`, `TributacaoIssqn`, `TipoRetencaoIssqn`, `RegimeTributario`, `VersaoSchema` e outros
- Facade `NfseNacionalFacade` para uso simplificado
- PHPStan level 8 configurado (0 erros)

[Unreleased]: https://github.com/marcelabeh/emissor-nfse-nacional/compare/v2.0.4...HEAD
[v2.0.4]: https://github.com/marcelabeh/emissor-nfse-nacional/compare/v2.0.3...v2.0.4
[v2.0.3]: https://github.com/marcelabeh/emissor-nfse-nacional/compare/v2.0.2...v2.0.3
[v2.0.2]: https://github.com/marcelabeh/emissor-nfse-nacional/compare/v2.0.1...v2.0.2
[v2.0.1]: https://github.com/marcelabeh/emissor-nfse-nacional/compare/v2.0.0...v2.0.1
[v2.0.0]: https://github.com/marcelabeh/emissor-nfse-nacional/releases/tag/v2.0.0
