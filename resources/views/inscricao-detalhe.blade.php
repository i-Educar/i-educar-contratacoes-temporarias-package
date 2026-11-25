@extends('act::layout')
@section('titulo', 'Inscrição '.$inscricao['protocolo'])
@section('migalha', 'Inscrição')
@section('icone-tela', 'fa-user')
@section('titulo-tela', $inscricao['nome'])
@section('lead')
    Protocolo {{ $inscricao['protocolo'] ?? '—' }} · {{ $inscricao['status'] }} · {{ $inscricao['pontuacao'] }} pts
@endsection
@section('acoes-topo')
    <a class="botao claro" href="{{ url('/contratacoes-temporarias/inscricoes?processo_id='.$inscricao['processo_id']) }}"><i class="fa fa-list"></i> Inscrições</a>
    <a class="botao claro" href="{{ url('/contratacoes-temporarias/protocolo?q='.urlencode($inscricao['protocolo'] ?? '')) }}"><i class="fa fa-search"></i> Protocolo</a>
@endsection

@section('conteudo')
    <div class="bloco">
        <dl class="form-grid">
            <div><strong>CPF</strong><br>{{ $inscricao['cpf'] }}</div>
            <div><strong>E-mail</strong><br>{{ $inscricao['email'] ?: '—' }}</div>
            <div><strong>Telefone</strong><br>{{ $inscricao['telefone'] ?: '—' }}</div>
            <div><strong>Classificação</strong><br>{{ $inscricao['classificacao'] ?? '—' }}</div>
            <div class="col-wide"><strong>Documentos enviados</strong><br>
                @forelse($inscricao['documentos'] as $d)
                    {{ $d['codigo'] }}@if(!empty($d['anexo_id'])) — <a href="{{ url('/contratacoes-temporarias/anexo/'.$d['anexo_id']) }}">{{ $d['nome'] ?? 'arquivo' }}</a>@endif
                    @if(!$loop->last); @endif
                @empty
                    Nenhum ainda
                @endforelse
            </div>
        </dl>
        @if(!empty($anexos))
            <p style="margin-top:10px"><strong>Arquivos</strong></p>
            <ul>
                @foreach($anexos as $a)
                    <li><a href="{{ url('/contratacoes-temporarias/anexo/'.$a['id']) }}"><i class="fa fa-paperclip"></i> {{ $a['codigo_documento'] }} · {{ $a['nome'] }}</a></li>
                @endforeach
            </ul>
        @endif
    </div>

    @if(!empty($podeAnexar))
        <div class="bloco">
            <h2>Anexar documento</h2>
            <form class="form-grid" method="post" action="{{ url('/contratacoes-temporarias/inscricoes/'.$inscricao['id'].'/anexo') }}" enctype="multipart/form-data">
                @csrf
                @include('buriti-ui::select-busca', [
                    'campo' => 'codigo',
                    'rotulo' => 'Tipo de documento',
                    'icone' => 'fa-file-text-o',
                    'opcoes' => collect($documentos)->map(fn ($d) => [
                        'id' => $d['codigo'],
                        'nome' => $d['codigo'].' — '.$d['nome'],
                        'meta' => ($d['obrigatorio'] ? 'Obrigatório' : 'Opcional').' · máx '.$d['pontuacao_max'].' pts',
                    ])->all(),
                    'selecionado' => null,
                    'vazio' => '— escolher tipo —',
                    'placeholder' => 'Buscar diploma, RG, comprovante…',
                    'ajuda' => 'Qual papel do edital você está anexando agora.',
                    'obrigatorio' => true,
                    'colWide' => true,
                    'tamanho' => min(6, max(3, count($documentos) ?: 3)),
                ])
                @include('buriti-ui::campo-anexo', [
                    'campo' => 'arquivo',
                    'rotulo' => 'Arquivo do documento',
                    'tituloAcao' => 'Soltar o arquivo aqui',
                    'ajuda' => 'Toque para escolher no celular ou arraste do computador.',
                    'obrigatorio' => true,
                    'icone' => 'fa-cloud-upload',
                ])
                <div class="acoes"><button class="botao" type="submit"><i class="fa fa-paper-plane"></i> Enviar documento</button></div>
            </form>
        </div>
    @endif

    <div class="bloco" style="padding:0;overflow:hidden">
        <div class="buriti-chat">
            <div class="buriti-chat-topo">
                <span class="avatar"><i class="fa fa-history"></i></span>
                <div>
                    <strong>Acompanhamento da inscrição</strong>
                    <small>Histórico em formato de conversa</small>
                </div>
            </div>
            <div class="buriti-chat-fios">
                @if(empty($timeline))
                    <div class="buriti-chat-vazio">Ainda sem eventos nesta inscrição.</div>
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
@endsection
