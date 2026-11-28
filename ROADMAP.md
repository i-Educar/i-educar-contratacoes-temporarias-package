# ROADMAP — Contratações Temporárias (ACT)

> Pacote: `buriti/i-educar-contratacoes-temporarias-package` · TR **5.15** · Prioridade **P0** · Nota **D**  
> Alinhado a `packages/buriti/docs/laguna/DOC-EXECUTIVO-PE-77-2026-LAGUNA-IEDUCAR.md`

## Visão

Portal online de escolha de vagas ACT: classificados, preferência de vagas e comprovantes.

## Fases

### F0 · Regras

Modelar fluxo Laguna de chamada/classificados.

### F1 · Portal

Login candidato, lista de vagas, escolha e comprovante.

### F2 · SME

Painel de gestão da chamada e homologação.

### F3 · Integração RH

Gerar vínculo/lotação provisória no 5.1.

### F4 · PoC

Ensaio end-to-end com dados fictícios.

## Cobertura 100% dos requisitos (TR)

Fonte: [`REQUISITOS-TR.md`](./REQUISITOS-TR.md) — **33 itens**.

Cada lote deve sair da PoC como **atende** ou entrar no plano de 30 dias.

### Lote R1 — itens 5.15.1 … 5.15.18 (15)

- `5.15.1`, `5.15.2`, `5.15.3`, `5.15.4`, `5.15.5`, `5.15.6`, `5.15.7`, `5.15.8`, `5.15.9`, `5.15.10`, `5.15.12`, `5.15.15`, `5.15.16`, `5.15.17`, `5.15.18`

- [ ] Implementar / validar / evidenciar na matriz PoC

### Lote R2 — itens 5.15.19 … 5.15.33 (15)

- `5.15.19`, `5.15.20`, `5.15.21`, `5.15.22`, `5.15.23`, `5.15.24`, `5.15.25`, `5.15.26`, `5.15.27`, `5.15.28`, `5.15.29`, `5.15.30`, `5.15.31`, `5.15.32`, `5.15.33`

- [ ] Implementar / validar / evidenciar na matriz PoC

### Lote R3 — itens 5.15.34 … 5.15.36 (3)

- `5.15.34`, `5.15.35`, `5.15.36`

- [ ] Implementar / validar / evidenciar na matriz PoC

### Gate 100%

- [ ] Todos os lotes R* com status atende ou gap≤30dias
- [ ] Soma atende ≥ 85% na demonstração

## Conformidade Laravel / pacote i-Educar 2.12

Antes da fase de código:

1. Criar `composer.json` com `php >=8.3`, `illuminate/support`, provider em `extra.laravel`, `compatible: ["2.12"]`.
2. Scaffold: `src/Providers`, `Http/Controllers`, `Services`, `Support`, `database/migrations`, `routes/web.php`, views Blade.
3. Schema `contratacoes_temporarias` (ou estratégia documentada); FKs lógicas para núcleo (`ref_cod_aluno`, `ref_cod_escola`, etc.).
4. Menu/processos em migration própria; respeitar multi-tenant.
5. Não acoplar UI mobile; dados para lojas só via ``buriti/i-educar-apps-api-package``.
6. Seguir `packages/buriti/PADRAO-PACOTE-2.12.md`.

## Dependências

- Cadastro mestre de aluno/escola/turma no i-Educar (SGE).
- Matriz item a item OCR do Anexo I antes de cravar %.
- Alinhamento com `i-educar-apps-api-package` (APIs 5.13/5.14; UI/lojas em equipe externa) e exports (4.x) quando o módulo expõe dados ao cidadão/SME.

## Riscos

- Valor global do edital incompatível com greenfield total — reuso e MVP demonstrável são obrigatórios.
- PDF escaneado: numerações OCR podem exigir reconciliação manual.
- PoC só conta o que estiver operacional no dia.

## Definição de pronto da fase PoC

Checklist do `TODO.md` marcado para a fatia MVP + roteiro de demonstração ensaiado.
