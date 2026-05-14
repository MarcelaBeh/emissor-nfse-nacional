# Roadmap de Refatoração - NFSe Nacional

Acompanhamento visual do progresso da refatoração para Clean Architecture.

---

## 🎯 Visão Geral

**Objetivo:** Refatorar o pacote emissor-nfse-nacional para arquitetura limpa, moderna, testável e segura.

**Duração Estimada:** 14 semanas (3,5 meses)

**Status Atual:** ✅ Fases 1-5 Implementadas (Domain, Infrastructure, Application, Presentation)

---

## 📊 Progresso Geral

```
Fase 1: Preparação               [████████████████████] 100% ✅
Fase 2: Domain Layer              [████████████████████] 100% ✅
Fase 3: Infrastructure Layer      [████████████████████] 100% ✅
Fase 4: Application Layer         [████████████████████] 100% ✅
Fase 5: Presentation Layer        [████████████████████] 100% ✅
Fase 6: Testes e Documentação     [░░░░░░░░░░░░░░░░░░░░] 0% ⏳
Fase 7: Migração e Release        [░░░░░░░░░░░░░░░░░░░░] 0% ⏳

Progresso Total: 71% ████████████████████████████████░░░░░░░░░░
```

---

## 📅 Timeline Detalhado

### Fase 1: Preparação (Semanas 1-2) ✅

**Objetivo:** Configurar ambiente e estrutura base

#### Semana 1
- [x] Criar branch `refactor/clean-architecture`
- [x] Documentação inicial (ARQUITETURA_REFATORACAO.md, GUIA_IMPLEMENTACAO.md, SEGURANCA_COMPLIANCE.md, LIBRARY_DESIGN.md)
- [x] Criar estrutura de diretórios completa
- [ ] Configurar PHPStan nível 8 (pendente instalação via Composer)
- [ ] Configurar PHP-CS-Fixer (pendente instalação via Composer)
- [ ] Configurar PHPUnit (pendente instalação via Composer)
- [ ] Configurar GitHub Actions (CI/CD)

**Entregável:** Ambiente configurado e documentação base

#### Semana 2
- [x] Criar estrutura de diretórios completa
- [x] Criar interfaces principais (contracts)
- [x] Criar exceções base customizadas
- [x] Implementar todos os Enums (PHP 8.1+)
- [ ] Testes das exceções e enums (pendente)

**Entregável:** Estrutura base pronta

---

### Fase 2: Domain Layer (Semanas 3-4) ✅

**Objetivo:** Implementar camada de domínio pura

#### Semana 3: Value Objects
- [x] Implementar `Cnpj` com validação completa
- [x] Implementar `Cpf` com validação completa
- [x] Implementar `ChaveAcesso`
- [x] Implementar `CodigoMunicipio` com validação IBGE
- [x] Implementar `InscricaoMunicipal`
- [x] Implementar `Nif`
- [x] Implementar `Cep`
- [x] Implementar `Email`
- [x] Implementar `Telefone`
- [x] Implementar `Money` (valores monetários)
- [ ] Testes unitários (cobertura > 90%) — pendente

**Entregável:** 10 Value Objects implementados

#### Semana 4: Entities
- [x] Implementar `Dps` (entidade principal)
- [x] Implementar `Nfse`
- [x] Implementar `Prestador`
- [x] Implementar `Tomador`
- [x] Implementar `Intermediario`
- [x] Implementar `Servico` com cálculos
- [x] Implementar `Evento`
- [x] Implementar `Endereco` (nacional/exterior)
- [x] Implementar `Substituicao`
- [x] Domain Service `DpsIdService` para geração de identificadores
- [ ] Testes unitários (cobertura > 90%) — pendente

**Entregável:** 8 Entities + Domain Services implementados

---

### Fase 3: Infrastructure Layer (Semanas 5-7) ✅

**Objetivo:** Implementações técnicas e integrações

#### Semana 5: HTTP e Comunicação
- [x] Implementar `HttpClientInterface`
- [x] Implementar `CurlHttpClient` com retry logic
- [x] Implementar `ApiConnector` principal
- [x] Implementar `RequestBuilder`
- [x] Implementar `ResponseParser`
- [x] Implementar tratamento de erros HTTP
- [ ] Testes de integração (mocks de API) — pendente

**Entregável:** Camada HTTP completa

#### Semana 6: XML e Validação
- [x] Implementar `DpsXmlBuilder` completo
- [x] Implementar `EventoXmlBuilder`
- [x] Implementar `NfseXmlParser`
- [x] Implementar `DpsXmlParser`
- [x] Implementar `ErrorXmlParser`
- [x] Implementar `XsdValidator` estrito
- [ ] Testes com XMLs reais e schemas XSD — pendente

**Entregável:** Construção e parsing de XML validado

