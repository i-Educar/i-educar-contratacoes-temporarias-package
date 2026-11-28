<?php

namespace iEducar\Packages\BuritiContratacoesTemporarias\Http\Controllers;

use iEducar\Packages\BuritiContratacoesTemporarias\Services\AnexoAct;
use iEducar\Packages\BuritiContratacoesTemporarias\Services\DocumentoRegra;
use iEducar\Packages\BuritiContratacoesTemporarias\Services\FluxoProcesso;
use iEducar\Packages\BuritiContratacoesTemporarias\Services\PocRegistro;
use iEducar\Packages\BuritiContratacoesTemporarias\Services\ProtocoloInscricao;
use iEducar\Packages\BuritiContratacoesTemporarias\Services\ResultadoFinal;
use iEducar\Packages\BuritiContratacoesTemporarias\Services\VagaOferta;
use iEducar\Packages\BuritiContratacoesTemporarias\Support\Identidade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

/**
 * Portal público do candidato (sem login i-Educar).
 */
class CandidatoPortalController extends Controller
{
    public function inicio(): View
    {
        $abertos = [];
        $outros = [];
        foreach ($this->processos() as $p) {
            $p['cargos'] = VagaOferta::cargosResumo((int) $p['id']);
            $p['vagas_qtd'] = count(VagaOferta::doProcesso((int) $p['id']));
            if (FluxoProcesso::podeInscrever($p['status'])) {
                $abertos[] = $p;
            } else {
                $outros[] = $p;
            }
        }

        return view('act::portal.inicio', [
            'identidade' => Identidade::tokens(),
            'municipio' => config('app.name', 'Rede municipal'),
            'abertos' => $abertos,
            'outros' => array_slice($outros, 0, 6),
            'setor' => $this->setor(),
            'resultadosPdf' => $this->itensResultadoPdf(),
        ]);
    }

    public function inscrever(int $id): View|RedirectResponse
    {
        $proc = $this->processoPorId($id);
        if ($proc === null) {
            return redirect()->route('act.candidato.inicio')->with('erro', 'Edital não encontrado.');
        }
        if (! FluxoProcesso::podeInscrever($proc['status'])) {
            return redirect()->route('act.candidato.inicio')
                ->with('erro', 'As inscrições deste edital não estão abertas no momento.');
        }

        return view('act::portal.inscricao', [
            'identidade' => Identidade::tokens(),
            'municipio' => config('app.name', 'Rede municipal'),
            'processo' => $proc,
            'documentos' => DocumentoRegra::doProcesso($id),
            'vagas' => VagaOferta::doProcesso($id),
            'cargosResumo' => VagaOferta::cargosResumo($id),
            'anexoMaxKb' => AnexoAct::maxKb(),
            'slaPrefs' => (int) config('act.sla_preferencias', 3),
            'setor' => $this->setor(),
        ]);
    }

