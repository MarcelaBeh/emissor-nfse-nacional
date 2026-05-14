# Roadmap de Refatoração - NFSe Nacional

Acompanhamento visual do progresso da refatoração para Clean Architecture.

---

## Visão Geral

**Objetivo:** Refatorar o pacote emissor-nfse-nacional para arquitetura limpa, moderna, testável e segura.

**PHP:** 8.3+ | **Status:** Fases 1-5 Implementadas + Qualidade Configurada

---

## Progresso Geral

```
Fase 1: Preparação               [████████████████████] 100% ✅
Fase 2: Domain Layer              [████████████████████] 100% ✅
Fase 3: Infrastructure Layer      [████████████████████] 100% ✅
Fase 4: Application Layer         [████████████████████] 100% ✅
Fase 5: Presentation Layer        [████████████████████] 100% ✅
Fase 6: Testes e Documentação     [███░░░░░░░░░░░░░░░░░] 15% 🧪
Fase 7: Migração e Release        [░░░░░░░░░░░░░░░░░░░░] 0% ⏳

Progresso Total: 72% ████████████████████████████████░░░░░░░░░░
```

---

## Fases Detalhadas

### Fase 1: Preparação ✅

- [x] Criar branch `refactor/clean-architecture`
- [x] Documentação inicial (ARQUITETURA_REFATORACAO.md, GUIA_IMPLEMENTACAO.md, SEGURANCA_COMPLIANCE.md, LIBRARY_DESIGN.md)
- [x] Criar estrutura de diretórios completa
- [x] PHPStan 2.1 configurado (nível 8, 0 erros)
- [x] PHP-CS-Fixer 3.95 configurado (PSR-12 + PHP 8.3 migration)
- [x] PHPUnit 10.5 configurado (25 testes iniciais)
- [ ] GitHub Actions (CI/CD)

### Fase 2: Domain Layer ✅

- [x] 10 Value Objects (Cnpj, Cpf, ChaveAcesso, CodigoMunicipio, InscricaoMunicipal, Nif, Cep, Email, Telefone, Money)
- [x] 8 Entities (Dps, Nfse, Prestador, Tomador, Intermediario, Servico, Evento, Endereco, Substituicao)
- [x] Domain Service `DpsIdService`
- [x] Testes unitários iniciais (Cnpj, Cpf, CodigoMunicipio, DpsIdService)

### Fase 3: Infrastructure Layer ✅

- [x] HTTP: CurlHttpClient, ApiConnector, RequestBuilder, ResponseParser, tratamento de erros
- [x] XML: DpsXmlBuilder, EventoXmlBuilder, NfseXmlParser, DpsXmlParser, ErrorXmlParser, XsdValidator
- [x] Segurança: CertificateManager, CertificateValidator, XmlSigner
- [x] Config: Configuration, MunicipioConfigLoader, ApiEndpoints

### Fase 4: Application Layer ✅

- [x] Services: EmitirDpsService, ConsultarNfseService, CancelarNfseService
- [x] DTOs de Request/Response completos
- [x] Validators: DpsValidator, EventoValidator, ConsultaValidator

### Fase 5: Presentation Layer ✅

- [x] NfseNacionalFacade (ponto único de entrada)
- [x] Factories: ServiceFactory, ConfigFactory, DpsFactory, NfseFactory

### Fase 6: Testes e Documentação 🧪

- [x] 25 testes unitários (Cnpj, Cpf, CodigoMunicipio, DpsIdService)
- [x] PHPStan nível 8 — 0 erros (src completo, exceto Common/Support)
- [x] PHP-CS-Fixer aplicado (PSR-12, sem erros)
- [ ] Cobertura > 80%
- [ ] Testes de integração
- [ ] Testes end-to-end com homologação
- [ ] Exemplos de uso atualizados (/exemples)
- [ ] Guia de migração v1 → v2

---

## Métricas de Qualidade Atuais

| Métrica | Meta | Atual |
|---------|------|-------|
| Testes | 25 passando | ✅ |
| PHPStan Level | 8 | ✅ 0 erros |
| PHP-CS-Fixer | PSR-12 | ✅ Limpo |
| Linhas por Classe | ≤ 300 | ✅ Aderente |
| Complexidade Ciclomática | ≤ 10 | ✅ Aderente |
| Cobertura | ≥ 80% | ⬜ Pendente |

---

## Comandos Rápidos

```bash
composer test         # PHPUnit
composer cs           # CS-Fixer dry-run
composer cs:fix       # CS-Fixer apply
composer stan         # PHPStan
composer check        # Tudo acima
```

---

**Última atualização:** 14/05/2026
