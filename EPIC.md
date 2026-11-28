# EPIC — Contratações Temporárias (ACT)

| Campo | Valor |
| --- | --- |
| Pacote | `buriti/i-educar-contratacoes-temporarias-package` |
| TR Laguna | **5.15** |
| Prioridade | **P0** |
| Nota atual (executivo) | **D** |
| Fonte | `packages/buriti/docs/laguna/DOC-EXECUTIVO-PE-77-2026-LAGUNA-IEDUCAR.md` |
| Regra de ouro | Pregão Eletrônico 77/2026 · Laguna/SC · PoC ≥ 85% por módulo · gaps ≤ 30 dias |
| Base atual | Inexistente |

## Objetivo

Portal online de escolha de vagas ACT: classificados, preferência de vagas e comprovantes.

## Padrão técnico (i-Educar 2.12)

| Item | Valor |
| --- | --- |
| Alvo | i-Educar **2.12** · PHP **≥ 8.3** · Illuminate **^11|^12|^13** (Laravel 13 no app) |
| Composer | `buriti/i-educar-contratacoes-temporarias-package` |
| Namespace | `iEducar\Packages\BuritiContratacoesTemporarias\` → `src/` |
| Compat | `extra.ieducar.compatible: ["2.12"]` · GPL-2.0-or-later · `type: library` |
| Schema / dados | contratacoes_temporarias |
| Rotas | prefixo `contratacoes-temporarias`; SME com middleware `ieducar.*` + `auth`; API apps via Sanctum quando exposto |
| Tenancy | `PackageTenant::allows('buriti/i-educar-contratacoes-temporarias-package')` antes de migrations/menu |
| Plug-and-play | path em `packages/buriti/…`; manter em `ignore` até instalável |
| Referência | `packages/buriti/PADRAO-PACOTE-2.12.md` |

**Nota de arquitetura:** Portal público (web) + painel SME; integra lotação com pacote 5.1.

## Cobertura 100% do TR

Este EPIC cobre **todos** os requisitos do módulo(s) 5.15 listados em [`REQUISITOS-TR.md`](./REQUISITOS-TR.md) (33 itens OCR).

- PoC: ≥ **85%** demonstráveis e operacionais.
- Até **15%** restante: produção em ≤ **30 dias**, sem ônus.
- Nenhum item do `REQUISITOS-TR.md` pode ficar fora do `ROADMAP.md` / `TODO.md` (direto ou por grupo rastreável).

## Épicos filhos

1. Cadastro de editais/chamadas e vagas por escola/disciplina/turno.
2. Lista de classificados e autenticação do candidato.
3. Escolha de vaga com regras de prioridade e bloqueio de conflito.
4. Comprovantes, atas e integração com vida funcional (5.1).

## Critérios de aceite (PoC)

- [ ] Checklist [`REQUISITOS-TR.md`](./REQUISITOS-TR.md) com **100%** dos itens classificados (atende / gap / N/A)
- [ ] Itens **atende** demonstráveis na PoC (≥ 85% do módulo 5.15)
- [ ] Itens **gap** ≤ 15% com plano ≤ 30 dias em produção, sem ônus
- [ ] Nenhum requisito do TR omitido da matriz


## Fora de escopo deste EPIC

- Orçamento comercial da proposta Laguna.
- Substituição do core i-Educar fora do domínio do módulo.
