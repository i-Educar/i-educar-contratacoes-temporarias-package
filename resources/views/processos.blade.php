@extends('act::layout')
@section('titulo', 'Processos seletivos')
@section('migalha', 'Processos')
@section('icone-tela', 'fa-gavel')
@section('titulo-tela', 'Editais / processos seletivos')
@section('lead')
    Monte o edital como um concurso: documentos com pontuação, banca e fases. Com inscrição aberta, o portal público libera o link no login.
@endsection
@section('acoes-topo')
    <a class="botao claro" href="{{ url('/contratacoes-temporarias') }}"><i class="fa fa-dashboard"></i> Painel</a>
@endsection

@section('conteudo')
    @if(!empty($chamadaAberta))
        <div class="bloco">
            <h2><i class="fa fa-plus"></i> Novo processo seletivo</h2>
            <form class="form-grid" method="post" action="{{ url('/contratacoes-temporarias/processos') }}">
                @csrf
                <label>Nº do edital <input name="numero" placeholder="Ex.: 002/2026"></label>
                <label>Início das inscrições <input type="date" name="inscricao_inicio"></label>
                <label>Fim das inscrições <input type="date" name="inscricao_fim"></label>
                <label class="col-wide">Título <input name="titulo" required placeholder="Ex.: Edital ACT 2026/02 — PEB I e II"></label>
                <label class="col-wide">Resumo <input name="meta" placeholder="Laguna · vagas · área"></label>
                <label class="col-wide">Descrição / orientações <textarea name="descricao" placeholder="Texto orientativo ao candidato"></textarea></label>
                <div class="acoes"><button class="botao" type="submit"><i class="fa fa-save"></i> Criar processo</button></div>
            </form>
        </div>
    @endif

    <div class="bloco">
        <h2><i class="fa fa-list"></i> Processos</h2>
        @if(!empty($linhas))
            <div class="card-grid">
                @foreach($linhas as $i => $l)
                    <article class="card" style="animation-delay: {{ $i * 0.04 }}s">
                        <header>
                            <span class="ico"><i class="fa fa-gavel"></i></span>
                            <div>
                                <h3>{{ $l['titulo'] }}</h3>
                                <p class="meta">{{ $l['meta'] ?? '' }}</p>
                            </div>
                        </header>
                        <span class="badge {{ $l['badge_tom'] ?? 'ok' }}">{{ $l['badge'] }}</span>
                        <p style="margin:8px 0 0;color:var(--suave)">{{ $l['corpo'] ?? '' }}</p>
                        @if(!empty($l['url']))
                            <p style="margin:10px 0 0"><a class="botao claro" href="{{ $l['url'] }}"><i class="fa fa-cog"></i> Configurar</a></p>
                        @endif
                    </article>
                @endforeach
            </div>
        @else
            <p style="color:var(--suave);margin:0">Nenhum processo ainda. Crie o edital acima.</p>
        @endif
    </div>
@endsection
