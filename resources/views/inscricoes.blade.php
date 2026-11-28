@extends('act::layout')
@section('titulo', 'Inscrições')
@section('migalha', 'Inscrições')
@section('icone-tela', 'fa-user-plus')
@section('titulo-tela', 'Inscrições no processo')
@section('lead')
    Foque nos inscritos; cadastrar e o portal ficam nos botões.
@endsection
@section('acoes-topo')
    @if(!empty($podeInscrever))
        <button type="button" class="botao primaria icone" data-buriti-modal="modal-nova-inscricao" title="Nova inscrição" aria-label="Nova inscrição"><i aria-hidden="true" class="fa fa-plus"></i></button>
    @elseif($processo)
        <button type="button" class="botao buriti-acao-bloqueada icone" disabled title="Inscrições fechadas nesta fase" aria-label="Nova inscrição"><i aria-hidden="true" class="fa fa-lock"></i></button>
    @endif
    <a class="botao claro icone" href="{{ url('/contratacoes-temporarias/candidato') }}" target="_blank" rel="noopener" title="Portal" aria-label="Portal"><i aria-hidden="true" class="fa fa-external-link"></i></a>
    <a class="botao claro icone" href="{{ url('/contratacoes-temporarias/protocolo') }}" title="Protocolo" aria-label="Protocolo"><i aria-hidden="true" class="fa fa-search"></i></a>
@endsection

@section('foco')
<section class="buriti-foco">
    <p class="buriti-foco-kicker">Edital</p>
    <h2 class="buriti-foco-titulo">
        @if($processo)
            {{ $processo['titulo'] ?? 'Processo' }} · {{ count($linhas ?? []) }} inscrito(s)
        @else
            Escolha um edital para ver as fichas
        @endif
    </h2>
    <p class="buriti-foco-texto">
        @if(!empty($podeInscrever))
            Cadastro pela SME ou pelo portal público.
        @elseif($processo)
            Inscrições fechadas nesta fase ({{ \iEducar\Packages\BuritiContratacoesTemporarias\Services\FluxoProcesso::rotulo($processo['status']) }}).
        @else
            Selecione o processo abaixo.
        @endif
    </p>
</section>
@endsection

@section('conteudo')
    @php
        $opcoesProcesso = collect($processos ?? [])->map(fn ($p) => [
            'id' => $p['id'],
            'nome' => $p['titulo'],
            'meta' => \iEducar\Packages\BuritiContratacoesTemporarias\Services\FluxoProcesso::rotulo($p['status']),
        ])->all();
    @endphp

    <div class="buriti-filtro-bloco">
        <h2><i class="fa fa-filter"></i> Edital</h2>
        <form class="buriti-filtro" method="get" action="{{ url('/contratacoes-temporarias/inscricoes') }}">
            @include('buriti-ui::select-busca', [
                'campo' => 'processo_id',
                'rotulo' => 'Edital / processo',
                'icone' => 'fa-gavel',
                'opcoes' => $opcoesProcesso,
                'selecionado' => $processoId,
                'vazio' => 'Escolher edital',
                'onchange' => 'this.form.submit()',
                'compacto' => true,
            ])
        </form>
    </div>

    <div class="bloco">
        <h2><i class="fa fa-list"></i> Inscritos ({{ count($linhas ?? []) }})</h2>
        @if(!empty($linhas))
            <div class="card-grid">
                @foreach($linhas as $i => $l)
                    <article class="card">
                        <header>
                            <span class="ico"><i class="fa fa-user"></i></span>
                            <div><h3>{{ $l['titulo'] }}</h3><p class="meta">{{ $l['meta'] }}</p></div>
                        </header>
                        <span class="badge {{ $l['badge_tom'] }}">{{ $l['badge'] }}</span>
                        <p style="margin:8px 0 0;color:var(--suave)">{{ $l['corpo'] }}</p>
                        <p style="margin:10px 0 0"><a class="botao claro" href="{{ $l['url'] }}"><i class="fa fa-eye"></i> Abrir</a></p>
                    </article>
                @endforeach
            </div>
        @else
            <p style="color:var(--suave);margin:0">Nenhuma inscrição neste processo ainda.</p>
        @endif
    </div>

    @if(!empty($podeInscrever))
        @include('buriti-ui::operacao-modal', [
            'id' => 'modal-nova-inscricao',
            'titulo' => 'Nova inscrição (SME)',
            'lead' => 'Use quando a pessoa estiver na secretaria. Externos preferem o portal.',
            'corpo' => view('act::partials.inscricao-nova', [
                'processoId' => $processoId,
                'vagas' => $vagas ?? [],
                'documentos' => $documentos ?? [],
                'slaPrefs' => $slaPrefs ?? 3,
                'anexoMaxKb' => $anexoMaxKb ?? 5120,
            ])->render(),
        ])
    @endif
@endsection