    public function salvar(Request $request, int $id): RedirectResponse
    {
        $proc = $this->processoPorId($id);
        if ($proc === null || ! FluxoProcesso::podeInscrever($proc['status'])) {
            return redirect()->route('act.candidato.inicio')->with('erro', 'Inscrições indisponíveis neste edital.');
        }

        $nome = trim((string) $request->input('nome', ''));
        $cpf = preg_replace('/\D+/', '', (string) $request->input('cpf', '')) ?? '';
        if ($nome === '' || strlen($cpf) < 11) {
            return back()->withInput()->with('erro', 'Informe nome completo e CPF válido (11 dígitos).');
        }

        $prefs = array_map('intval', (array) $request->input('preferencias', []));
        $maxPrefs = max(1, (int) config('act.sla_preferencias', 3));
        $prefs = array_slice(array_values(array_unique(array_filter($prefs))), 0, $maxPrefs);

        $inscricaoId = ProtocoloInscricao::abrir($id, [
            'nome' => $nome,
            'cpf' => $cpf,
            'email' => trim((string) $request->input('email', '')),
            'telefone' => trim((string) $request->input('telefone', '')),
            'preferencias' => $prefs,
            'observacao' => trim((string) $request->input('observacao', '')),
            'autor' => 'candidato-portal',
            'origem' => 'portal_publico',
        ]);

        $reg = PocRegistro::buscar($inscricaoId);
        $protocolo = (string) ($reg['payload']['protocolo'] ?? ProtocoloInscricao::gerar($inscricaoId));

        $regras = DocumentoRegra::doProcesso($id);
        $docsPayload = [];
        foreach ($regras as $regra) {
            $campo = 'doc_'.$regra['codigo'];
            if (! $request->hasFile($campo)) {
                continue;
            }
            try {
                $anexo = AnexoAct::salvar(
                    $request->file($campo),
                    $id,
                    'inscricao',
                    $inscricaoId,
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
                return redirect()->route('act.candidato.comprovante', ['id' => $inscricaoId])
                    ->with('erro', 'Inscrição ok, mas o anexo «'.$regra['nome'].'» falhou: '.$e->getMessage());
            }
        }

        if ($docsPayload !== [] && $reg !== null) {
            $p = $reg['payload'];
            $p['documentos'] = $docsPayload;
            PocRegistro::atualizarPayload($inscricaoId, $p);
        }

        $faltando = [];
        foreach ($regras as $regra) {
            if (! $regra['obrigatorio']) {
                continue;
            }
            $tem = false;
            foreach ($docsPayload as $d) {
                if (($d['codigo'] ?? '') === $regra['codigo']) {
                    $tem = true;
                    break;
                }
            }
            if (! $tem) {
                $faltando[] = $regra['nome'];
            }
        }

        $msg = 'Inscrição '.$protocolo.' registrada. Guarde o protocolo para acompanhar.';
        if ($faltando !== []) {
            $msg .= ' Ainda faltam: '.implode(', ', $faltando).'.';
        }

        return redirect()->route('act.candidato.comprovante', ['id' => $inscricaoId])->with('ok', $msg);
    }

    public function comprovante(int $id): View|RedirectResponse
    {
        $ins = $this->inscricaoPorId($id);
        if ($ins === null) {
            return redirect()->route('act.candidato.inicio')->with('erro', 'Inscrição não encontrada.');
        }
        $proc = $this->processoPorId((int) $ins['processo_id']);

        return view('act::portal.comprovante', [
            'identidade' => Identidade::tokens(),
            'municipio' => config('app.name', 'Rede municipal'),
            'inscricao' => $ins,
            'processo' => $proc,
            'timeline' => ProtocoloInscricao::timeline((string) ($ins['protocolo'] ?? ''), $id),
            'setor' => $this->setor(),
        ]);
    }

    public function protocolo(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $inscricao = null;
        $timeline = [];
        if ($q !== '') {
            $achado = ProtocoloInscricao::buscarPorProtocolo($q);
            if ($achado !== null) {
                $inscricao = $this->inscricaoPorId((int) $achado['id']);
            }
            $timeline = ProtocoloInscricao::timeline(
                (string) (($inscricao['protocolo'] ?? null) ?: $q),
                isset($inscricao['id']) ? (int) $inscricao['id'] : null
            );
        }

        return view('act::portal.protocolo', [
            'identidade' => Identidade::tokens(),
            'municipio' => config('app.name', 'Rede municipal'),
            'q' => $q,
            'inscricao' => $inscricao,
            'timeline' => $timeline,
            'setor' => $this->setor(),
        ]);
    }

    public function resultados(): View
    {
        return view('act::portal.resultados', [
            'identidade' => Identidade::tokens(),
            'municipio' => config('app.name', 'Rede municipal'),
            'setor' => $this->setor(),
            'resultadosPdf' => $this->itensResultadoPdf(),
        ]);
    }

    /**
     * PDF público de classificação do processo (após divulgação / encerramento das fases).
     * Layout institucional via Exportação unificada.
     */
    public function resultadoPdf(int $id): SymfonyResponse
    {
        $proc = $this->processoPorId($id);
        if ($proc === null || ! $this->processoPodePublicarPdf($proc)) {
            abort(404, 'Resultado ainda não disponível para este edital.');
        }

        $linhas = $this->linhasResultadosPublicos($id);
        $titulo = 'Classificação — '.$proc['titulo'];
        $subtitulo = trim(($proc['numero'] ?? '').' · '.FluxoProcesso::rotulo((string) $proc['status']));
        $slug = preg_replace('/[^\w\-]+/u', '-', mb_strtolower((string) $proc['titulo'])) ?: 'edital';
        $nomeBase = 'resultado-contratacao-'.$id.'-'.$slug;
        $cabecalhos = ['Posição', 'Candidato', 'Protocolo', 'Pontuação', 'Situação'];

        if (class_exists(\iEducar\Packages\BuritiExportacaoUnificada\Support\ExportacaoModulo::class)
            && \iEducar\Packages\BuritiExportacaoUnificada\Support\ExportacaoModulo::disponivel()) {
            return \iEducar\Packages\BuritiExportacaoUnificada\Support\ExportacaoModulo::download(
                $titulo,
                $cabecalhos,
                $linhas,
                'Contratações',
                'pdf',
                $subtitulo !== '' ? $subtitulo : 'Resultado público do edital',
                'Portal público · Contratações temporárias',
                $nomeBase,
                true,
            );
        }

        if (class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\PdfTabelaPublica::class)) {
            return \iEducar\Packages\BuritiLagunaAmbiente\Support\PdfTabelaPublica::baixar(
                $titulo,
                $cabecalhos,
                $linhas,
                $nomeBase.'.pdf',
                $subtitulo !== '' ? $subtitulo : null,
            );
        }

        abort(503, 'Geração de PDF indisponível.');
    }

