@extends('act::layout')
@section('titulo', 'Avaliação')
@section('migalha', 'Avaliação')
@section('icone-tela', 'fa-calculator')
@section('titulo-tela', 'Avaliação e pontuação')
@section('lead')
    A comissão nota cada documento como numa prova: ponto por critério do edital, até o máximo permitido.
@endsection

@section('conteudo')
    @php
        $opcoesProcesso = collect($processos ?? [])->map(fn ($p) => [
            'id' => $p['id'],
            'nome' => $p['titulo'],
            'meta' => \iEducar\Packages\BuritiContratacoesTemporarias\Services\FluxoProcesso::rotulo($p['status'] ?? ''),
        ])->all();
    @endphp

    <div class="buriti-filtro-bloco">
        <h2><i class="fa fa-filter"></i> Edital</h2>
        <form class="buriti-filtro" method="get" action="{{ url('/contratacoes-temporarias/avaliacao') }}">
            @include('buriti-ui::select-busca', [
                'campo' => 'processo_id',
                'rotulo' => 'Edital em avaliação',
                'icone' => 'fa-gavel',
                'opcoes' => $opcoesProcesso,
                'selecionado' => $processoId,
                'vazio' => 'Escolher edital',
                'onchange' => 'this.form.submit()',
                'compacto' => true,
            ])
        </form>
        @if(!empty($comissao))
            <p style="margin:8px 0 0;color:var(--suave);font-size:.85rem">Comissão: {{ collect($comissao)->pluck('nome')->implode(', ') }}</p>
        @endif
    </div>

    @if(empty($podeAvaliar))
        <p class="flash warn">Avaliação disponível nas fases de análise / preliminar / recursos. Avance o edital se necessário.</p>
    @endif

    @forelse(($inscricoes ?? []) as $ins)
        <div class="bloco">
            <h2>#{{ $ins['id'] }} · {{ $ins['nome'] }} <small style="font-weight:500;color:var(--suave)">{{ $ins['protocolo'] }} · atual {{ $ins['pontuacao'] }} pts</small></h2>
            @if(!empty($podeAvaliar))
                <form class="form-grid" method="post" action="{{ url('/contratacoes-temporarias/avaliacao/'.$ins['id']) }}">
                    @csrf
                    @foreach(($regras ?? []) as $r)
                        <label>{{ $r['codigo'] }} (máx {{ $r['pontuacao_max'] }})
                            <input type="number" step="0.5" min="0" max="{{ $r['pontuacao_max'] }}" name="nota_{{ $r['codigo'] }}" value="{{ $ins['pontuacao_detalhe'][$r['codigo']] ?? 0 }}">
                        </label>
                    @endforeach
                    <div class="acoes"><button class="botao" type="submit"><i class="fa fa-check"></i> Salvar pontuação</button></div>
                </form>
            @else
                <p style="margin:0;color:var(--suave)">Detalhe: {{ $ins['pontuacao_detalhe'] ? json_encode($ins['pontuacao_detalhe']) : '—' }}</p>
            @endif
        </div>
    @empty
        <div class="bloco"><p style="margin:0;color:var(--suave)">Sem inscrições para avaliar neste edital.</p></div>
    @endforelse
@endsection
