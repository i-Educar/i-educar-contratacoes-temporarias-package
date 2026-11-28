@extends('act::layout')
@section('titulo', 'Protocolo')
@section('migalha', 'Protocolo')
@section('icone-tela', 'fa-search')
@section('titulo-tela', 'Acompanhar pelo protocolo')
@section('lead')
    Digite o protocolo e veja o histórico como uma conversa — do pedido ao resultado. Candidatos externos usam o mesmo número no portal público.
@endsection

@section('conteudo')
    <div class="buriti-filtro-bloco">
        <h2><i class="fa fa-search"></i> Busca</h2>
        <form class="buriti-filtro" method="get" action="{{ url('/contratacoes-temporarias/protocolo') }}">
            <label>Protocolo <input name="q" value="{{ $q ?? '' }}" placeholder="ACT-2026-000001" required></label>
            <div class="acoes"><button class="botao" type="submit"><i class="fa fa-search"></i> Buscar</button></div>
        </form>
    </div>

    @if(($q ?? '') !== '')
        @if($inscricao)
            @php $p = $inscricao['payload']; @endphp
            <div class="bloco">
                <h2>{{ $p['nome'] ?? $inscricao['titulo'] }}</h2>
                <p>Protocolo <strong>{{ $p['protocolo'] ?? $q }}</strong> · status {{ $p['status'] ?? '—' }} · {{ $p['pontuacao'] ?? 0 }} pts
                    @if(!empty($p['classificacao'])) · classificação #{{ $p['classificacao'] }}@endif
                </p>
                <p><a class="botao claro" href="{{ url('/contratacoes-temporarias/inscricoes/'.$inscricao['id']) }}"><i class="fa fa-eye"></i> Abrir inscrição</a></p>
            </div>
        @else
            <p class="flash warn">Nenhuma inscrição com este protocolo — exibindo eventos coincidentes, se houver.</p>
        @endif

        <div class="bloco" style="padding:0;overflow:hidden">
            <div class="buriti-chat">
                <div class="buriti-chat-topo">
                    <span class="avatar"><i class="fa fa-history"></i></span>
                    <div>
                        <strong>Histórico do protocolo</strong>
                        <small>Cada passo aparece como uma mensagem</small>
                    </div>
                </div>
                <div class="buriti-chat-fios">
                    @if(empty($timeline))
                        <div class="buriti-chat-vazio">Nenhum evento encontrado para este protocolo.</div>
                    @else
                        @foreach($timeline as $i => $ev)
                            <div class="buriti-balao {{ $i % 2 === 0 ? 'eles' : 'eu' }}">
                                <span class="selo">{{ $ev['tipo'] }}</span>
                                <span class="quem">{{ $ev['autor'] ?: 'Sistema' }}</span>
                                <p class="texto">{{ $ev['texto'] }}</p>
                                <span class="quando">{{ $ev['em'] }}</span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    @endif
@endsection
