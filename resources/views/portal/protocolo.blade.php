@extends('act::portal.layout')
@section('titulo', 'Acompanhar protocolo')

@section('conteudo')
    <h1>Acompanhar pelo protocolo</h1>
    <p class="lead">Digite o número que você recebeu na inscrição — como consultar um pedido de entrega pelo código de rastreio.</p>

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
            <div class="cartao">
                <h2>{{ $inscricao['nome'] }}</h2>
                <p class="protocolo-grande" style="font-size:1.25rem">{{ $inscricao['protocolo'] ?? $q }}</p>
                <p style="margin:0;color:var(--suave)">
                    Status <strong>{{ $inscricao['status'] }}</strong>
                    · {{ $inscricao['pontuacao'] }} pts
                    @if(!empty($inscricao['classificacao'])) · classificação #{{ $inscricao['classificacao'] }}@endif
                </p>
            </div>
        @else
            <p class="flash warn">Não encontramos inscrição com este protocolo. Confira os dígitos ou tente de novo mais tarde.</p>
        @endif

        <div class="cartao" style="padding:0;overflow:hidden">
            <div class="buriti-chat">
                <div class="buriti-chat-topo">
                    <span class="avatar"><i class="fa fa-history"></i></span>
                    <div>
                        <strong>Histórico</strong>
                        <small>Mensagens do processo</small>
                    </div>
                </div>
                <div class="buriti-chat-fios">
                    @forelse(($timeline ?? []) as $i => $ev)
                        <div class="buriti-balao {{ $i % 2 === 0 ? 'eles' : 'eu' }}">
                            <span class="selo">{{ $ev['tipo'] }}</span>
                            <span class="quem">{{ $ev['autor'] ?: 'Sistema' }}</span>
                            <p class="texto">{{ $ev['texto'] }}</p>
                            <span class="quando">{{ $ev['em'] }}</span>
                        </div>
                    @empty
                        <div class="buriti-chat-vazio">Nenhum evento encontrado para este protocolo.</div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
@endsection
