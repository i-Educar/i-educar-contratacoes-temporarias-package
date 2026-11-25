@extends('act::portal.layout')
@section('titulo', 'Comprovante — '.$inscricao['protocolo'])

@section('conteudo')
    <h1>Inscrição recebida</h1>
    <p class="lead">Guarde este número como o comprovante de uma fila no banco: é com ele que você acompanha tudo.</p>

    <div class="cartao">
        <p style="margin:0;color:var(--suave)">Protocolo</p>
        <p class="protocolo-grande">{{ $inscricao['protocolo'] ?? '—' }}</p>
        <p style="margin:0"><strong>{{ $inscricao['nome'] }}</strong></p>
        <p style="margin:4px 0 0;color:var(--suave)">
            CPF {{ $inscricao['cpf'] }}
            @if($processo) · {{ $processo['titulo'] }}@endif
            · status {{ $inscricao['status'] }}
        </p>
        <div class="acoes">
            <a class="botao" href="{{ url('/contratacoes-temporarias/candidato/protocolo?q='.urlencode($inscricao['protocolo'] ?? '')) }}">
                <i class="fa fa-search"></i> Acompanhar protocolo
            </a>
            <a class="botao claro" href="{{ url('/contratacoes-temporarias/candidato') }}">
                <i class="fa fa-home"></i> Outros editais
            </a>
        </div>
    </div>

    @if(!empty($timeline))
        <div class="cartao" style="padding:0;overflow:hidden">
            <div class="buriti-chat">
                <div class="buriti-chat-topo">
                    <span class="avatar"><i class="fa fa-history"></i></span>
                    <div>
                        <strong>Linha do tempo</strong>
                        <small>Cada passo da sua inscrição</small>
                    </div>
                </div>
                <div class="buriti-chat-fios">
                    @foreach($timeline as $i => $ev)
                        <div class="buriti-balao {{ $i % 2 === 0 ? 'eles' : 'eu' }}">
                            <span class="selo">{{ $ev['tipo'] }}</span>
                            <span class="quem">{{ $ev['autor'] ?: 'Sistema' }}</span>
                            <p class="texto">{{ $ev['texto'] }}</p>
                            <span class="quando">{{ $ev['em'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
@endsection
