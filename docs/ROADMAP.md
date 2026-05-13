# Roadmap de Refatoração - NFSe Nacional

Acompanhamento visual do progresso da refatoração para Clean Architecture.

---

## 🎯 Visão Geral

**Objetivo:** Refatorar o pacote emissor-nfse-nacional para arquitetura limpa, moderna, testável e segura.

**Duração Estimada:** 14 semanas (3,5 meses)

**Status Atual:** 📝 Planejamento Concluído

---

## 📊 Progresso Geral

```
Fase 1: Preparação               [⬜⬜⬜⬜⬜⬜⬜⬜⬜⬜] 0%
Fase 2: Domain Layer              [⬜⬜⬜⬜⬜⬜⬜⬜⬜⬜] 0%
Fase 3: Infrastructure Layer      [⬜⬜⬜⬜⬜⬜⬜⬜⬜⬜] 0%
Fase 4: Application Layer         [⬜⬜⬜⬜⬜⬜⬜⬜⬜⬜] 0%
Fase 5: Presentation Layer        [⬜⬜⬜⬜⬜⬜⬜⬜⬜⬜] 0%
Fase 6: Testes e Documentação     [⬜⬜⬜⬜⬜⬜⬜⬜⬜⬜] 0%
Fase 7: Migração e Release        [⬜⬜⬜⬜⬜⬜⬜⬜⬜⬜] 0%

Progresso Total: 0% ████░░░░░░░░░░░░░░░░░░░░░░░░░░░░
```

---

## 📅 Timeline Detalhado

### Fase 1: Preparação (Semanas 1-2) ⏳

**Objetivo:** Configurar ambiente e estrutura base

#### Semana 1
- [ ] Criar branch `refactor/clean-architecture`
- [ ] Configurar PHPStan nível 8
- [ ] Configurar PHP-CS-Fixer
- [ ] Configurar PHPUnit
- [ ] Configurar GitHub Actions (CI/CD)
- [ ] Documentação inicial

**Entregável:** Ambiente configurado e CI/CD funcionando

#### Semana 2
- [ ] Criar estrutura de diretórios completa
- [ ] Criar interfaces principais (contracts)
- [ ] Criar exceções base customizadas
- [ ] Implementar todos os Enums (PHP 8.1+)
- [ ] Testes das exceções e enums

**Entregável:** Estrutura base pronta

---

### Fase 2: Domain Layer (Semanas 3-4) 🏗️

**Objetivo:** Implementar camada de domínio pura

#### Semana 3: Value Objects
- [ ] Implementar `Cnpj` com validação completa
- [ ] Implementar `Cpf` com validação completa
- [ ] Implementar `ChaveAcesso`
- [ ] Implementar `CodigoMunicipio` com validação IBGE
- [ ] Implementar `InscricaoMunicipal`
- [ ] Implementar `Nif`
- [ ] Implementar `Cep`
- [ ] Implementar `Email`
- [ ] Implementar `Telefone`
- [ ] Implementar `Money` (valores monetários)
- [ ] Testes unitários (cobertura > 90%)

**Entregável:** 10 Value Objects testados

#### Semana 4: Entities
- [ ] Implementar `Dps` (entidade principal)
- [ ] Implementar `Nfse`
- [ ] Implementar `Prestador`
- [ ] Implementar `Tomador`
- [ ] Implementar `Intermediario`
- [ ] Implementar `Servico` com cálculos
- [ ] Implementar `Evento`
- [ ] Implementar `Endereco` (nacional/exterior)
- [ ] Implementar regras de negócio nas entities
- [ ] Testes unitários (cobertura > 90%)

**Entregável:** 8 Entities com regras de negócio testadas

---

### Fase 3: Infrastructure Layer (Semanas 5-7) 🔧

**Objetivo:** Implementações técnicas e integrações

#### Semana 5: HTTP e Comunicação
- [ ] Implementar `HttpClientInterface`
- [ ] Implementar `CurlHttpClient` com retry logic
- [ ] Implementar `ApiConnector` principal
- [ ] Implementar `RequestBuilder`
- [ ] Implementar `ResponseParser`
- [ ] Implementar tratamento de erros HTTP
- [ ] Testes de integração (mocks de API)