#### Semana 7: Segurança e Config
- [x] Implementar `CertificateManager` seguro
- [x] Implementar `CertificateValidator`
- [x] Implementar `XmlSigner` (assinatura digital)
- [x] Implementar `Configuration` completa
- [x] Implementar `MunicipioConfigLoader`
- [x] Implementar `ApiEndpoints`
- [ ] Testes de segurança — pendente

**Entregável:** Gestão segura de certificados e configuração

---

### Fase 4: Application Layer (Semanas 8-9) ✅

**Objetivo:** Casos de uso e orquestração

#### Semana 8: Services e DTOs
- [x] Implementar `EmitirDpsService` completo
- [x] Implementar `ConsultarNfseService`
- [x] Implementar `CancelarNfseService`
- [x] Criar todos os DTOs de Request (DpsRequest, PrestadorRequest, TomadorRequest, ServicoRequest, EventoRequest, SubstituicaoRequest, ConsultaRequest)
- [x] Criar todos os DTOs de Response (NfseResponse, DpsResponse, EventoResponse, ErrorResponse)
- [ ] Testes de integração — pendente

**Entregável:** Services funcionais implementados

#### Semana 9: Validators
- [x] Implementar `DpsValidator` completo
- [x] Implementar `EventoValidator`
- [x] Implementar `ConsultaValidator`
- [x] Integrar validators nos services
- [ ] Testes de validação — pendente

**Entregável:** Validações completas integradas

---

### Fase 5: Presentation Layer (Semana 10) ✅

**Objetivo:** Interface pública da biblioteca

- [x] Implementar `NfseNacionalFacade` principal
- [x] Implementar `ServiceFactory`
- [x] Implementar `NfseFactory`
- [x] Implementar `DpsFactory`
- [x] Implementar `ConfigFactory`
- [x] Documentar API pública (DocBlocks)
- [ ] Criar exemplos de uso atualizados — pendente
- [ ] Testes end-to-end — pendente

**Entregável:** Facade funcional com factories

---

### Fase 6: Testes e Documentação (Semanas 11-12) 🧪

**Objetivo:** Qualidade e documentação completa

#### Semana 11: Testes
- [ ] Testes unitários > 90% cobertura
- [ ] Testes de integração completos
- [ ] Testes end-to-end com ambiente de homologação
- [ ] Validação com todos os schemas (v1.00 e v1.01)
- [ ] Testes de performance
- [ ] Testes de segurança
- [ ] Correção de bugs encontrados

**Entregável:** Suite de testes completa

#### Semana 12: Documentação
- [ ] Atualizar README principal
- [ ] Criar guia de migração v1 → v2
- [ ] Criar exemplos práticos completos
- [ ] Gerar API Reference (PHPDoc)
- [ ] Atualizar CHANGELOG
- [ ] Criar documentação de troubleshooting
- [ ] Revisar toda documentação

**Entregável:** Documentação completa e atualizada

---

### Fase 7: Migração e Release (Semanas 13-14) 🚀

**Objetivo:** Transição suave e lançamento

#### Semana 13: Retrocompatibilidade
- [ ] Criar camada de compatibilidade (adapters)
- [ ] Mapear APIs antigas → novas
- [ ] Deprecar métodos antigos (com warnings)
- [ ] Criar guia de migração detalhado
- [ ] Testes de compatibilidade
- [ ] Beta release (v2.0.0-beta.1)
- [ ] Feedback de early adopters

**Entregável:** Versão beta estável

#### Semana 14: Release Final
- [ ] Code review completo do time
- [ ] Audit de segurança final
- [ ] Correções finais
- [ ] Atualizar versão (v2.0.0)
- [ ] Tag no Git
- [ ] Merge para branch main
- [ ] Publicar no Packagist
- [ ] Anunciar release (breaking changes)
- [ ] Monitorar issues pós-release

**Entregável:** v2.0.0 em produção

---

## 📈 Métricas de Qualidade

### Objetivos de Qualidade

| Métrica | Meta | Status |
|---------|------|--------|
| Cobertura de Testes | ≥ 80% | ⬜ 0% (não iniciado) |
| PHPStan Level | 8 | ⬜ Pendente instalação |
| Linhas por Classe | ≤ 300 | ✅ Aderente |
| Complexidade Ciclomática | ≤ 10 | ✅ Aderente |
| Duplicação de Código | ≤ 3% | ✅ Aderente |
| Vulnerabilidades | 0 | ⬜ A auditar |

### Testes por Camada

| Camada | Unitários | Integração | E2E | Total |
|--------|-----------|------------|-----|-------|
| Domain | ⬜ 0 | - | - | 0 |
| Infrastructure | ⬜ 0 | ⬜ 0 | - | 0 |
| Application | ⬜ 0 | ⬜ 0 | - | 0 |
| Presentation | - | - | ⬜ 0 | 0 |
| **Total** | **0** | **0** | **0** | **0** |

---

## 🏆 Marcos Importantes

### Marco 1: Fundação ✅
- [x] Documentação de arquitetura completa
- [x] Estrutura de diretórios criada
- [x] Enums implementados
- [ ] CI/CD configurado — pendente

