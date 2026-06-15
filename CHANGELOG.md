# Changelog

Todas as mudanças notáveis neste projeto são documentadas aqui.

Formato baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Versionamento Semântico](https://semver.org/lang/pt-BR/).

---

## [Unreleased]

---

## [v2.2.9] - 2026-06-15

### ⚠️ BREAKING CHANGES
- Removido `valorDeducoes` de `ServicoRequest`/`Servico`. Migração: use `valorDeducaoPadrao` (`<vDR>`).

### Fixed
- Dedução via `<vDedRed>` (`pDR`/`vDR`/`documentos`) agora reduz `baseCalculo` e `valorIss`. Antes só `valorDeducoes` calculava, inflando o ISS com `aliquotaIss != null`.

### Added
- Validação: dedução e soma de descontos não podem exceder `vServ`.
- Validação: campos obrigatórios, decimal `TSDec15V2` e `vDeducaoReducao ≤ vDedutivelRedutivel` em `docDedRed`.
- `CstClassTribRepository::findValidosParaNfse()`.

---

## [v2.2.8] - 2026-06-12

Falsos positivos do validador IBS/CBS que rejeitavam NFS-e válidas (aceitas pela SEFIN com `cStat=100`).

### Fixed
- Campos escalares da resposta voltam `0.00` por padrão da SEFIN: `vCalcReeRepRes` (E1531/E1533), `pRedutor` (E1522/E1523), `pRedAliq*` (E1541/E1546/E1551) e `vDif*` (E1566/E1570/E1580) passam a considerar "informado" só quando `> 0`
- `hasReducaoIBS()/hasReducaoCBS()`: `pRedIBS/pRedCBS = 0.0` (97 dos 156 códigos) era lido como "possui redução", exigindo `pRedAliq*` que a SEFIN não envia. Agora redução só existe com percentual `> 0`
- Destinatário no exterior (`indDest=1`) escapava à validação de endereço; agora valida o ramo `endExt` do `TCEndereco` (`cPais`/`cEndPost`/`xCidade`/`xEstProvReg`) e rejeita nacional+exterior simultâneos

### Changed
- `gDif` com `pDifUF=pDifMun=pDifCBS=0` agora é rejeitado (grupo vazio deve ser omitido)

---

## [v2.2.7] - 2026-06-11

Ajustes de leiaute confirmados pela **NT-007 SE/CGNFS-e v1.0**.

### Added
- `TribFederalRequest`/`TribFederal`: campos `vBCPisCofins`, `vPis` e `vCofins` (débito próprio de PIS/COFINS; retenções seguem em `vRetCSLL`)
- `ServicoRequest`: campos `vTotTribFed`, `vTotTribEst` e `vTotTribMun` (totais aproximados, Lei 12.741/2012)
- Validação do domínio oficial do `CST` de PIS/COFINS (`TSTipoCST`)

### Fixed
- `vTotTrib`: builder ignorava os totais informados e emitia `0.00`; agora usa os valores de `ServicoRequest`
- Endereço de tomador/intermediário: `xLgr`/`nro`/`xBairro` exigidos quando há endereço, evitando tags vazias rejeitadas pelo XSD

### Changed
- Endereço de tomador e intermediário agora opcional (`TCInfoPessoa/end` é `minOccurs=0`): `?Endereco` nas entidades e `?string` nos Requests. Retrocompatível; omite `<end>` quando ausente. Permite consumidor final (CPF) sem endereço

---

## [v2.2.6] - 2026-06-11

### Added
- `NfseResponse::erros` e `EventoResponse::erros`: lista completa dos erros da SEFIN (`[{codigo, descricao}, ...]`), já que ela pode retornar mais de um por vez; `mensagem` segue com o primeiro
- Logger **PSR-3** injetável via `NfseNacionalFacade::create()` e `ServiceFactory` (`Psr\Log\LoggerInterface` opcional, padrão `Psr\Log\NullLogger`) — aceita Monolog/Symfony/etc. Inclui `SanitizedLogger` (PSR-3) que mascara CPF/CNPJ/chave/e-mail antes de escrever

### Fixed
- `xItemPed`: limite corrigido de 255 para 60 caracteres (`TSNumeroEndereco`) e validação do teto de 99 itens (`maxOccurs`)
- Endereço de obra e de evento: `xs:choice` CEP|endExt passa a ser exigida e o endereço no exterior é validado

### Removed (breaking)
- `NfseResponse::codigoVerificacao`, `ServicoRequest::codigoCnae` e `TomadorRequest::nomeFantasia`: campos sem correspondência no XSD desta API

---

## [v2.2.5] - 2026-06-11

### Fixed
- `NfseXmlParser::parse()` exigia um envelope `<CompNFSe>` que a Sefin Nacional não usa em nenhuma resposta, retornando vazio para o XML real; com isso a v2.2.4 não populava `chaveAcesso`/`numero` e caía no fallback (chave do DPS, número nulo). Agora ancora em `<NFSe>` — a raiz real da emissão e da consulta (`SefinNacional_1.6.0`), conforme o XSD oficial (`TCNFSe`)

### Removed
- Dependência do envelope `<CompNFSe>` (leiaute ABRASF municipal), inexistente nas respostas desta API e ausente do XSD oficial

---

## [v2.2.4] - 2026-06-11

### Fixed
- Erro de cancelamento e demais eventos retornava sempre `"Erro ao cancelar NFSe"` genérico; agora extrai `Codigo`+`Descricao` de `erro[]` da SEFIN (`SefinNacional_1.6.0`), igual à emissão. O payload cru segue em `dados`
- Emissão bem-sucedida retornava a chave gerada localmente (do DPS) e não preenchia o número; agora `chaveAcesso` e `numero` vêm da NFS-e autorizada na resposta da SEFIN (atributo `Id` e `nNFSe`)

---

## [v2.2.3] - 2026-06-10

### Fixed
- Erro de emissão retornava sempre `"Erro ao emitir DPS"` genérico; agora extrai `Codigo`+`Descricao` de `erros[]` da SEFIN (`SefinNacional_1.6.0`). O payload cru segue em `dados`

---

## [v2.2.2] - 2026-06-10

### Fixed
- **BUG-06** — prestador-emitente: `xNome` e `<end>` do prestador são `minOccurs=0` no XSD e proibidos quando `tpEmit=1` (`E0121`/`E0128`); agora omitidos nesse caso
- `gReeRepRes/documentos/fornec`: grupo do fornecedor não era validado (choice, `xNome`, `cNaoNIF`)
- Tetos de cardinalidade XSD não validados: `documentos` e `docDedRed` (1000), `refNFSe` (99)
- `dest`: endereço sem CEP/número fabricava `<CEP>00000000</CEP>`/`<nro>` vazio; agora exige campos do `endNac`
- `dest/end/endExt` e `imovel/end/endExt`: endereço no exterior inalcançável/não validado
- `cCIB`: exigia 8 dígitos; `TSCodCIB` aceita 8 alfanuméricos
- `Nif` (VO): limite 20 → 40 caracteres (`TSNIF`)
- Eventos: `xMotivo` exigido nas rejeições quando `cMotivo=9` (AnexoIV E1944/E1949/E1954)
- Schema: pattern do `serie` mantido sem âncoras `^`/`$` (literais em XSD rejeitam toda série)

### Changed
- `Configuration::getVersion()` deriva a versão da tag git/Packagist (`composer-runtime-api`)

---

## [v2.2.1] - 2026-06-10

### Fixed
- Certificado coletado prematuramente pelo GC: o `ServiceFactory` (única referência ao `CertificateManager`) era descartado logo após inicializar os serviços, disparando o `__destruct` que apaga os PEMs temporários. Como o cURL os relê do disco a cada request, a emissão falhava com `CURL Error 58: could not load PEM client certificate`. A posse do `CertificateManager` foi movida para o `CurlHttpClient` — único consumidor dos PEMs —, eliminando a dependência da ordem de coleta de lixo

### Changed
- `CurlHttpClient` passa a receber `CertificateManagerInterface` (em vez de `certPath`/`privateKeyPath`) e materializa os PEMs sob demanda na primeira request, memoizando os caminhos. Nenhum segredo é escrito em disco se nenhuma request for feita

### Added
- Teste de regressão `CertificateLifetimeTest`: garante que os PEMs persistem enquanto o cliente HTTP vive e são removidos quando ele é destruído

---

## [v2.2.0] - 2026-06-09

### Fixed
- IBS/CBS: validação de regras de negócio (`cClassTrib`, `gDif`, `gTribRegular`) estava inativa em produção — `ServiceFactory` não injetava o `CstClassTribRepository` no `DpsValidator`
- IBS/CBS: diferimento parcial por esfera (`pDif=0`) gerava falso positivo (`E1570`/`E1580`) e bloqueava NFS-e válida
- IBS/CBS: resposta da SEFIN sem o grupo `IBSCBS` (quando a DPS o enviou) era ignorada silenciosamente; agora lança `ServiceException`
- IBS/CBS: `cNaoNIF` do destinatário podia violar o `xs:choice` (emitido fora do `if/elseif`)
- `verificarDpsExiste()` e `emitirPorDecisaoJudicial()` quebravam em runtime — operações `verificar_dps` e `decisao_judicial_nfse` ausentes da configuração
- `Tomador` por `cNaoNIF`/`CAEPF` era descartado no mapeamento DTO→entidade
- Endereço exterior de tomador/intermediário gerava `endExt` com campos vazios (validador exigia campos nacionais)
- `pRedBCBM` formatado com 3 casas decimais era rejeitado pelo XSD (`TSDec3V2` = 2 casas)
- Ranges decimais alinhados aos tipos XSD (`pAliq`, `pAliqPis/Cofins`, `pTotTrib*`)
- `tpAmb` como string `'2'` passava na validação e quebrava com `TypeError`
- Eventos: `xMotivo` obrigatório por tipo (alinhado ao `minOccurs` do XSD), código "Outros" por tipo (9 cancelamento, 99 substituição), e campos dos eventos de ofício/bloqueio (`xProcAdm`, `idEvManifRej`, `codEvento`, `idBloqOfic`)
- Segurança: `XsdValidator` sem `LIBXML_NOENT`; `CertificateManager` remove arquivos temporários no destrutor; `CurlHttpClient` sem `Content-Type` duplicado

### Changed
- **BREAKING** — `EventoRequest::tipoAmbiente` e `Evento::getTipoAmbiente()`: `string` → `int` (padronizado com `DpsRequest`)
- `NfseNacionalFacade::create()` aceita `Configuration|array`

### Removed
- `DpsIdService::generatePrefixedEvento()` — formato de `Id` antigo (com `nPedRegEvento`, removido pelo governo em 27/12/2025)

### Added
- Testes `ApiEndpointsTest` e `CClassTribTabelaIntegridadeTest`

---

## [v2.1.1] - 2026-05-22

### Added
- `SensitiveDataSanitizer` — sanitiza CPF, CNPJ, e-mail, chaves de acesso (50 dígitos) e campos sensíveis (`senha`, `token`, `password`, etc.) antes de logar; implementado em `src/Infrastructure/Security/`
- `LoggerInterface` — contrato mínimo (`info`, `warning`, `error`) para injeção nos services
- `NullLogger` — implementação padrão (sem output); evita dependência de pacotes externos
- `SanitizedLogger` — implementação opcional com sanitização automática via `SensitiveDataSanitizer`; aceita qualquer `Closure` como writer (arquivo, PSR-3 externo, etc.)
- `roave/security-advisories` adicionado em `require-dev` — bloqueia instalação de dependências com CVEs conhecidos

### Changed
- `EmitirDpsService`, `ConsultarNfseService`, `CancelarNfseService`: aceitam `LoggerInterface` como dependência opcional (padrão `NullLogger`) — erros de HTTP e validação são logados via interface
- `NfseNacionalFacade`: todos os métodos públicos documentados com `@throws` explícitos (`ValidationException`, `ServiceException`, `CertificateExpiredException`, `CertificateExpiringException`)
- `EmitirDpsService`: `\RuntimeException` em `compactarXml` e `descompactarXml` convertidos para `ServiceException` — exceções tipadas em toda a pilha
- `composer.json`: adicionados `type: library`, `support` (issues + source), keywords expandidos (sefin, ibs, cbs, nota-fiscal-servico, fiscal, brasil), `role: Maintainer` no autor principal

---

## [v2.1.0] - 2026-05-22

### Changed
- `NfseXmlParser`: parser expandido para extrair todos os campos da NFS-e conforme XSDs v1.01 — identificação (`ambGer`, `cStat`, `dhProc`, `xLocEmi`, `xLocPrestacao`, `xTribNac`, `xTribMun`, `xNBS`, `xOutInf`), emitente (`emit`), valores da NFS-e, DPS embutida completa (`TCInfDPS`: `verAplic`, `cMotivoEmisTI`, `chNFSeRej`, `cLocEmi`; `TCInfoPrestador`/`TCInfoPessoa`: `cNaoNIF`, `CAEPF`; `TCCServ`: `cIntContrib`; `TCComExterior`; `TCAtvEvento`: `xNome`, `dtIni`, `dtFim`; `TCInfoObra`: `cCIB`; `TCSubstituicao`: `cMotivo`, `xMotivo`; `TCVServPrest`: `vReceb`; `TCInfoDedRed` como bloco choice `pDR|vDR`; `TCTotTrib` como choice `vTotTrib|pTotTrib|indTotTrib`; `TCTribOutrosPisCofins`: `CST`, `vBCPisCofins`, `pAliqPis`, `pAliqCofins`; `TCTribMunicipal`: `pAliq`) e bloco IBS/CBS (`TCRTCIBSCBS`: `cLocalidadeIncid`, `xLocalidadeIncid`, `pRedutor`, `valores`, `totCIBS`)

### Fixed
- `DpsXmlBuilder`: `pAliq` agora formatado com `number_format(..., 2)` antes de serializar — `TSDec1V2` exige exatamente 2 casas decimais e o cast de float nativo não garante isso; campo omitido quando `null` (era sempre emitido)
- `DpsXmlBuilder`: ordem de `cPaisResult` e `tpImunidade` em `<tribMun>` corrigida para respeitar a sequência do XSD `TCTribMunicipal` (`cPaisResult` vem antes de `tpImunidade`)
- `DpsXmlBuilder`: `<BM>` agora emite `vRedBCBM` **ou** `pRedBCBM` (if/elseif) — eram dois `if` independentes, violando o `xs:choice` de `TCBeneficioMunicipal`
- `DpsXmlBuilder`: `buildExigSusp` removido fallback `?? ''` em `nProcesso` e `?? '1'` em `tpSusp` — string vazia viola o pattern `[0-9]{30}` de `TSNumProcExigSuspensa`; a entidade já garante os valores
- `DpsValidator`: validação de `inscricaoMunicipal` do intermediário agora verifica comprimento mínimo de 1 caractere além do máximo de 15 — consistente com a validação do prestador e do tomador (`TSInscMun`)

### Removed
- `NfseNacionalFacade::consultarDanfse()` e `consultarDanfseNfse()` — endpoints da API do governo serão suspensos em 01/07/2026 conforme NT 008; a geração do DANFSe passa a ser responsabilidade do software consumidor a partir do XML da NFS-e retornado por `consultarPorChave()`
- `ConsultarNfseService::consultarDanfse()` e `consultarDanfseNfse()`
- `ApiEndpoints::consultarDanfse()`, `consultarDanfseNfseCertificado()` e `consultarDanfseNfseDownload()`
- Operações `consultar_danfse`, `consultar_danfse_nfse_certificado` e `consultar_danfse_nfse_download` da `Configuration`
- URLs legadas `nfse_homologacao` e `nfse_producao` do portal emissor nacional — usadas exclusivamente pelo fluxo DANFSE removido

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

[Unreleased]: https://github.com/marcelabeh/emissor-nfse-nacional/compare/v2.2.9...HEAD
[v2.2.9]: https://github.com/marcelabeh/emissor-nfse-nacional/compare/v2.2.8...v2.2.9
[v2.2.8]: https://github.com/marcelabeh/emissor-nfse-nacional/compare/v2.2.7...v2.2.8
[v2.2.7]: https://github.com/marcelabeh/emissor-nfse-nacional/compare/v2.2.6...v2.2.7
[v2.2.6]: https://github.com/marcelabeh/emissor-nfse-nacional/compare/v2.2.5...v2.2.6
[v2.2.5]: https://github.com/marcelabeh/emissor-nfse-nacional/compare/v2.2.4...v2.2.5
[v2.2.4]: https://github.com/marcelabeh/emissor-nfse-nacional/compare/v2.2.3...v2.2.4
[v2.2.3]: https://github.com/marcelabeh/emissor-nfse-nacional/compare/v2.2.2...v2.2.3
[v2.2.2]: https://github.com/marcelabeh/emissor-nfse-nacional/compare/v2.2.1...v2.2.2
[v2.2.1]: https://github.com/marcelabeh/emissor-nfse-nacional/compare/v2.2.0...v2.2.1
[v2.2.0]: https://github.com/marcelabeh/emissor-nfse-nacional/compare/v2.1.1...v2.2.0
[v2.1.1]: https://github.com/marcelabeh/emissor-nfse-nacional/compare/v2.1.0...v2.1.1
[v2.1.0]: https://github.com/marcelabeh/emissor-nfse-nacional/compare/v2.0.4...v2.1.0
[v2.0.4]: https://github.com/marcelabeh/emissor-nfse-nacional/compare/v2.0.3...v2.0.4
[v2.0.3]: https://github.com/marcelabeh/emissor-nfse-nacional/compare/v2.0.2...v2.0.3
[v2.0.2]: https://github.com/marcelabeh/emissor-nfse-nacional/compare/v2.0.1...v2.0.2
[v2.0.1]: https://github.com/marcelabeh/emissor-nfse-nacional/compare/v2.0.0...v2.0.1
[v2.0.0]: https://github.com/marcelabeh/emissor-nfse-nacional/releases/tag/v2.0.0
