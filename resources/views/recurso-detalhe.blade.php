@extends('act::layout')
@section('titulo', 'Recurso #'.$recurso['id'])
@section('migalha', 'Recurso')
@section('icone-tela', 'fa-comments')
@section('titulo-tela', 'Conversa do recurso')
@section('lead')
    {{ $recurso['candidato'] }} · {{ $recurso['protocolo'] ?? '' }} · {{ \iEducar\Packages\BuritiContratacoesTemporarias\Services\RecursoProcesso::rotulo($recurso['status']) }}
@endsection
@section('acoes-topo')
    <a class="botao claro icone" href="{{ url('/contratacoes-temporarias/recursos?processo_id='.$recurso['processo_id']) }}" title="Recursos" aria-label="Recursos"><i aria-hidden="true" class="fa fa-list"></i></a>
@endsection

@section('conteudo')
    <div class="bloco" style="padding:0;overflow:hidden">
        <div class="buriti-chat">
            <div class="buriti-chat-topo">
                <span class="avatar">{{ mb_substr($recurso['candidato'] ?? 'C', 0, 1) }}</span>
                <div>
                    <strong>{{ $recurso['candidato'] }}</strong>
                    <small>Recurso do processo seletivo — trâmite institucional</small>
                </div>
            </div>
            <div class="buriti-chat-fios" id="chat-recurso">
                <div class="buriti-balao eles">
                    <span class="selo">Pedido do candidato</span>
                    <span class="quem">{{ $recurso['candidato'] }}</span>
                    <p class="texto">{{ $recurso['motivo'] }}</p>
                    @foreach($anexos ?? [] as $a)
                        <a class="anexo-chip" href="{{ url('/contratacoes-temporarias/anexo/'.$a['id']) }}">
                            <i class="fa fa-paperclip"></i> {{ $a['nome'] }}
                        </a>
                    @endforeach
                    <span class="quando">{{ $recurso['aberto_em'] ?? $recurso['created_at'] ?? '' }}</span>
                </div>

                @if(!empty($recurso['resposta']))
                    <div class="buriti-balao eu">
                        <span class="selo">Resposta da banca</span>
                        <span class="quem">{{ $recurso['respondido_por'] ?? 'Banca' }}</span>
                        <p class="texto">{{ $recurso['resposta'] }}</p>
                        @foreach($anexosResposta ?? [] as $a)
                            <a class="anexo-chip" href="{{ url('/contratacoes-temporarias/anexo/'.$a['id']) }}">
                                <i class="fa fa-paperclip"></i> {{ $a['nome'] }}
                            </a>
                        @endforeach
                        <span class="quando">{{ $recurso['respondido_em'] ?? '' }}</span>
                    </div>
                @else
                    <div class="buriti-chat-vazio">Aguardando a resposta da banca…</div>
                @endif
            </div>

            @if(!empty($podeResponder))
                <form class="buriti-chat-composer" method="post" action="{{ url('/contratacoes-temporarias/recursos/'.$recurso['id'].'/responder') }}" enctype="multipart/form-data">
                    @csrf
                    <div>
                        <label style="display:grid;gap:4px;font-size:.78rem;font-weight:700;color:#5b6b76;margin:0 0 6px">
                            Decisão
                            <select name="status" style="min-height:36px;border-radius:10px;border:1px solid #cfd8df;padding:6px 8px;font:inherit">
                                <option value="deferido">Deferir (aceitar o recurso)</option>
                                <option value="indeferido">Indeferir (manter a decisão)</option>
                                <option value="respondido">Só responder, sem mudar o resultado</option>
                            </select>
                        </label>
                        <label style="display:grid;gap:4px;font-size:.78rem;font-weight:700;color:#5b6b76;margin:0 0 6px">
                            Nova pontuação (se deferir)
                            <input type="number" step="0.5" min="0" name="nova_pontuacao" placeholder="Opcional" style="min-height:36px;border-radius:10px;border:1px solid #cfd8df;padding:6px 8px;font:inherit">
                        </label>
                        <textarea name="resposta" required placeholder="Escreva a resposta / fundamentação…" rows="3"></textarea>
                        @include('buriti-ui::campo-anexo', [
                            'campo' => 'anexo',
                            'rotulo' => 'Anexar PDF da resposta (opcional)',
                            'tituloAcao' => 'Soltar o PDF aqui',
                            'ajuda' => 'Anexe o PDF da manifestação.',
                            'accept' => 'application/pdf,.pdf',
                            'icone' => 'fa-file-pdf-o',
                        ])
                    </div>
                    <div class="composer-acoes">
                        <button class="botao-enviar" type="submit" title="Enviar resposta"><i class="fa fa-paper-plane"></i></button>
                    </div>
                </form>
            @endif
        </div>
    </div>
    <script>
    (function () {
        var f = document.getElementById('chat-recurso');
        if (f) f.scrollTop = f.scrollHeight;
    })();
    </script>
@endsection
