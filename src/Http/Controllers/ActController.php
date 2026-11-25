<?php

namespace iEducar\Packages\BuritiContratacoesTemporarias\Http\Controllers;

use iEducar\Packages\BuritiContratacoesTemporarias\Services\AdmissaoAct;
use iEducar\Packages\BuritiContratacoesTemporarias\Services\AnexoAct;
use iEducar\Packages\BuritiContratacoesTemporarias\Support\Acesso;
use iEducar\Packages\BuritiContratacoesTemporarias\Support\Processos;
use iEducar\Packages\BuritiContratacoesTemporarias\Services\ChamadaAct;
use iEducar\Packages\BuritiContratacoesTemporarias\Services\ComissaoAvaliacao;
use iEducar\Packages\BuritiContratacoesTemporarias\Services\DocumentoRegra;
use iEducar\Packages\BuritiContratacoesTemporarias\Services\FluxoProcesso;
use iEducar\Packages\BuritiContratacoesTemporarias\Services\PocRegistro;
use iEducar\Packages\BuritiContratacoesTemporarias\Services\ProtocoloInscricao;
use iEducar\Packages\BuritiContratacoesTemporarias\Services\RecursoProcesso;
use iEducar\Packages\BuritiContratacoesTemporarias\Services\ResultadoFinal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ActController extends Controller
{
    public function index(): View
    {
        $this->garantirSeed();
        $processos = $this->processosLista();
        $inscricoesLista = $this->inscricoesLista();
        $inscricoes = count($inscricoesLista);
        $recursosAbertos = 0;
        $recursosLista = [];
        foreach (PocRegistro::listar('recurso', 200) as $r) {
            if (($r['payload']['status'] ?? '') === RecursoProcesso::ABERTO) {
                $recursosAbertos++;
                $recursosLista[] = $r;
            }
        }
        $vagas = $this->vagas();
        $avaliacoes = PocRegistro::contar('avaliacao');
        $porStatus = [];
        foreach ($processos as $p) {
            $st = (string) ($p['status'] ?? 'rascunho');
            $porStatus[$st] = ($porStatus[$st] ?? 0) + 1;
        }

        $analise = [];
        foreach (array_slice($processos, 0, 6) as $p) {
            $analise[] = [
                'titulo' => $p['titulo'],
                'meta' => ($p['numero'] ?? '').' · insc. '.($p['inscricao_inicio'] ?? '—').' a '.($p['inscricao_fim'] ?? '—'),
                'badge' => FluxoProcesso::rotulo($p['status']),
                'badge_tom' => FluxoProcesso::tom($p['status']),
                'url' => '/contratacoes-temporarias/processos/'.$p['id'],
            ];
        }
        foreach (array_slice($recursosLista, 0, 5) as $r) {
            $analise[] = [
                'titulo' => 'Recurso #'.$r['id'],
                'meta' => $r['titulo'] ?? ($r['payload']['candidato'] ?? 'Candidato'),
                'badge' => 'Aberto',
                'badge_tom' => 'warn',
                'url' => '/contratacoes-temporarias/recursos',
            ];
        }
        if ($analise === []) {
            $analise[] = [
                'titulo' => 'Sem editais',
                'meta' => 'Cadastre um processo seletivo para iniciar',
                'badge' => 'Vazio',
                'badge_tom' => 'warn',
                'url' => '/contratacoes-temporarias/processos',
            ];
        }

        return view('act::painel', [
            'kpis' => [
                ['rotulo' => 'Processos', 'valor' => count($processos), 'icone' => 'fa-gavel', 'tom' => 'var(--cor)'],
                ['rotulo' => 'Inscrições', 'valor' => $inscricoes, 'icone' => 'fa-user-plus', 'tom' => 'var(--destaque)'],
                ['rotulo' => 'Recursos abertos', 'valor' => $recursosAbertos, 'icone' => 'fa-balance-scale', 'tom' => $recursosAbertos ? 'var(--ambar)' : 'var(--ok)'],
                ['rotulo' => 'Vagas', 'valor' => count($vagas), 'icone' => 'fa-building', 'tom' => 'var(--ok)'],
                ['rotulo' => 'Avaliações', 'valor' => $avaliacoes, 'icone' => 'fa-calculator', 'tom' => '#0e7490'],
                ['rotulo' => 'Em inscrição', 'valor' => $porStatus['inscricao'] ?? $porStatus['inscricoes'] ?? 0, 'icone' => 'fa-pencil', 'tom' => 'var(--ambar)'],
            ],
            'destaques' => [
                [
                    'titulo' => count($processos) ? 'Processo seletivo ativo' : 'Sem edital',
                    'meta' => 'Documentos · comissão · recurso · protocolo · resultado',
                    'icone' => 'fa-gavel',
                    'tom' => 'var(--cor)',
                    'corpo' => $recursosAbertos
                        ? "{$recursosAbertos} recurso(s) aguardando resposta."
                        : $inscricoes.' inscrição(ões) registradas na chamada.',
                ],
            ],
            'analise' => $analise,
            'analiseTitulo' => 'Editais e recursos',
            'resumo' => 'Contratações temporárias (ACT) · edital, inscrição com protocolo, avaliação, recurso e resultado.',
            'atalhos' => [
                ['url' => '/contratacoes-temporarias/processos', 'icone' => 'fa-gavel', 'rotulo' => '1. Edital', 'meta' => 'Fases e comissão'],
                ['url' => '/contratacoes-temporarias/inscricoes', 'icone' => 'fa-user-plus', 'rotulo' => '2. Inscrição', 'meta' => 'Protocolo + anexos'],
                ['url' => '/contratacoes-temporarias/avaliacao', 'icone' => 'fa-calculator', 'rotulo' => '3. Avaliação', 'meta' => 'Pontuação'],
                ['url' => '/contratacoes-temporarias/recursos', 'icone' => 'fa-balance-scale', 'rotulo' => '4. Recurso', 'meta' => 'Pedido e resposta'],
                ['url' => '/contratacoes-temporarias/resultado', 'icone' => 'fa-trophy', 'rotulo' => '5. Resultado', 'meta' => 'Classificação'],
                ['url' => '/contratacoes-temporarias/protocolo', 'icone' => 'fa-search', 'rotulo' => 'Protocolo', 'meta' => 'Acompanhar'],
            ],
            'fluxoTitulo' => 'Fluxo do processo seletivo',
        ]);
    }

    public function processos(): View
    {
        $this->garantirSeed();
        $linhas = [];
        foreach ($this->processosLista() as $p) {
            $linhas[] = [
                'id' => $p['id'],
                'titulo' => $p['titulo'],
                'meta' => ($p['numero'] ?? '').' · '.($p['meta'] ?? ''),
                'badge' => FluxoProcesso::rotulo($p['status']),
                'badge_tom' => FluxoProcesso::tom($p['status']),
                'corpo' => 'Inscrições: '.($p['inscricao_inicio'] ?? '—').' a '.($p['inscricao_fim'] ?? '—'),
                'url' => url('/contratacoes-temporarias/processos/'.$p['id']),
            ];
        }

        return view('act::processos', [
            'linhas' => $linhas,
            'chamadaAberta' => (bool) config('act.chamada_aberta', true),
            'statuses' => FluxoProcesso::todos(),
        ]);
    }

    public function salvarProcesso(Request $request): RedirectResponse
    {
        if (! (bool) config('act.chamada_aberta', true)) {
            return back()->with('erro', 'Chamada ACT fechada por config.');
        }
        if (! PocRegistro::disponivel()) {
            return back()->with('erro', 'Schema act.poc_registro ausente — rode migrate.');
        }

        $titulo = trim((string) $request->input('titulo', ''));
        $numero = trim((string) $request->input('numero', ''));
        $meta = trim((string) $request->input('meta', ''));
        $inicio = trim((string) $request->input('inscricao_inicio', ''));
        $fim = trim((string) $request->input('inscricao_fim', ''));
        $descricao = trim((string) $request->input('descricao', ''));
        if ($titulo === '') {
            return back()->with('erro', 'Informe o título do edital.');
        }

        $id = PocRegistro::gravar('processo', $titulo, $meta !== '' ? $meta : ($numero !== '' ? $numero : null), [
            'numero' => $numero !== '' ? $numero : ('ACT-'.date('Y').'-'.str_pad((string) (PocRegistro::contar('processo') + 1), 3, '0', STR_PAD_LEFT)),
            'titulo' => $titulo,
            'descricao' => $descricao,
            'status' => FluxoProcesso::RASCUNHO,
            'inscricao_inicio' => $inicio !== '' ? $inicio : null,
            'inscricao_fim' => $fim !== '' ? $fim : null,
            'aberto_em' => now()->toIso8601String(),
            'sla_preferencias' => (int) config('act.sla_preferencias', 3),
        ]);

        if ((bool) config('act.seed_documentos_padrao', true)) {
            DocumentoRegra::seedPadrao($id);
        }
        ProtocoloInscricao::evento($id, null, null, 'processo', 'Processo criado: '.$titulo, $this->nomeUsuario());

        if (class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\Notificador::class)) {
            \iEducar\Packages\BuritiLagunaAmbiente\Support\Notificador::paraProcesso(
                Processos::PROCESSOS,
                "ACT: novo processo seletivo #{$id} — {$titulo}.",
                '/contratacoes-temporarias/processos/'.$id,
            );
        }

        return redirect('/contratacoes-temporarias/processos/'.$id)
            ->with('ok', "Processo seletivo #{$id} criado. Configure documentos e comissão.");
    }

    public function detalheProcesso(int $id): View|RedirectResponse
    {
        $proc = $this->processoPorId($id);
        if ($proc === null) {
            return redirect('/contratacoes-temporarias/processos')->with('erro', 'Processo não encontrado.');
        }
        $status = $proc['status'];

        return view('act::processo-detalhe', [
            'processo' => $proc,
            'documentos' => DocumentoRegra::doProcesso($id),
            'comissao' => ComissaoAvaliacao::doProcesso($id),
            'usuarios' => $this->usuariosRede(),
            'proximos' => FluxoProcesso::proximos($status),
            'rotulosStatus' => $this->rotulosStatus(),
            'inscricoesQtd' => count(array_filter($this->inscricoesLista(), static fn ($i) => $i['processo_id'] === $id)),
        ]);
    }

    public function alterarStatusProcesso(Request $request, int $id): RedirectResponse
    {
        $reg = PocRegistro::buscar($id);
        if ($reg === null || $reg['tipo'] !== 'processo') {
            return back()->with('erro', 'Processo não encontrado.');
        }
        $p = $reg['payload'];
        $de = (string) ($p['status'] ?? FluxoProcesso::RASCUNHO);
        $para = trim((string) $request->input('status', ''));
        if (! in_array($para, FluxoProcesso::proximos($de), true)) {
            return back()->with('erro', 'Transição de fase não permitida.');
        }
        $p['status'] = $para;
        PocRegistro::atualizarPayload($id, $p, meta: FluxoProcesso::rotulo($para));
        ProtocoloInscricao::evento($id, null, null, 'fase', 'Fase: '.FluxoProcesso::rotulo($de).' → '.FluxoProcesso::rotulo($para), $this->nomeUsuario());

        if (class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\Notificador::class)) {
            \iEducar\Packages\BuritiLagunaAmbiente\Support\Notificador::paraProcesso(
                Processos::PROCESSOS,
                'ACT: processo #'.$id.' mudou de fase para «'.FluxoProcesso::rotulo($para).'».',
                '/contratacoes-temporarias/processos/'.$id,
            );
        }

        return back()->with('ok', 'Fase atualizada para '.FluxoProcesso::rotulo($para).'.');
    }

    public function salvarDocumento(Request $request, int $id): RedirectResponse
    {
        if ($this->processoPorId($id) === null) {
            return back()->with('erro', 'Processo não encontrado.');
        }
        $codigo = trim((string) $request->input('codigo', ''));
        $nome = trim((string) $request->input('nome', ''));
        if ($codigo === '' || $nome === '') {
            return back()->with('erro', 'Código e nome do documento são obrigatórios.');
        }
        $ext = preg_split('/[\s,;]+/', (string) $request->input('extensoes', 'pdf')) ?: ['pdf'];
        DocumentoRegra::gravar(
            $id,
            $codigo,
            $nome,
            $request->boolean('obrigatorio'),
            (float) $request->input('pontuacao_max', 0),
            $ext,
            trim((string) $request->input('descricao', '')),
        );

        return back()->with('ok', "Regra de documento '{$nome}' cadastrada.");
    }

    public function salvarComissao(Request $request, int $id): RedirectResponse
    {
        if ($this->processoPorId($id) === null) {
            return back()->with('erro', 'Processo não encontrado.');
        }
        $userId = (int) $request->input('user_id', 0);
        $nome = trim((string) $request->input('nome', ''));
        $papel = trim((string) $request->input('papel', 'Avaliador')) ?: 'Avaliador';
        if ($userId > 0) {
            foreach ($this->usuariosRede() as $u) {
                if ((int) $u['id'] === $userId) {
                    $nome = $u['nome'];
                    break;
                }
            }
        }
        if ($nome === '') {
            return back()->with('erro', 'Informe o membro da comissão.');
        }
        ComissaoAvaliacao::adicionar($id, $nome, $papel, $userId);

        return back()->with('ok', "{$nome} incluído(a) na comissão.");
    }

    public function removerComissao(int $id, int $membroId): RedirectResponse
    {
        ComissaoAvaliacao::remover($membroId);

        return redirect('/contratacoes-temporarias/processos/'.$id)->with('ok', 'Membro removido da comissão.');
    }

    public function inscricoes(Request $request): View
    {
        $this->garantirSeed();
        $processoId = (int) $request->query('processo_id', 0);
        $processos = $this->processosLista();
        if ($processoId <= 0 && $processos !== []) {
            $processoId = (int) $processos[0]['id'];
        }
        $proc = $processoId > 0 ? $this->processoPorId($processoId) : null;
        $linhas = [];
        foreach ($this->inscricoesLista($processoId > 0 ? $processoId : null) as $i) {
            $linhas[] = [
                'id' => $i['id'],
                'titulo' => $i['nome'],
                'meta' => ($i['protocolo'] ?? '—').' · CPF '.$i['cpf'],
                'badge' => ucfirst($i['status']).($i['classificacao'] ? ' · #'.$i['classificacao'] : ''),
                'badge_tom' => $i['status'] === 'classificado' ? 'ok' : 'warn',
                'corpo' => 'Pontuação: '.$i['pontuacao'],
                'url' => url('/contratacoes-temporarias/inscricoes/'.$i['id']),
            ];
        }

        return view('act::inscricoes', [
            'linhas' => $linhas,
            'processos' => $processos,
            'processoId' => $processoId,
            'processo' => $proc,
            'documentos' => $processoId > 0 ? DocumentoRegra::doProcesso($processoId) : [],
            'vagas' => $this->vagas(),
            'podeInscrever' => $proc !== null && FluxoProcesso::podeInscrever($proc['status']),
            'anexoMaxKb' => AnexoAct::maxKb(),
            'slaPrefs' => (int) config('act.sla_preferencias', 3),
        ]);
    }

    public function salvarInscricao(Request $request): RedirectResponse
    {
        $processoId = (int) $request->input('processo_id', 0);
        $proc = $this->processoPorId($processoId);
        if ($proc === null) {
            return back()->with('erro', 'Selecione um processo válido.');
        }
        if (! FluxoProcesso::podeInscrever($proc['status'])) {
            return back()->with('erro', 'Inscrições não estão abertas nesta fase.');
        }
        $nome = trim((string) $request->input('nome', ''));
        $cpf = preg_replace('/\D+/', '', (string) $request->input('cpf', '')) ?? '';
        if ($nome === '' || strlen($cpf) < 11) {
            return back()->with('erro', 'Nome e CPF válidos são obrigatórios.');
        }
        $prefs = array_map('intval', (array) $request->input('preferencias', []));
        $id = ProtocoloInscricao::abrir($processoId, [
            'nome' => $nome,
            'cpf' => $cpf,
            'email' => trim((string) $request->input('email', '')),
            'telefone' => trim((string) $request->input('telefone', '')),
            'preferencias' => $prefs,
            'observacao' => trim((string) $request->input('observacao', '')),
            'autor' => $this->nomeUsuario(),
        ]);
        $reg = PocRegistro::buscar($id);
        $protocolo = (string) ($reg['payload']['protocolo'] ?? ProtocoloInscricao::gerar($id));

        // Anexos iniciais por código de documento (campo docs[CODIGO])
        $regras = DocumentoRegra::doProcesso($processoId);
        $docsPayload = [];
        foreach ($regras as $regra) {
            $campo = 'doc_'.$regra['codigo'];
            if (! $request->hasFile($campo)) {
                continue;
            }
            try {
                $anexo = AnexoAct::salvar(
                    $request->file($campo),
                    $processoId,
                    'inscricao',
                    $id,
                    $regra['codigo'],
                    $regra['extensoes'],
                    $nome
                );
                $docsPayload[] = [
                    'codigo' => $regra['codigo'],
                    'anexo_id' => $anexo['id'],
                    'nome' => $anexo['nome'],
                ];
            } catch (Throwable $e) {
                return redirect('/contratacoes-temporarias/inscricoes/'.$id)
                    ->with('erro', 'Inscrição ok, anexo '.$regra['codigo'].' falhou: '.$e->getMessage());
            }
        }
        if ($docsPayload !== [] && $reg !== null) {
            $p = $reg['payload'];
            $p['documentos'] = $docsPayload;
            PocRegistro::atualizarPayload($id, $p);
        }

        // Obrigatoriedade
        $faltando = [];
        foreach ($regras as $regra) {
            if (! $regra['obrigatorio']) {
                continue;
            }
            $tem = false;
            foreach ($docsPayload as $d) {
                if ($d['codigo'] === $regra['codigo']) {
                    $tem = true;
                    break;
                }
            }
            if (! $tem) {
                $faltando[] = $regra['nome'];
            }
        }

        $msg = "Inscrição {$protocolo} registrada.";
        if ($faltando !== []) {
            $msg .= ' Documentos pendentes: '.implode(', ', $faltando).'.';
        }

        return redirect('/contratacoes-temporarias/inscricoes/'.$id)->with('ok', $msg);
    }

    public function detalheInscricao(int $id): View|RedirectResponse
    {
        $ins = $this->inscricaoPorId($id);
        if ($ins === null) {
            return redirect('/contratacoes-temporarias/inscricoes')->with('erro', 'Inscrição não encontrada.');
        }
        $proc = $this->processoPorId($ins['processo_id']);
        $anexos = AnexoAct::listar($ins['processo_id'], 'inscricao', $id);

        return view('act::inscricao-detalhe', [
            'inscricao' => $ins,
            'processo' => $proc,
            'documentos' => DocumentoRegra::doProcesso($ins['processo_id']),
            'anexos' => $anexos,
            'timeline' => ProtocoloInscricao::timeline($ins['protocolo'], $id),
            'anexoMaxKb' => AnexoAct::maxKb(),
            'podeAnexar' => $proc !== null && FluxoProcesso::podeInscrever($proc['status']),
        ]);
    }

    public function anexarDocumentoInscricao(Request $request, int $id): RedirectResponse
    {
        $ins = $this->inscricaoPorId($id);
        if ($ins === null) {
            return back()->with('erro', 'Inscrição não encontrada.');
        }
        $codigo = trim((string) $request->input('codigo', ''));
        $regras = DocumentoRegra::doProcesso($ins['processo_id']);
        $regra = null;
        foreach ($regras as $r) {
            if ($r['codigo'] === $codigo) {
                $regra = $r;
                break;
            }
        }
        if ($regra === null || ! $request->hasFile('arquivo')) {
            return back()->with('erro', 'Selecione o tipo de documento e o arquivo.');
        }
        try {
            $anexo = AnexoAct::salvar(
                $request->file('arquivo'),
                $ins['processo_id'],
                'inscricao',
                $id,
                $codigo,
                $regra['extensoes'],
                $this->nomeUsuario()
            );
        } catch (Throwable $e) {
            return back()->with('erro', $e->getMessage());
        }
        $reg = PocRegistro::buscar($id);
        if ($reg !== null) {
            $p = $reg['payload'];
            $docs = (array) ($p['documentos'] ?? []);
            $docs = array_values(array_filter($docs, static fn ($d) => ($d['codigo'] ?? '') !== $codigo));
            $docs[] = ['codigo' => $codigo, 'anexo_id' => $anexo['id'], 'nome' => $anexo['nome']];
            $p['documentos'] = $docs;
            PocRegistro::atualizarPayload($id, $p);
        }
        ProtocoloInscricao::evento($ins['processo_id'], $id, $ins['protocolo'], 'anexo', 'Anexo '.$codigo.' enviado', $this->nomeUsuario());

        return back()->with('ok', 'Documento anexado.');
    }

    public function avaliacao(Request $request): View
    {
        $processoId = (int) $request->query('processo_id', 0);
        $processos = $this->processosLista();
        if ($processoId <= 0 && $processos !== []) {
            $processoId = (int) $processos[0]['id'];
        }
        $proc = $processoId > 0 ? $this->processoPorId($processoId) : null;
        $regras = $processoId > 0 ? DocumentoRegra::doProcesso($processoId) : [];
        $inscricoes = $this->inscricoesLista($processoId > 0 ? $processoId : null);

        return view('act::avaliacao', [
            'processos' => $processos,
            'processoId' => $processoId,
            'processo' => $proc,
            'regras' => $regras,
            'inscricoes' => $inscricoes,
            'comissao' => $processoId > 0 ? ComissaoAvaliacao::doProcesso($processoId) : [],
            'podeAvaliar' => $proc !== null && FluxoProcesso::podeAvaliar($proc['status']),
        ]);
    }

    public function salvarAvaliacao(Request $request, int $id): RedirectResponse
    {
        $ins = $this->inscricaoPorId($id);
        if ($ins === null) {
            return back()->with('erro', 'Inscrição não encontrada.');
        }
        $proc = $this->processoPorId($ins['processo_id']);
        if ($proc === null || ! FluxoProcesso::podeAvaliar($proc['status'])) {
            return back()->with('erro', 'Avaliação não permitida nesta fase.');
        }
        $regras = DocumentoRegra::doProcesso($ins['processo_id']);
        $detalhe = [];
        $total = 0.0;
        foreach ($regras as $regra) {
            $nota = (float) $request->input('nota_'.$regra['codigo'], 0);
            $nota = max(0, min($nota, $regra['pontuacao_max']));
            $detalhe[$regra['codigo']] = $nota;
            $total += $nota;
        }
        $reg = PocRegistro::buscar($id);
        if ($reg === null) {
            return back()->with('erro', 'Registro ausente.');
        }
        $p = $reg['payload'];
        $p['pontuacao'] = $total;
        $p['pontuacao_detalhe'] = $detalhe;
        $p['avaliado_em'] = now()->toIso8601String();
        $p['avaliado_por'] = $this->nomeUsuario();
        $p['status'] = 'avaliado';
        PocRegistro::atualizarPayload($id, $p);
        ProtocoloInscricao::evento($ins['processo_id'], $id, $ins['protocolo'], 'avaliacao', 'Pontuação '.$total, $this->nomeUsuario());

        return back()->with('ok', "Avaliação de {$ins['nome']}: {$total} ponto(s).");
    }

    public function recursos(Request $request): View
    {
        $processoId = (int) $request->query('processo_id', 0);
        $processos = $this->processosLista();
        if ($processoId <= 0 && $processos !== []) {
            $processoId = (int) $processos[0]['id'];
        }
        $proc = $processoId > 0 ? $this->processoPorId($processoId) : null;
        $lista = $processoId > 0 ? RecursoProcesso::doProcesso($processoId) : [];

        return view('act::recursos', [
            'processos' => $processos,
            'processoId' => $processoId,
            'processo' => $proc,
            'recursos' => $lista,
            'inscricoes' => $this->inscricoesLista($processoId > 0 ? $processoId : null),
            'podeAbrir' => $proc !== null && FluxoProcesso::podeRecurso($proc['status']),
            'anexoMaxKb' => AnexoAct::maxKb(),
        ]);
    }

    public function abrirRecurso(Request $request): RedirectResponse
    {
        $inscricaoId = (int) $request->input('inscricao_id', 0);
        $ins = $this->inscricaoPorId($inscricaoId);
        if ($ins === null) {
            return back()->with('erro', 'Inscrição não encontrada.');
        }
        $proc = $this->processoPorId($ins['processo_id']);
        if ($proc === null || ! FluxoProcesso::podeRecurso($proc['status'])) {
            return back()->with('erro', 'Recursos não estão abertos nesta fase.');
        }
        $motivo = trim((string) $request->input('motivo', ''));
        if ($motivo === '') {
            return back()->with('erro', 'Informe o motivo do recurso.');
        }
        $rid = RecursoProcesso::abrir(
            $ins['processo_id'],
            $inscricaoId,
            $ins['nome'],
            $motivo,
            $ins['protocolo'],
            $this->nomeUsuario()
        );
        if ($request->hasFile('anexo')) {
            try {
                $anexo = AnexoAct::salvar($request->file('anexo'), $ins['processo_id'], 'recurso', $rid, null, ['pdf'], $this->nomeUsuario());
                $reg = PocRegistro::buscar($rid);
                if ($reg !== null) {
                    $p = $reg['payload'];
                    $p['anexo_ids'] = [$anexo['id']];
                    PocRegistro::atualizarPayload($rid, $p);
                }
            } catch (Throwable $e) {
                return redirect('/contratacoes-temporarias/recursos/'.$rid)->with('erro', 'Recurso aberto, PDF falhou: '.$e->getMessage());
            }
        }

        return redirect('/contratacoes-temporarias/recursos/'.$rid)->with('ok', 'Recurso protocolado.');
    }

    public function detalheRecurso(int $id): View|RedirectResponse
    {
        $reg = PocRegistro::buscar($id);
        if ($reg === null || $reg['tipo'] !== 'recurso') {
            return redirect('/contratacoes-temporarias/recursos')->with('erro', 'Recurso não encontrado.');
        }
        $p = $reg['payload'];
        $processoId = (int) ($p['processo_id'] ?? 0);
        $proc = $this->processoPorId($processoId);
        $anexos = AnexoAct::listar($processoId, 'recurso', $id);
        $anexosResp = AnexoAct::listar($processoId, 'resposta_recurso', $id);

        return view('act::recurso-detalhe', [
            'recurso' => [
                'id' => $id,
                'processo_id' => $processoId,
                'inscricao_id' => (int) ($p['inscricao_id'] ?? 0),
                'protocolo' => $p['protocolo'] ?? null,
                'candidato' => (string) ($p['candidato'] ?? $reg['titulo']),
                'motivo' => (string) ($p['motivo'] ?? ''),
                'status' => (string) ($p['status'] ?? RecursoProcesso::ABERTO),
                'resposta' => $p['resposta'] ?? null,
                'aberto_em' => $p['aberto_em'] ?? $reg['created_at'],
                'respondido_em' => $p['respondido_em'] ?? null,
                'respondido_por' => $p['respondido_por'] ?? null,
            ],
            'processo' => $proc,
            'anexos' => $anexos,
            'anexosResposta' => $anexosResp,
            'podeResponder' => $proc !== null && FluxoProcesso::podeResponderRecurso($proc['status']) && ($p['status'] ?? '') === RecursoProcesso::ABERTO,
            'anexoMaxKb' => AnexoAct::maxKb(),
        ]);
    }

    public function responderRecurso(Request $request, int $id): RedirectResponse
    {
        $reg = PocRegistro::buscar($id);
        if ($reg === null || $reg['tipo'] !== 'recurso') {
            return back()->with('erro', 'Recurso não encontrado.');
        }
        $p = $reg['payload'];
        $proc = $this->processoPorId((int) $p['processo_id']);
        if ($proc === null || ! FluxoProcesso::podeResponderRecurso($proc['status'])) {
            return back()->with('erro', 'Resposta não permitida nesta fase.');
        }
        $resposta = trim((string) $request->input('resposta', ''));
        $status = (string) $request->input('status', RecursoProcesso::RESPONDIDO);
        if ($resposta === '') {
            return back()->with('erro', 'Informe a resposta.');
        }
        $anexoIds = [];
        if ($request->hasFile('anexo')) {
            try {
                $anexo = AnexoAct::salvar($request->file('anexo'), (int) $p['processo_id'], 'resposta_recurso', $id, null, ['pdf'], $this->nomeUsuario());
                $anexoIds[] = $anexo['id'];
            } catch (Throwable $e) {
                return back()->with('erro', $e->getMessage());
            }
        }
        RecursoProcesso::responder($id, $resposta, $status, $this->nomeUsuario(), $anexoIds);

        // Se deferido, opcionalmente ajustar pontuação
        if ($status === RecursoProcesso::DEFERIDO && $request->filled('nova_pontuacao')) {
            $insId = (int) ($p['inscricao_id'] ?? 0);
            $insReg = PocRegistro::buscar($insId);
            if ($insReg !== null && $insReg['tipo'] === 'inscricao') {
                $ip = $insReg['payload'];
                $ip['pontuacao'] = (float) $request->input('nova_pontuacao');
                $ip['status'] = 'avaliado';
                PocRegistro::atualizarPayload($insId, $ip);
            }
        }

        return redirect('/contratacoes-temporarias/recursos/'.$id)->with('ok', 'Recurso respondido ('.RecursoProcesso::rotulo($status).').');
    }

    public function resultado(Request $request): View
    {
        $processoId = (int) $request->query('processo_id', 0);
        $processos = $this->processosLista();
        if ($processoId <= 0 && $processos !== []) {
            $processoId = (int) $processos[0]['id'];
        }
        $proc = $processoId > 0 ? $this->processoPorId($processoId) : null;
        $itens = $processoId > 0 ? ResultadoFinal::ultimo($processoId) : [];

        return view('act::resultado', [
            'processos' => $processos,
            'processoId' => $processoId,
            'processo' => $proc,
            'itens' => $itens,
            'podeGerar' => $proc !== null && FluxoProcesso::podeGerarResultado($proc['status']),
        ]);
    }

    public function gerarResultado(Request $request): RedirectResponse
    {
        $processoId = (int) $request->input('processo_id', 0);
        $proc = $this->processoPorId($processoId);
        if ($proc === null || ! FluxoProcesso::podeGerarResultado($proc['status'])) {
            return back()->with('erro', 'Não é possível gerar resultado nesta fase.');
        }
        $itens = ResultadoFinal::calcular($processoId, true);
        $reg = PocRegistro::buscar($processoId);
        if ($reg !== null) {
            $p = $reg['payload'];
            $de = (string) ($p['status'] ?? '');
            if (in_array($de, [FluxoProcesso::AVALIACAO, FluxoProcesso::RECURSOS], true)) {
                $p['status'] = $de === FluxoProcesso::RECURSOS ? FluxoProcesso::FINAL : FluxoProcesso::PRELIMINAR;
                PocRegistro::atualizarPayload($processoId, $p, meta: FluxoProcesso::rotulo($p['status']));
            } elseif ($de === FluxoProcesso::PRELIMINAR && $request->boolean('final')) {
                $p['status'] = FluxoProcesso::FINAL;
                PocRegistro::atualizarPayload($processoId, $p, meta: FluxoProcesso::rotulo($p['status']));
            }
        }

        return redirect('/contratacoes-temporarias/resultado?processo_id='.$processoId)
            ->with('ok', 'Resultado gerado com '.count($itens).' classificado(s).');
    }

    public function protocolo(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $inscricao = null;
        $timeline = [];
        if ($q !== '') {
            $inscricao = ProtocoloInscricao::buscarPorProtocolo($q);
            if ($inscricao !== null) {
                $timeline = ProtocoloInscricao::timeline((string) ($inscricao['payload']['protocolo'] ?? $q), $inscricao['id']);
            } else {
                $timeline = ProtocoloInscricao::timeline($q);
            }
        }

        return view('act::protocolo', [
            'q' => $q,
            'inscricao' => $inscricao,
            'timeline' => $timeline,
        ]);
    }

    public function baixarAnexo(int $id): BinaryFileResponse|RedirectResponse
    {
        $meta = AnexoAct::meta($id);
        $path = AnexoAct::caminhoAbsoluto($id);
        if ($meta === null || $path === null) {
            return redirect('/contratacoes-temporarias')->with('erro', 'Anexo não encontrado.');
        }

        return response()->download($path, $meta['nome'], ['Content-Type' => $meta['mime']]);
    }

    public function classificados(): View
    {
        $this->garantirSeed();
        $processos = $this->processosLista();
        $processoId = (int) request()->query('processo_id', $processos[0]['id'] ?? 0);
        $itens = $processoId ? ResultadoFinal::ultimo($processoId) : [];
        if ($itens === []) {
            foreach (PocRegistro::listar('inscricao') as $r) {
                $p = $r['payload'];
                if ((int) ($p['processo_id'] ?? 0) !== $processoId && $processoId > 0) {
                    continue;
                }
                $itens[] = [
                    'inscricao_id' => $r['id'],
                    'nome' => (string) ($p['nome'] ?? $r['titulo']),
                    'protocolo' => $p['protocolo'] ?? $r['meta'],
                    'pontuacao' => (float) ($p['pontuacao'] ?? 0),
                    'classificacao' => (int) ($p['classificacao'] ?? 0),
                    'status' => (string) ($p['status'] ?? 'inscrito'),
                    'servidor_id' => $p['servidor_id'] ?? null,
                    'escola_contratacao' => $p['escola_contratacao'] ?? null,
                ];
            }
            usort($itens, static fn ($a, $b) => ($a['classificacao'] ?: 9999) <=> ($b['classificacao'] ?: 9999));
        }
        $escolas = DB::table('pmieducar.escola')->where('ativo', 1)->orderBy('cod_escola')->limit(80)
            ->get(['cod_escola', DB::raw('(select fantasia from cadastro.juridica where idpes = escola.ref_idpes limit 1) as nome')]);
        $linhas = [];
        foreach ($itens as $c) {
            $status = (string) ($c['status'] ?? 'classificado');
            $contratado = $status === 'contratado' || ! empty($c['servidor_id']);
            $linhas[] = [
                'inscricao_id' => (int) ($c['inscricao_id'] ?? 0),
                'titulo' => '#'.($c['classificacao'] ?? '—').' · '.($c['nome'] ?? ''),
                'meta' => ($c['protocolo'] ?? '—').' · '.($c['pontuacao'] ?? 0).' pts',
                'badge' => $contratado ? 'Contratado' : ('Classe '.($c['classificacao'] ?? '—')),
                'badge_tom' => $contratado ? 'ok' : (($c['classificacao'] ?? 99) <= 3 ? 'ok' : 'warn'),
                'corpo' => $contratado
                    ? ('Servidor #'.($c['servidor_id'] ?? '—').' · escola '.($c['escola_contratacao'] ?? '—'))
                    : 'Status: '.$status.' · disponível para admissão',
                'contratado' => $contratado,
            ];
        }

        return view('act::classificados', [
            'linhas' => $linhas,
            'escolas' => $escolas,
            'podeAdmitir' => Acesso::permite(Processos::ADMITIR, 'gravar'),
            'processos' => $processos,
            'processoId' => $processoId,
        ]);
    }

    public function admitir(Request $request, int $id): RedirectResponse
    {
        if (! Acesso::permite(Processos::ADMITIR, 'gravar')) {
            return back()->with('erro', 'Sem permissão para admitir/contratar.');
        }
        $dados = $request->validate([
            'escola_id' => 'nullable|integer',
            'criar_usuario' => 'nullable',
        ]);
        try {
            $r = AdmissaoAct::admitir($id, [
                'escola_id' => isset($dados['escola_id']) ? (int) $dados['escola_id'] : null,
                'criar_usuario' => $request->boolean('criar_usuario'),
            ]);
            $msg = $r['ja_contratado']
                ? 'Candidato já estava contratado (servidor #'.$r['servidor_id'].').'
                : 'Contratado · servidor #'.$r['servidor_id'].' · escola '.$r['escola_id'].' · disponível no quadro de horários e diário.';
            return redirect('/contratacoes-temporarias/classificados')->with('ok', $msg);
        } catch (Throwable $e) {
            return back()->with('erro', $e->getMessage());
        }
    }

    public function alocacao(): View
    {
        $this->garantirSeed();
        $candidatos = $this->candidatos();
        $vagas = $this->vagas();
        $vagasIdx = collect($vagas)->keyBy('id');
        $candIdx = collect($candidatos)->keyBy('candidato_id');
        $resultado = ChamadaAct::alocar($candidatos, array_column($vagas, 'id'));

        $linhas = [];
        foreach ($resultado['alocacoes'] as $a) {
            $linhas[] = [
                'titulo' => $candIdx[$a['candidato_id']]['nome'] ?? ('Candidato '.$a['candidato_id']),
                'meta' => $vagasIdx[$a['vaga_id']]['titulo'] ?? ('Vaga '.$a['vaga_id']),
                'badge' => 'Alocado',
                'badge_tom' => 'ok',
                'corpo' => 'Preferência atendida conforme classificação.',
            ];
        }
        foreach ($resultado['vagas_restantes'] as $vagaId) {
            $linhas[] = [
                'titulo' => $vagasIdx[$vagaId]['titulo'] ?? ('Vaga '.$vagaId),
                'meta' => 'Sem candidato alocado',
                'badge' => 'Aberta',
                'badge_tom' => 'warn',
                'corpo' => 'Aguardando próxima chamada ou remanescentes.',
            ];
        }

        return view('act::alocacao', compact('linhas'));
    }

    /** @return list<array<string,mixed>> */
    private function processosLista(): array
    {
        $out = [];
        foreach (PocRegistro::listar('processo') as $r) {
            $p = $r['payload'];
            $out[] = [
                'id' => $r['id'],
                'titulo' => (string) ($p['titulo'] ?? $r['titulo']),
                'numero' => (string) ($p['numero'] ?? ''),
                'meta' => $r['meta'],
                'descricao' => (string) ($p['descricao'] ?? ''),
                'status' => (string) ($p['status'] ?? FluxoProcesso::RASCUNHO),
                'inscricao_inicio' => $p['inscricao_inicio'] ?? null,
                'inscricao_fim' => $p['inscricao_fim'] ?? null,
                'created_at' => $r['created_at'],
            ];
        }

        return $out;
    }

    /** @return array<string,mixed>|null */
    private function processoPorId(int $id): ?array
    {
        foreach ($this->processosLista() as $p) {
            if ($p['id'] === $id) {
                return $p;
            }
        }

        return null;
    }

    /** @return list<array<string,mixed>> */
    private function inscricoesLista(?int $processoId = null): array
    {
        $out = [];
        foreach (PocRegistro::listar('inscricao', 500) as $r) {
            $p = $r['payload'];
            if ($processoId !== null && (int) ($p['processo_id'] ?? 0) !== $processoId) {
                continue;
            }
            $out[] = [
                'id' => $r['id'],
                'processo_id' => (int) ($p['processo_id'] ?? 0),
                'nome' => (string) ($p['nome'] ?? $r['titulo']),
                'cpf' => (string) ($p['cpf'] ?? ''),
                'email' => (string) ($p['email'] ?? ''),
                'telefone' => (string) ($p['telefone'] ?? ''),
                'protocolo' => isset($p['protocolo']) ? (string) $p['protocolo'] : null,
                'status' => (string) ($p['status'] ?? 'inscrito'),
                'pontuacao' => (float) ($p['pontuacao'] ?? 0),
                'pontuacao_detalhe' => (array) ($p['pontuacao_detalhe'] ?? []),
                'classificacao' => isset($p['classificacao']) ? (int) $p['classificacao'] : null,
                'preferencias' => array_map('intval', (array) ($p['preferencias'] ?? [])),
                'documentos' => (array) ($p['documentos'] ?? []),
                'inscrito_em' => (string) ($p['inscrito_em'] ?? $r['created_at'] ?? ''),
            ];
        }

        return $out;
    }

    /** @return array<string,mixed>|null */
    private function inscricaoPorId(int $id): ?array
    {
        foreach ($this->inscricoesLista() as $i) {
            if ($i['id'] === $id) {
                return $i;
            }
        }

        return null;
    }

    /** @return array<string,string> */
    private function rotulosStatus(): array
    {
        $out = [];
        foreach (FluxoProcesso::todos() as $s) {
            $out[$s] = FluxoProcesso::rotulo($s);
        }

        return $out;
    }

    private function nomeUsuario(): string
    {
        $u = Auth::user();

        return $u?->name ?? $u?->login ?? $u?->email ?? 'sistema';
    }

    /**
     * Comissão: só usuários com permissão de ver avaliação/comissão/processos ACT,
     * no escopo do ator (rede / escola).
     *
     * @return list<array{id:int,nome:string,meta?:string,login?:string}>
     */
    private function usuariosRede(): array
    {
        if (class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\UsuariosSelecionaveis::class)) {
            return \iEducar\Packages\BuritiLagunaAmbiente\Support\UsuariosSelecionaveis::paraSelect(
                Auth::user(),
                [
                    \iEducar\Packages\BuritiContratacoesTemporarias\Support\Processos::COMISSAO,
                    \iEducar\Packages\BuritiContratacoesTemporarias\Support\Processos::AVALIACAO,
                    \iEducar\Packages\BuritiContratacoesTemporarias\Support\Processos::PROCESSOS,
                ],
            );
        }

        try {
            if (! Schema::hasTable('users')) {
                return [];
            }
            $q = DB::table('users')->orderBy('name')->limit(150);
            if (Schema::hasColumn('users', 'active')) {
                $q->where('active', true);
            }

            return $q->get(['id', 'name', 'email'])
                ->map(static fn ($u): array => [
                    'id' => (int) $u->id,
                    'nome' => (string) ($u->name ?: $u->email ?: 'Usuário #'.$u->id),
                ])
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /** @return list<array{candidato_id:int,nome:string,classificacao:int,preferencias:list<int>}> */
    private function candidatos(): array
    {
        $out = [];
        // Preferir inscrições classificadas do processo mais recente
        $processos = $this->processosLista();
        if ($processos !== []) {
            $pid = (int) $processos[0]['id'];
            foreach (ResultadoFinal::ultimo($pid) as $i) {
                $ins = $this->inscricaoPorId($i['inscricao_id']);
                $out[] = [
                    'candidato_id' => $i['inscricao_id'],
                    'nome' => $i['nome'],
                    'classificacao' => $i['classificacao'],
                    'preferencias' => $ins['preferencias'] ?? [],
                ];
            }
            if ($out !== []) {
                return $out;
            }
        }
        foreach (PocRegistro::listar('candidato') as $c) {
            $payload = $c['payload'];
            $out[] = [
                'candidato_id' => (int) ($payload['candidato_id'] ?? $c['id']),
                'nome' => $c['titulo'],
                'classificacao' => (int) ($payload['classificacao'] ?? 999),
                'preferencias' => array_map('intval', (array) ($payload['preferencias'] ?? [])),
            ];
        }

        return $out;
    }

    /** @return list<array{id:int,titulo:string}> */
    private function vagas(): array
    {
        $out = [];
        foreach (PocRegistro::listar('vaga') as $v) {
            $payload = $v['payload'];
            $out[] = [
                'id' => (int) ($payload['vaga_id'] ?? $v['id']),
                'titulo' => $v['titulo'],
            ];
        }

        return $out;
    }

    private function garantirSeed(): void
    {
        if (! PocRegistro::disponivel()) {
            return;
        }
        if (! (bool) config('act.seed_nucleo', true)) {
            return;
        }
        if (PocRegistro::contar('vaga') > 0 || PocRegistro::contar('candidato') > 0) {
            return;
        }

        $escolas = $this->escolasReais();
        $servidores = $this->servidoresReais();
        if ($escolas === [] && $servidores === []) {
            $this->seedDemo();

            return;
        }

        $vagaId = 1;
        foreach ($escolas as $e) {
            PocRegistro::gravar('vaga', 'PEB I — '.$e['nome'], 'Escola '.$e['cod'], [
                'vaga_id' => $vagaId,
                'cod_escola' => $e['cod'],
                'cargo' => 'PEB I',
            ]);
            $vagaId++;
        }
        if ($escolas === []) {
            PocRegistro::gravar('vaga', 'PEB I — Rede Laguna', null, ['vaga_id' => 1, 'cargo' => 'PEB I']);
        }

        $totalVagas = max(1, PocRegistro::contar('vaga'));
        $idClass = 1;
        $sla = (int) config('act.sla_preferencias', 3);
        foreach ($servidores as $s) {
            $prefs = range(1, min($sla, $totalVagas));
            shuffle($prefs);
            PocRegistro::gravar('candidato', $s['nome'], 'Cargo '.$s['cargo'], [
                'candidato_id' => $s['id'],
                'classificacao' => $idClass,
                'preferencias' => array_values($prefs),
            ]);
            $idClass++;
            if ($idClass > 12) {
                break;
            }
        }
        if ($idClass === 1) {
            $this->seedCandidatosDemo($totalVagas);
        }
    }

    /** @return list<array{cod:int,nome:string}> */
    private function escolasReais(): array
    {
        if (! Schema::hasTable('pmieducar.escola') || ! Schema::hasTable('cadastro.juridica')) {
            return [];
        }
        try {
            return DB::table('pmieducar.escola')
                ->join('cadastro.juridica', 'cadastro.juridica.idpes', '=', 'pmieducar.escola.ref_idpes')
                ->where('pmieducar.escola.ativo', 1)
                ->orderBy('cadastro.juridica.fantasia')
                ->limit(5)
                ->get(['pmieducar.escola.cod_escola as cod', 'cadastro.juridica.fantasia as nome'])
                ->map(static fn ($r): array => ['cod' => (int) $r->cod, 'nome' => (string) $r->nome])
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /** @return list<array{id:int,nome:string,cargo:string}> */
    private function servidoresReais(): array
    {
        if (! Schema::hasTable('pmieducar.servidor') || ! Schema::hasTable('cadastro.pessoa')) {
            return [];
        }
        try {
            return DB::table('pmieducar.servidor')
                ->join('cadastro.pessoa', 'cadastro.pessoa.idpes', '=', 'pmieducar.servidor.cod_servidor')
                ->where('pmieducar.servidor.ativo', 1)
                ->orderBy('pmieducar.servidor.cod_servidor')
                ->limit(10)
                ->get(['pmieducar.servidor.cod_servidor as id', 'cadastro.pessoa.nome as nome'])
                ->map(static fn ($r): array => [
                    'id' => (int) $r->id,
                    'nome' => (string) $r->nome,
                    'cargo' => 'PEB I',
                ])
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    private function seedDemo(): void
    {
        $vagas = [
            ['titulo' => 'PEB I — EMEF Norte', 'meta' => 'Demo'],
            ['titulo' => 'PEB I — EMEF Sul', 'meta' => 'Demo'],
            ['titulo' => 'PEB II — Matemática', 'meta' => 'Demo'],
        ];
        foreach ($vagas as $i => $v) {
            PocRegistro::gravar('vaga', $v['titulo'], $v['meta'], ['vaga_id' => $i + 1, 'cargo' => 'PEB']);
        }
        $this->seedCandidatosDemo(count($vagas));
    }

    private function seedCandidatosDemo(int $totalVagas): void
    {
        $nomes = ['Elena Martins', 'Fábio Rocha', 'Gina Alves', 'Hugo Prado'];
        $sla = (int) config('act.sla_preferencias', 3);
        foreach ($nomes as $i => $nome) {
            $prefs = range(1, min($sla, max(1, $totalVagas)));
            shuffle($prefs);
            PocRegistro::gravar('candidato', $nome, 'Demo', [
                'candidato_id' => 100 + $i + 1,
                'classificacao' => $i + 1,
                'preferencias' => array_values($prefs),
            ]);
        }
    }
}