    /**
     * @return list<array{titulo:string,meta:string,url:string,rotulo:string}>
     */
    private function itensResultadoPdf(): array
    {
        $itens = [];
        foreach ($this->processos() as $p) {
            if (! $this->processoPodePublicarPdf($p)) {
                continue;
            }
            $meta = array_filter([
                $p['numero'] !== '' ? (string) $p['numero'] : null,
                FluxoProcesso::rotulo((string) $p['status']),
            ]);
            $itens[] = [
                'titulo' => (string) $p['titulo'],
                'meta' => implode(' · ', $meta),
                'url' => url('/contratacoes-temporarias/candidato/resultados/'.$p['id'].'/pdf'),
                'rotulo' => 'Baixar PDF',
            ];
        }

        return $itens;
    }

    /** @param  array<string,mixed>  $proc */
    private function processoPodePublicarPdf(array $proc): bool
    {
        $status = (string) ($proc['status'] ?? '');
        if (FluxoProcesso::podePublicarResultado($status)) {
            return true;
        }

        return ResultadoFinal::ultimo((int) $proc['id']) !== [];
    }

    /**
     * @return list<list<string>>
     */
    private function linhasResultadosPublicos(int $processoId): array
    {
        $linhas = [];
        if ($processoId < 1) {
            return $linhas;
        }
        foreach (ResultadoFinal::ultimo($processoId) as $item) {
            $linhas[] = [
                (string) ($item['classificacao'] ?? '—'),
                $this->anonimizar((string) ($item['nome'] ?? '')),
                (string) ($item['protocolo'] ?? '—'),
                number_format((float) ($item['pontuacao'] ?? 0), 1, ',', '.'),
                (string) ($item['status'] ?? 'classificado'),
            ];
        }

        return $linhas;
    }

    private function anonimizar(string $nome): string
    {
        if (class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\SetorResponsavel::class)) {
            return \iEducar\Packages\BuritiLagunaAmbiente\Support\SetorResponsavel::anonimizarNome($nome);
        }
        $nome = trim($nome);
        if ($nome === '') {
            return '—';
        }
        $partes = preg_split('/\s+/u', $nome) ?: [];

        return implode(' ', array_map(static fn ($p) => mb_substr($p, 0, 1).'****', $partes));
    }

    /** @return array{nome:string,endereco:string,horario:string,telefone:string,email:string,observacao:string,publicado:bool} */
    private function setor(): array
    {
        if (! class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\SetorResponsavel::class)) {
            return [
                'nome' => '',
                'endereco' => '',
                'horario' => '',
                'telefone' => '',
                'email' => '',
                'observacao' => '',
                'publicado' => false,
            ];
        }

        return \iEducar\Packages\BuritiLagunaAmbiente\Support\SetorResponsavel::de('act');
    }

    /** @return list<array<string,mixed>> */
    private function processos(): array
    {
        $out = [];
        foreach (PocRegistro::listar('processo', 100) as $r) {
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
            ];
        }

        return $out;
    }

    /** @return array<string,mixed>|null */
    private function processoPorId(int $id): ?array
    {
        foreach ($this->processos() as $p) {
            if ((int) $p['id'] === $id) {
                return $p;
            }
        }

        return null;
    }

    /** @return array<string,mixed>|null */
    private function inscricaoPorId(int $id): ?array
    {
        $r = PocRegistro::buscar($id);
        if ($r === null || ($r['tipo'] ?? '') !== 'inscricao') {
            return null;
        }
        $p = $r['payload'];

        return [
            'id' => $r['id'],
            'processo_id' => (int) ($p['processo_id'] ?? 0),
            'nome' => (string) ($p['nome'] ?? $r['titulo']),
            'cpf' => (string) ($p['cpf'] ?? ''),
            'email' => (string) ($p['email'] ?? ''),
            'telefone' => (string) ($p['telefone'] ?? ''),
            'protocolo' => isset($p['protocolo']) ? (string) $p['protocolo'] : null,
            'status' => (string) ($p['status'] ?? 'inscrito'),
            'pontuacao' => (float) ($p['pontuacao'] ?? 0),
            'classificacao' => isset($p['classificacao']) ? (int) $p['classificacao'] : null,
            'documentos' => (array) ($p['documentos'] ?? []),
            'inscrito_em' => (string) ($p['inscrito_em'] ?? $r['created_at'] ?? ''),
        ];
    }

}