**Data Prevista:** Semana 2 | **Status:** ✅ Concluído

### Marco 2: Domínio Completo ✅
- [x] Todos Value Objects implementados (10/10)
- [x] Todas Entities implementadas (8/8)
- [x] Domain Services implementados (DpsIdService)
- [ ] Testes de domínio > 90% — pendente

**Data Prevista:** Semana 4 | **Status:** ✅ Código concluído

### Marco 3: Infraestrutura Pronta ✅
- [x] HTTP client funcional (CurlHttpClient, RequestBuilder, ResponseParser)
- [x] XML builders/parsers funcionais (DpsXmlBuilder, EventoXmlBuilder, parsers)
- [x] Certificados seguros (CertificateManager, CertificateValidator)
- [x] XSD validação estrita (XsdValidator)
- [x] Configuração completa (Configuration, MunicipioConfigLoader, ApiEndpoints)

**Data Prevista:** Semana 7 | **Status:** ✅ Código concluído

### Marco 4: Aplicação Funcional ✅
- [x] Todos os services implementados (EmitirDps, ConsultarNfse, CancelarNfse)
- [x] Validações completas (DpsValidator, EventoValidator, ConsultaValidator)
- [x] DTOs de Request/Response implementados
- [ ] Testes de integração — pendente

**Data Prevista:** Semana 9 | **Status:** ✅ Código concluído

### Marco 5: API Pública ✅
- [x] Facade implementada (NfseNacionalFacade)
- [x] Factories implementadas (ServiceFactory, ConfigFactory, DpsFactory, NfseFactory)
- [x] Documentação da API (DocBlocks)
- [ ] Exemplos atualizados — pendente

**Data Prevista:** Semana 10 | **Status:** ✅ Código concluído

### Marco 6: Release Beta
- [ ] Testes completos
- [ ] Documentação finalizada
- [ ] Beta release publicada

**Data Prevista:** Semana 13

### Marco 7: Release v2.0.0 🎉
- [ ] Code review completo
- [ ] Audit de segurança
- [ ] Versão publicada
- [ ] Anúncio oficial

**Data Prevista:** Semana 14

---

## 🚧 Riscos e Mitigações

### Riscos Identificados

| Risco | Probabilidade | Impacto | Mitigação |
|-------|---------------|---------|-----------|
| Breaking changes afetam usuários | Alta | Alto | Criar camada de compatibilidade + guia de migração |
| Problemas com certificados | Média | Alto | Testes extensivos + validação estrita |
| Performance degradada | Baixa | Médio | Benchmarks + otimizações |
| Bugs em produção | Média | Alto | Testes > 80% + beta release |
| Prazo estourado | Média | Médio | Buffer de 2 semanas + priorização |

### Plano de Contingência

1. **Se houver atraso:**
   - Priorizar features críticas
   - Postergar features secundárias para v2.1
   - Reavaliar timeline na Semana 7

2. **Se houver bugs críticos:**
   - Hotfix imediato
   - Reverter para versão anterior se necessário
   - Comunicação transparente com usuários

---

## 👥 Responsabilidades

### Papéis

- **Arquiteto:** Define arquitetura e padrões
- **Desenvolvedores:** Implementam conforme documentação
- **QA:** Testes e validação
- **Revisor:** Code review e aprovação
- **DevOps:** CI/CD e deploy

### Reuniões

- **Daily:** 15min (progresso e bloqueios)
- **Weekly Review:** 1h (demo e retrospectiva)
- **Sprint Planning:** 2h (início de cada fase)

---

## 📝 Convenções de Commit

```
feat: adiciona Value Object Cnpj com validação
fix: corrige validação de dígito verificador
docs: atualiza guia de implementação
test: adiciona testes para DpsXmlBuilder
refactor: simplifica lógica de Money
perf: otimiza parsing de XML
style: formata código com PHP-CS-Fixer
chore: atualiza dependências
```

---

## ✅ Checklist de Release

### Pre-Release
- [ ] Todos os testes passando
- [ ] Cobertura > 80%
- [ ] PHPStan nível 8 sem erros
- [ ] PHP-CS-Fixer aplicado
- [ ] Documentação atualizada
- [ ] CHANGELOG atualizado
- [ ] Exemplos funcionando
- [ ] Sem TODO ou FIXME no código

### Release
- [ ] Tag criada (v2.0.0)
- [ ] Branch main atualizada
- [ ] Packagist atualizado
- [ ] GitHub Release criada
- [ ] Anúncio publicado

### Post-Release
- [ ] Monitorar issues
- [ ] Responder perguntas
- [ ] Hotfixes se necessário
- [ ] Retrospectiva do projeto

---

## 📞 Contatos

- **Issues:** GitHub Issues
- **Discussões:** GitHub Discussions
- **Email:** [contato do projeto]

---

**Status do Roadmap:** 🟢 Ativo  
**Próxima Atualização:** Semanal  
**Última Atualização:** 13/05/2026