**Entregável:** Camada HTTP completa

#### Semana 6: XML e Validação
- [ ] Implementar `DpsXmlBuilder` completo
- [ ] Implementar `EventoXmlBuilder`
- [ ] Implementar builders parciais (Prestador, Tomador, Serviço)
- [ ] Implementar `NfseXmlParser`
- [ ] Implementar `DpsXmlParser`
- [ ] Implementar `ErrorXmlParser`
- [ ] Implementar `XsdValidator` estrito
- [ ] Implementar `SchemaLoader`
- [ ] Testes com XMLs reais e schemas XSD

**Entregável:** Construção e parsing de XML validado

#### Semana 7: Segurança e Config
- [ ] Implementar `CertificateManager` seguro
- [ ] Implementar `CertificateValidator`
- [ ] Implementar `XmlSigner` (assinatura digital)
- [ ] Implementar `SecureCertificateStorage`
- [ ] Implementar `Configuration` completa
- [ ] Implementar `MunicipioConfigLoader`
- [ ] Migrar `prefeituras.json`
- [ ] Implementar `ApiEndpoints`
- [ ] Testes de segurança

**Entregável:** Gestão segura de certificados e configuração

---

### Fase 4: Application Layer (Semanas 8-9) 📋

**Objetivo:** Casos de uso e orquestração

#### Semana 8: Services e DTOs
- [ ] Implementar `EmitirDpsService` completo
- [ ] Implementar `ConsultarNfseService`
- [ ] Implementar `ConsultarDpsService`
- [ ] Implementar `CancelarNfseService`
- [ ] Implementar `ConsultarEventosService`
- [ ] Implementar `ConsultarDanfseService`
- [ ] Criar todos os DTOs de Request
- [ ] Criar todos os DTOs de Response
- [ ] Testes de integração

**Entregável:** Services funcionais testados

#### Semana 9: Validators
- [ ] Implementar `DpsValidator` completo
- [ ] Implementar `EventoValidator`
- [ ] Implementar `ConsultaValidator`
- [ ] Implementar validações de negócio
- [ ] Integrar validators nos services
- [ ] Testes de validação

**Entregável:** Validações completas integradas

---

### Fase 5: Presentation Layer (Semana 10) 🎨

**Objetivo:** Interface pública da biblioteca

- [ ] Implementar `NfseNacionalFacade` principal
- [ ] Implementar `ServiceFactory`
- [ ] Implementar `NfseFactory`
- [ ] Implementar `DpsFactory`
- [ ] Implementar `ConfigFactory`
- [ ] Documentar API pública (DocBlocks)
- [ ] Criar exemplos de uso
- [ ] Testes end-to-end

**Entregável:** Facade funcional com exemplos

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
| Cobertura de Testes | ≥ 80% | ⬜ 0% |
| PHPStan Level | 8 | ⬜ Não configurado |
| Linhas por Classe | ≤ 300 | ⬜ A verificar |
| Complexidade Ciclomática | ≤ 10 | ⬜ A verificar |
| Duplicação de Código | ≤ 3% | ⬜ A verificar |
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
- [ ] Estrutura de diretórios criada
- [ ] CI/CD configurado
- [ ] Enums implementados

**Data Prevista:** Semana 2

### Marco 2: Domínio Completo
- [ ] Todos Value Objects implementados
- [ ] Todas Entities implementadas
- [ ] Testes de domínio > 90%

**Data Prevista:** Semana 4

### Marco 3: Infraestrutura Pronta
- [ ] HTTP client funcional
- [ ] XML builders/parsers funcionais
- [ ] Certificados seguros
- [ ] XSD validação estrita

**Data Prevista:** Semana 7

### Marco 4: Aplicação Funcional
- [ ] Todos os services implementados
- [ ] Validações completas
- [ ] Testes de integração passando

**Data Prevista:** Semana 9

### Marco 5: API Pública
- [ ] Facade implementada
- [ ] Exemplos funcionando
- [ ] Documentação da API

**Data Prevista:** Semana 10

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
