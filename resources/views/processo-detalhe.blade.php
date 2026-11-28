@extends('act::layout')
@section('titulo', 'Processo #'.$processo['id'])
@section('migalha', 'Processo')
@section('icone-tela', 'fa-gavel')
@section('titulo-tela', $processo['titulo'])
@section('lead')
    {{ $processo['numero'] ?? '' }} · {{ $rotulosStatus[$processo['status']] ?? $processo['status'] }} · {{ $inscricoesQtd }} inscrição(ões) — avance as fases como etapas de um concurso.
@endsection
@section('acoes-topo')
    <a class="botao claro icone" href="{{ url('/contratacoes-temporarias/processos') }}" title="Processos" aria-label="Processos"><i aria-hidden="true" class="fa fa-list"></i></a>
    <a class="botao claro icone" href="{{ url('/contratacoes-temporarias/inscricoes?processo_id='.$processo['id']) }}" title="Inscrições" aria-label="Inscrições"><i aria-hidden="true" class="fa fa-user-plus"></i></a>
    @if(($processo['status'] ?? '') === 'inscricoes_abertas')
        <a class="botao icone" href="{{ url('/contratacoes-temporarias/candidato/inscrever/'.$processo['id']) }}" target="_blank" rel="noopener" title="Link público" aria-label="Link público"><i aria-hidden="true" class="fa fa-external-link"></i></a>
    @endif
@endsection

