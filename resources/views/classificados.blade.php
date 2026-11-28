@extends('act::layout')
@section('titulo', 'Classificados')
@section('migalha', 'Classificados')
@section('icone-tela', 'fa-list-ol')
@section('titulo-tela', 'Classificados')
@section('lead')
    Lista ordenada do processo. Quem tem permissão pode <strong>admitir/contratar</strong> — isso cria o servidor e a alocação para o quadro de horários e o diário.
@endsection
@section('acoes-topo')
    @include('exportacao::partials.botoes-exportar', [
        'action' => url('/contratacoes-temporarias/classificados/exportar'),
        'formatos' => ['pdf', 'xlsx', 'csv'],
        'opcoes' => [
            ['value' => 'lista', 'label' => 'Lista de classificados'],
        ],
        'campoExtra' => ['processo_id' => $processoId ?? ''],
        'tituloModal' => 'Download — Classificados',
    ])
    <a class="botao claro icone" href="{{ url('/contratacoes-temporarias') }}" title="Painel" aria-label="Painel"><i aria-hidden="true" class="fa fa-dashboard"></i></a>
@endsection

@section('conteudo')

    <div class="bloco">
        @if(!empty($linhas))
            <div class="card-grid">
                @foreach($linhas as $i => $l)
                    <article class="card" style="animation-delay: {{ $i * 0.04 }}s">
                        <header>
                            <span class="ico"><i class="fa fa-list-ol"></i></span>
                            <div>
                                <h3>{{ $l['titulo'] ?? ('Item '.($i+1)) }}</h3>
                                <p class="meta">{{ $l['meta'] ?? '' }}</p>
                            </div>
                        </header>
                        @if(!empty($l['badge']))
                            <span class="badge {{ $l['badge_tom'] ?? 'ok' }}">{{ $l['badge'] }}</span>
                        @endif
                        <p style="margin:8px 0 0;color:var(--suave)">{{ $l['corpo'] ?? '' }}</p>
                        @if(!empty($podeAdmitir) && empty($l['contratado']) && !empty($l['inscricao_id']))
                            <form method="post" action="{{ url('/contratacoes-temporarias/admitir/'.$l['inscricao_id']) }}" style="margin-top:12px;display:grid;gap:8px">
                                @csrf
                                <label style="font-size:.8rem;color:var(--suave)">Escola de lotação
                                    <select name="escola_id" required style="width:100%;min-height:34px">
                                        <option value="">Selecione…</option>
                                        @foreach(($escolas ?? []) as $e)
                                            <option value="{{ $e->cod_escola }}">{{ $e->nome ?: ('Escola #'.$e->cod_escola) }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <input type="hidden" name="criar_usuario" value="0">
                                <label style="font-size:.8rem;display:flex;gap:6px;align-items:center">
                                    <input type="checkbox" name="criar_usuario" value="1" checked> Criar acesso de professor
                                </label>
                                <button class="botao" type="submit" onclick="return confirm('Contratar este classificado e liberar no quadro/diário?')">
                                    <i class="fa fa-handshake-o"></i> Admitir / contratar
                                </button>
                            </form>
                        @endif
                    </article>
                @endforeach
            </div>
        @else
            <p style="color:var(--suave);margin:0">Sem classificados neste processo.</p>
        @endif
    </div>
@endsection
