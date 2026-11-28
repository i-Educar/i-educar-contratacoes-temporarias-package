@extends('act::portal.layout')
@section('titulo', 'Inscrição — '.$processo['titulo'])

@section('conteudo')
    <p style="margin:0 0 8px"><a class="botao claro" href="{{ url('/contratacoes-temporarias/candidato') }}"><i class="fa fa-arrow-left"></i> Voltar aos editais</a></p>
    <h1>{{ $processo['titulo'] }}</h1>
    <p class="lead">
        Preencha como se estivesse entregando a ficha na secretaria: nome, CPF e documentos pedidos no edital.
        Ao final você recebe um protocolo para acompanhar.
    </p>

    @include('buriti-ui::setor-contato', ['setor' => $setor ?? 'act'])

    <div class="cartao">
        <p style="margin:0 0 8px">
            <span class="badge ok">Inscrições abertas</span>
            @if(!empty($processo['numero'])) · {{ $processo['numero'] }}@endif
        </p>
        <p style="margin:0;color:var(--suave)">
            Prazo: <strong>{{ $processo['inscricao_inicio'] ?? '—' }}</strong> a <strong>{{ $processo['inscricao_fim'] ?? '—' }}</strong>
        </p>
        @if(!empty($processo['descricao']))
            <p style="margin:10px 0 0">{{ $processo['descricao'] }}</p>
        @endif
        @if(!empty($cargosResumo))
            <div style="margin-top:14px;display:flex;flex-wrap:wrap;gap:8px">
                @foreach($cargosResumo as $c)
                    <span class="badge">
                        {{ $c['cargo'] }}
                        · {{ $c['quantidade'] }} vaga{{ $c['quantidade'] > 1 ? 's' : '' }}
                        @if($c['escolas'] > 0) · {{ $c['escolas'] }} escola{{ $c['escolas'] > 1 ? 's' : '' }}@endif
                    </span>
                @endforeach
            </div>
        @endif
    </div>

    <div class="cartao">
        <h2><i class="fa fa-user-plus"></i> Seus dados</h2>
        <form class="form-grid" method="post" action="{{ url('/contratacoes-temporarias/candidato/inscrever/'.$processo['id']) }}" enctype="multipart/form-data">
            @csrf
            <label>Nome completo *
                <input name="nome" required value="{{ old('nome') }}" placeholder="Como no documento de identidade">
            </label>
            <label>CPF *
                <input name="cpf" required value="{{ old('cpf') }}" placeholder="somente números" inputmode="numeric">
            </label>
            <label>E-mail
                <input type="email" name="email" value="{{ old('email') }}" placeholder="para avisos da SME">
            </label>
            <label>Telefone
                <input name="telefone" value="{{ old('telefone') }}" placeholder="(xx) xxxxx-xxxx">
            </label>

            @include('buriti-ui::select-preferencias-vaga', [
                'campo' => 'preferencias[]',
                'rotulo' => 'Preferência de cargo e escola',
                'icone' => 'fa-briefcase',
                'opcoes' => $vagas ?? [],
                'selecionados' => old('preferencias', []),
                'maximo' => (int) ($slaPrefs ?? 3),
                'ajuda' => 'Toque nos cards para montar a ordem de preferência (até '.($slaPrefs ?? 3).'). Cargo, endereço e turmas/segmento aparecem em cada opção.',
            ])

            @foreach(($documentos ?? []) as $d)
                @include('buriti-ui::campo-anexo', [
                    'campo' => 'doc_'.$d['codigo'],
                    'rotulo' => $d['nome'].($d['obrigatorio'] ? ' *' : ''),
                    'tituloAcao' => 'Soltar «'.$d['nome'].'» aqui',
                    'ajuda' => ($d['descricao'] ?: 'Documento do edital').' · '.strtoupper(implode(', ', $d['extensoes'])).' · até '.$anexoMaxKb.' KB',
                    'accept' => '.'.implode(',.', $d['extensoes']),
                    'obrigatorio' => ! empty($d['obrigatorio']),
                    'icone' => 'fa-id-card',
                ])
            @endforeach

            <label class="col-wide">Observação (opcional)
                <textarea name="observacao" placeholder="Algo que a banca precise saber…">{{ old('observacao') }}</textarea>
            </label>

            <div class="acoes col-wide">
                <button class="botao" type="submit"><i class="fa fa-paper-plane"></i> Enviar inscrição</button>
            </div>
        </form>
    </div>
@endsection