@section('conteudo')
    <div class="bloco">
        <p>
            <span class="badge {{ \iEducar\Packages\BuritiContratacoesTemporarias\Services\FluxoProcesso::tom($processo['status']) }}">
                {{ $rotulosStatus[$processo['status']] ?? $processo['status'] }}
            </span>
        </p>
        <p style="color:var(--suave)">{{ $processo['descricao'] ?: ($processo['meta'] ?? '') }}</p>
        <p>Inscrições: <strong>{{ $processo['inscricao_inicio'] ?? '—' }}</strong> a <strong>{{ $processo['inscricao_fim'] ?? '—' }}</strong></p>

        @if(!empty($proximos))
            <form class="form-grid" method="post" action="{{ url('/contratacoes-temporarias/processos/'.$processo['id'].'/status') }}" style="margin-top:12px">
                @csrf
                <label>Avançar fase
                    <select name="status" required>
                        @foreach($proximos as $st)
                            <option value="{{ $st }}">{{ $rotulosStatus[$st] ?? $st }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="acoes"><button class="botao" type="submit"><i class="fa fa-forward"></i> Atualizar fase</button></div>
            </form>
        @endif
    </div>

    <div class="fluxo-sep">Configuração do edital</div>

    <section class="fluxo-bloco docs">
        <header class="fluxo-cabeca">
            <div>
                <h2><i class="fa fa-file-text-o"></i> Documentos e pontuação</h2>
                <p>Regras do edital — o que o candidato anexa e quanto vale cada papel.</p>
            </div>
            <span class="selo-fluxo">Documentos</span>
        </header>
        <table>
            <thead><tr><th>Código</th><th>Documento</th><th>Obrig.</th><th>Pontuação máx.</th><th>Extensões</th></tr></thead>
            <tbody>
            @forelse($documentos as $d)
                <tr>
                    <td>{{ $d['codigo'] }}</td>
                    <td>{{ $d['nome'] }}<br><small style="color:var(--suave)">{{ $d['descricao'] }}</small></td>
                    <td>{{ $d['obrigatorio'] ? 'Sim' : 'Não' }}</td>
                    <td>{{ $d['pontuacao_max'] }}</td>
                    <td>{{ implode(', ', $d['extensoes']) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Nenhuma regra — use o formulário abaixo (ou seed padrão na criação).</td></tr>
            @endforelse
            </tbody>
        </table>
        <form class="form-grid" method="post" action="{{ url('/contratacoes-temporarias/processos/'.$processo['id'].'/documentos') }}" style="margin-top:14px">
            @csrf
            <label>Código <input name="codigo" required placeholder="DIPLOMA"></label>
            <label>Nome <input name="nome" required placeholder="Diploma de graduação"></label>
            <label>Pontuação máx. <input type="number" step="0.5" min="0" name="pontuacao_max" value="0"></label>
            <label>Obrigatório?
                <select name="obrigatorio"><option value="0">Não</option><option value="1">Sim</option></select>
            </label>
            <label>Extensões <input name="extensoes" value="pdf" placeholder="pdf, jpg"></label>
            <label class="col-wide">Descrição / regra de pontuação <input name="descricao" placeholder="Ex.: 2 pts por ano, máx 10"></label>
            <div class="acoes"><button class="botao claro" type="submit"><i class="fa fa-plus"></i> Incluir documento</button></div>
        </form>
    </section>

    <div class="fluxo-sep">Cargos e escolas do edital</div>

    <section class="fluxo-bloco docs">
        <header class="fluxo-cabeca">
            <div>
                <h2><i class="fa fa-briefcase"></i> Cargos / vagas disponíveis</h2>
                <p>O que o candidato vê no portal: cargo, escola, endereço e segmento/turmas.</p>
            </div>
            <span class="selo-fluxo">Ofertas</span>
        </header>

        @if(!empty($cargosResumo))
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin:0 0 12px">
                @foreach($cargosResumo as $c)
                    <span class="badge ok">
                        {{ $c['cargo'] }} · {{ $c['quantidade'] }} vaga(s)
                        @if($c['escolas'] > 0) · {{ $c['escolas'] }} escola(s)@endif
                    </span>
                @endforeach
            </div>
        @endif

        <table>
            <thead>
                <tr>
                    <th>Cargo</th>
                    <th>Escola</th>
                    <th>Endereço</th>
                    <th>Segmento / turmas</th>
                    <th>Qtd.</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse(($vagas ?? []) as $v)
                <tr>
                    <td>
                        <strong>{{ $v['cargo'] ?: '—' }}</strong>
                        @if(!empty($v['carga_horaria']))
                            <br><small style="color:var(--suave)">{{ $v['carga_horaria'] }}</small>
                        @endif
                    </td>
                    <td>{{ $v['escola'] ?: '—' }}</td>
                    <td><small>{{ $v['endereco'] ?: '—' }}</small></td>
                    <td>
                        <small>
                            @if($v['segmento'] !== ''){{ $v['segmento'] }}@endif
                            @if($v['turmas'] !== '')
                                @if($v['segmento'] !== '')<br>@endif
                                Turmas: {{ $v['turmas'] }}
                            @endif
                            @if($v['segmento'] === '' && $v['turmas'] === '')—@endif
                        </small>
                    </td>
                    <td>{{ $v['quantidade'] }}</td>
                    <td>
                        <form method="post" action="{{ url('/contratacoes-temporarias/processos/'.$processo['id'].'/vagas/'.$v['registro_id'].'/remover') }}" onsubmit="return confirm('Remover esta oferta do edital?')">
                            @csrf
                            <button class="botao claro" type="submit" style="padding:2px 8px;font-size:.75rem">Remover</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">Nenhuma vaga ainda — cadastre cargo e escola abaixo para aparecer no portal do candidato.</td></tr>
            @endforelse
            </tbody>
        </table>

        <form class="form-grid" method="post" action="{{ url('/contratacoes-temporarias/processos/'.$processo['id'].'/vagas') }}" style="margin-top:14px">
            @csrf
            <label>Cargo *
                <input name="cargo" required list="act-cargos-sugeridos" placeholder="Ex.: PEB I, PEB II, Auxiliar de turma" value="{{ old('cargo') }}">
            </label>
            <datalist id="act-cargos-sugeridos">
                <option value="PEB I">
                <option value="PEB II">
                <option value="Professor de Apoio">
                <option value="Auxiliar de turma">
                <option value="Monitor">
                <option value="Merendeira">
                <option value="Agente administrativo">
            </datalist>
            <label>Quantidade de vagas <input type="number" name="quantidade" min="1" value="{{ old('quantidade', 1) }}"></label>
            <label>Carga horária <input name="carga_horaria" placeholder="Ex.: 20h / 40h" value="{{ old('carga_horaria') }}"></label>
            @include('buriti-ui::select-busca', [
                'campo' => 'escola_id',
                'rotulo' => 'Escola de lotação',
                'icone' => 'fa-building',
                'opcoes' => collect($escolasCatalogo ?? [])->map(fn ($e) => [
                    'id' => $e['id'],
                    'nome' => $e['nome'],
                    'meta' => $e['meta'] ?? '',
                ])->all(),
                'selecionado' => old('escola_id'),
                'vazio' => '— selecione a escola —',
                'ajuda' => 'Endereço, cursos e turmas são preenchidos automaticamente a partir do cadastro da escola.',
                'colWide' => true,
            ])
            <label class="col-wide">Segmento / cursos (opcional, sobrescreve o automático)
                <input name="segmento" value="{{ old('segmento') }}" placeholder="Deixe em branco para usar os cursos da escola">
            </label>
            <label class="col-wide">Turmas (opcional)
                <input name="turmas" value="{{ old('turmas') }}" placeholder="Deixe em branco para listar turmas ativas do ano">
            </label>
            <div class="acoes"><button class="botao" type="submit"><i class="fa fa-plus"></i> Incluir cargo / vaga</button></div>
        </form>
    </section>

    <div class="fluxo-sep">Banca avaliadora (outro fluxo)</div>

    <section class="fluxo-bloco equipe">
        <header class="fluxo-cabeca">
            <div>
                <h2><i class="fa fa-users"></i> Comissão de avaliação</h2>
                <p>Quem corrige e pontua — só pessoas com permissão neste módulo e no seu escopo (rede/escola).</p>
            </div>
            <span class="selo-fluxo">Equipe</span>
        </header>
        <ul style="margin:0 0 12px;padding-left:18px">
            @forelse($comissao as $m)
                <li>
                    <strong>{{ $m['nome'] }}</strong> · {{ $m['papel'] }}
                    <form method="post" action="{{ url('/contratacoes-temporarias/processos/'.$processo['id'].'/comissao/'.$m['id'].'/remover') }}" style="display:inline">
                        @csrf
                        <button class="botao claro" type="submit" style="padding:2px 8px;font-size:.75rem">Remover</button>
                    </form>
                </li>
            @empty
                <li style="color:var(--suave)">Nenhum membro ainda.</li>
            @endforelse
        </ul>
        <form class="form-grid" method="post" action="{{ url('/contratacoes-temporarias/processos/'.$processo['id'].'/comissao') }}">
            @csrf
            @include('buriti-ui::select-usuario', [
                'campo' => 'user_id',
                'rotulo' => 'Pessoa com acesso ao ACT',
                'usuarios' => $usuarios ?? [],
                'vazio' => '— ou informe o nome abaixo —',
                'ajuda' => empty($usuarios)
                    ? 'Ninguém elegível: confira se o tipo de usuário tem menu de avaliação/comissão.'
                    : 'Só entram quem tem permissão no ACT e está no seu escopo. Ou digite o nome livre abaixo.',
            ])
            <label>Nome livre <input name="nome" placeholder="Se a pessoa não estiver na lista"></label>
            <label>Papel na banca
                <select name="papel">
                    <option>Presidente</option>
                    <option selected>Avaliador</option>
                    <option>Suplente</option>
                    <option>Secretário</option>
                </select>
            </label>
            <div class="acoes"><button class="botao" type="submit"><i class="fa fa-user-plus"></i> Incluir na comissão</button></div>
        </form>
    </section>
@endsection
