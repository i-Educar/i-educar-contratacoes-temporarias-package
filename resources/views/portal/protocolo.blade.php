@extends('act::portal.layout')
@section('titulo', 'Acompanhar protocolo')

@section('conteudo')
    <h1>Acompanhar pelo protocolo</h1>
    <p class="lead">Informe o número recebido na inscrição para consultar a situação oficial do processo seletivo.</p>

    @include('buriti-ui::setor-contato', ['setor' => $setor ?? 'act'])

    <div class="cartao">
        <form class="form-grid" method="get" action="{{ url('/contratacoes-temporarias/candidato/protocolo') }}">
            <label class="col-wide">Protocolo
                <input name="q" value="{{ $q ?? '' }}" required placeholder="ACT-2026-000001" autocomplete="off">
            </label>
            <div class="acoes"><button class="botao" type="submit"><i class="fa fa-search"></i> Buscar</button></div>
        </form>
    </div>

    @if(($q ?? '') !== '')
        @if($inscricao)
            @php
                $st = (string) ($inscricao['status'] ?? 'inscrito');
                $map = [
                    'inscrito' => 0,
                    'em_analise' => 1,
                    'avaliado' => 2,
                    'classificado' => 3,
                    'admitido' => 4,
                    'eliminado' => 4,
                    'indeferido' => 4,
                ];
                $idx = $map[$st] ?? 1;
                $etapas = [
                    ['rotulo' => 'Inscrição registrada', 'detalhe' => 'Protocolo gerado no portal.', 'feito' => $idx >= 0, 'atual' => $idx === 0],
                    ['rotulo' => 'Análise documental', 'detalhe' => 'Conferência dos anexos exigidos.', 'feito' => $idx >= 1, 'atual' => $idx === 1],
                    ['rotulo' => 'Pontuação / avaliação', 'detalhe' => 'Aplicação dos critérios do edital.', 'feito' => $idx >= 2, 'atual' => $idx === 2],
                    ['rotulo' => 'Resultado', 'detalhe' => ucfirst($st).(isset($inscricao['classificacao']) ? ' · classificação #'.$inscricao['classificacao'] : ''), 'feito' => $idx >= 3, 'atual' => $idx >= 3],
                ];
                $nomePublico = class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\SetorResponsavel::class)
                    ? \iEducar\Packages\BuritiLagunaAmbiente\Support\SetorResponsavel::anonimizarNome((string) $inscricao['nome'])
                    : $inscricao['nome'];
            @endphp
            <div class="cartao">
                <h2>{{ $nomePublico }}</h2>
                <p class="protocolo-grande" style="font-size:1.25rem">{{ $inscricao['protocolo'] ?? $q }}</p>
                <p style="margin:0;color:var(--suave)">
                    Status <strong>{{ $inscricao['status'] }}</strong>
                    · {{ $inscricao['pontuacao'] }} pts
                    @if(!empty($inscricao['classificacao'])) · classificação #{{ $inscricao['classificacao'] }}@endif
                </p>
            </div>

            @include('buriti-ui::protocolo-etapas', [
                'titulo' => 'Etapas do processo',
                'etapas' => $etapas,
            ])

            <div class="cartao">
                <h2>Histórico oficial</h2>
                @forelse(($timeline ?? []) as $ev)
                    <article style="padding:10px 0;border-top:1px solid var(--linha)">
                        <strong style="display:block">{{ $ev['tipo'] }}</strong>
                        <span style="color:var(--suave);font-size:.85rem">{{ $ev['autor'] ?: 'Sistema' }} · {{ $ev['em'] }}</span>
                        <p style="margin:6px 0 0">{{ $ev['texto'] }}</p>
                    </article>
                @empty
                    <p style="margin:0;color:var(--suave)">Nenhum evento encontrado para este protocolo.</p>
                @endforelse
            </div>
        @else
            <p class="flash warn">Não encontramos inscrição com este protocolo. Confira os dígitos ou tente de novo mais tarde.</p>
        @endif
    @endif

    <p class="acoes">
        <a class="botao claro" href="{{ url('/contratacoes-temporarias/candidato/resultados') }}"><i class="fa fa-list-ol"></i> Ver resultados publicados</a>
    </p>
@endsection
