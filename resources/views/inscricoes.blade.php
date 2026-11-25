@extends('act::layout')
@section('titulo', 'Inscrições')
@section('migalha', 'Inscrições')
@section('icone-tela', 'fa-user-plus')
@section('titulo-tela', 'Inscrições no processo')
@section('lead')
    Cadastro pela SME ou pelo portal público: gera protocolo e anexa documentos como na ficha de matrícula.
@endsection
@section('acoes-topo')
    <a class="botao claro" href="{{ url('/contratacoes-temporarias/candidato') }}" target="_blank" rel="noopener"><i class="fa fa-external-link"></i> Portal do candidato</a>
    <a class="botao claro" href="{{ url('/contratacoes-temporarias/protocolo') }}"><i class="fa fa-search"></i> Protocolo</a>
@endsection

@section('conteudo')
    @php
        $opcoesProcesso = collect($processos ?? [])->map(fn ($p) => [
            'id' => $p['id'],
            'nome' => $p['titulo'],
            'meta' => \iEducar\Packages\BuritiContratacoesTemporarias\Services\FluxoProcesso::rotulo($p['status']),
        ])->all();
    @endphp

    <div class="aviso-portal">
        <p><strong>Quem é de fora da rede</strong> se inscreve pelo portal público (link no login, quando a flag estiver ligada). Aqui a SME registra ou acompanha as fichas.</p>
        <a class="botao claro" href="{{ url('/contratacoes-temporarias/candidato') }}" target="_blank" rel="noopener">Abrir portal</a>
    </div>

    <div class="bloco">
        <form class="form-grid" method="get" action="{{ url('/contratacoes-temporarias/inscricoes') }}">
            @include('buriti-ui::select-busca', [
                'campo' => 'processo_id',
                'rotulo' => 'Edital / processo',
                'icone' => 'fa-gavel',
                'opcoes' => $opcoesProcesso,
                'selecionado' => $processoId,
                'vazio' => '— escolher edital —',
                'placeholder' => 'Digite o nome ou número do edital…',
                'ajuda' => 'Escolha o edital para ver inscritos e, se a fase permitir, cadastrar uma ficha.',
                'onchange' => 'this.form.submit()',
                'obrigatorio' => false,
                'colWide' => true,
                'tamanho' => min(6, max(3, count($opcoesProcesso) ?: 3)),
            ])
        </form>
    </div>

    @if(!empty($podeInscrever))
        <div class="bloco">
            <h2><i class="fa fa-plus"></i> Nova inscrição (SME)</h2>
            <p style="margin:0 0 12px;color:var(--suave)">Use quando a pessoa estiver na secretaria. Candidatos externos devem preferir o portal.</p>
            <form class="form-grid" method="post" action="{{ url('/contratacoes-temporarias/inscricoes') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="processo_id" value="{{ $processoId }}">
                <label>Nome completo <input name="nome" required placeholder="Como no documento"></label>
                <label>CPF <input name="cpf" required placeholder="somente números" inputmode="numeric"></label>
                <label>E-mail <input type="email" name="email" placeholder="para contato"></label>
                <label>Telefone <input name="telefone" placeholder="WhatsApp"></label>
                @if(!empty($vagas))
                    @include('buriti-ui::select-multi', [
                        'campo' => 'preferencias[]',
                        'rotulo' => 'Preferência de vagas / escolas (ordem)',
                        'icone' => 'fa-building',
                        'opcoes' => collect($vagas)->map(fn ($v) => ['id' => $v['id'], 'nome' => $v['titulo']])->all(),
                        'ajuda' => 'Marque até '.($slaPrefs ?? 3).' opções — a ordem vale como na escolha de escolas.',
                        'tamanho' => min(6, max(3, count($vagas))),
                    ])
                @endif
                @foreach(($documentos ?? []) as $d)
                    @include('buriti-ui::campo-anexo', [
                        'campo' => 'doc_'.$d['codigo'],
                        'rotulo' => $d['nome'].($d['obrigatorio'] ? ' *' : ''),
                        'tituloAcao' => 'Soltar «'.$d['nome'].'» aqui',
                        'ajuda' => strtoupper(implode(', ', $d['extensoes'])).' · até '.$anexoMaxKb.' KB · máx '.$d['pontuacao_max'].' pts',
                        'accept' => '.'.implode(',.', $d['extensoes']),
                        'obrigatorio' => ! empty($d['obrigatorio']),
                        'icone' => 'fa-id-card',
                    ])
                @endforeach
                <label class="col-wide">Observação <textarea name="observacao" placeholder="Algo que a banca precise saber…"></textarea></label>
                <div class="acoes"><button class="botao" type="submit"><i class="fa fa-paper-plane"></i> Inscrever e gerar protocolo</button></div>
            </form>
        </div>
    @elseif($processo)
        <p class="flash warn">Inscrições fechadas nesta fase ({{ \iEducar\Packages\BuritiContratacoesTemporarias\Services\FluxoProcesso::rotulo($processo['status']) }}). Abra a fase no edital para liberar o portal e este formulário.</p>
    @endif

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
@endsection
