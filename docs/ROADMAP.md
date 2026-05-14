# Roadmap - NFSe Nacional

Acompanhamento do desenvolvimento do emissor-nfse-nacional.

---

## Visão Geral

**Objetivo:** Biblioteca PHP para integração com NFSe Nacional - Clean Architecture, testável e segura.

**PHP:** 8.3+ | **Status:** Fases 1-5 Implementadas + Qualidade Configurada

---

## Progresso Geral

```
Fase 1: Preparação               [████████████████████] 100% ✅
Fase 2: Domain Layer              [████████████████████] 100% ✅
Fase 3: Infrastructure Layer      [████████████████████] 100% ✅
Fase 4: Application Layer         [████████████████████] 100% ✅
Fase 5: Presentation Layer        [████████████████████] 100% ✅
Fase 6: Testes e Documentação     [███████████████░░░░░] 70% 🧪
Fase 7: Migração e Release        [░░░░░░░░░░░░░░░░░░░░] 0% ⏳

Progresso Total: 86%
```

---

## Fases Detalhadas

### Fase 1: Preparação ✅

- [x] Estrutura de diretórios Clean Architecture
- [x] PHPStan nível 8 configurado (0 erros)
- [x] PHP-CS-Fixer configurado (PSR-12)
- [x] PHPUnit configurado (481 testes)
- [x] GitHub Actions CI/CD

### Fase 2: Domain Layer ✅

- [x] 10 Value Objects (Cnpj, Cpf, ChaveAcesso, CodigoMunicipio, InscricaoMunicipal, Nif, Cep, Email, Telefone, Money)
- [x] 8 Entities (Dps, Nfse, Prestador, Tomador, Intermediario, Servico, Evento, Endereco, Substituicao)
- [x] Domain Service `DpsIdService`
- [x] Enums para tipos e estados

### Fase 3: Infrastructure Layer ✅

- [x] HTTP: CurlHttpClient, ApiConnector, RequestBuilder, ResponseParser
- [x] XML: DpsXmlBuilder, EventoXmlBuilder, EventoEnvelopeBuilder, NfseXmlParser, DpsXmlParser, ErrorXmlParser, XsdValidator
- [x] Segurança: CertificateManager, CertificateValidator, XmlSigner
- [x] Config: Configuration, ApiEndpoints
- [x] Repository: CstClassTribRepository (3 implementações)

### Fase 4: Application Layer ✅

- [x] Services: EmitirDpsService, ConsultarNfseService, CancelarNfseService
- [x] DTOs de Request/Response completos
- [x] Validators: DpsValidator, EventoValidator, ConsultaValidator, IbscbsResponseValidator

### Fase 5: Presentation Layer ✅

- [x] NfseNacionalFacade (API unificada)
- [x] Factories: ServiceFactory, ConfigFactory, DpsFactory, NfseFactory

### Fase 6: Testes e Documentação 🧪

- [x] 481 testes unitários (100% passando)
- [x] PHPStan nível 8 — 0 erros
- [x] PHP-CS-Fixer aplicado (PSR-12)
- [x] Documentação: README.md, ARQUITETURA.md, GUIA_IMPLEMENTACAO.md, SEGURANCA_COMPLIANCE.md
- [x] Exemplos em `/examples` (7 arquivos)
- [ ] Cobertura > 80%
- [ ] Testes de integração
- [ ] Testes end-to-end com homologação
- [ ] Guia de migração v1 → v2

### Fase 7: Migração e Release ⏳

- [ ] API de compatibilidade (v1 legacy)
- [ ] Guia de migração detalhado
- [ ] Release v2.0.0
- [ ] Publicação no Packagist

---

## Métricas de Qualidade

| Métrica | Meta | Atual |
|---------|------|-------|
| Testes | 481 passando | ✅ |
| PHPStan Level | 8 | ✅ 0 erros |
| PHP-CS-Fixer | PSR-12 | ✅ Limpo |
| Linhas por Classe | ≤ 300 | ✅ Aderente |
| Complexidade Ciclomática | ≤ 10 | ✅ Aderente |
| Cobertura | ≥ 80% | ⬜ Pendente |

---

## Comandos Rápidos

```bash
composer test      # PHPUnit (481 testes)
composer cs        # CS-Fixer dry-run
composer cs:fix    # CS-Fixer apply
composer stan      # PHPStan nível 8
composer check     # Tudo junto
```

---

**Última atualização:** 14/05/2026
