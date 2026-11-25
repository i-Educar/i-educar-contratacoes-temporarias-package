@extends('act::layout')
@section('titulo', 'Resultado final')
@section('migalha', 'Resultado')
@section('icone-tela', 'fa-trophy')
@section('titulo-tela', 'Resultado do processo seletivo')
@section('lead')
    Monta a classificação como um ranking de campeonato: quem tem mais pontos sobe; preliminar ou final conforme a fase.
@endsection

@section('conteudo')
    @php
        $opcoesProcesso = collect($processos ?? [])->map(fn ($p) => [
            'id' => $p['id'],
            'nome' => $p['titulo'],
            'meta' => \iEducar\Packages\BuritiContratacoesTemporarias\Services\FluxoProcesso::rotulo($p['status'] ?? ''),
        ])->all();
    @endphp

    <div class="bloco">
        <form class="form-grid" method="get" action="{{ url('/contratacoes-temporarias/resultado') }}">
            @include('buriti-ui::select-busca', [
                'campo' => 'processo_id',
                'rotulo' => 'Edital',
                'icone' => 'fa-trophy',
                'opcoes' => $opcoesProcesso,
                'selecionado' => $processoId,
                'vazio' => '— escolher edital —',
                'placeholder' => 'Buscar edital…',
                'ajuda' => 'Processo cuja classificação você quer gerar ou consultar.',
                'onchange' => 'this.form.submit()',
                'colWide' => true,
                'tamanho' => min(6, max(3, count($opcoesProcesso) ?: 3)),
            ])
        </form>
        @if(!empty($podeGerar) && $processoId)
            <form method="post" action="{{ url('/contratacoes-temporarias/resultado/gerar') }}" style="margin-top:12px" class="form-grid">
                @csrf
                <input type="hidden" name="processo_id" value="{{ $processoId }}">
                @if(($processo['status'] ?? '') === 'resultado_preliminar')
                    <label>Publicar como final?
                        <select name="final">
                            <option value="0">Manter preliminar (ainda cabe recurso)</option>
                            <option value="1">Gerar resultado final</option>
                        </select>
                    </label>
                @endif
                <div class="acoes"><button class="botao" type="submit"><i class="fa fa-trophy"></i> Gerar / atualizar classificação</button></div>
            </form>
        @endif
    </div>

    <div class="bloco">
        <h2>Classificação ({{ count($itens ?? []) }})</h2>
        <table>
            <thead><tr><th>#</th><th>Candidato</th><th>Protocolo</th><th>Pontuação</th><th>Status</th></tr></thead>
            <tbody>
            @forelse(($itens ?? []) as $i)
                <tr>
                    <td>{{ $i['classificacao'] }}</td>
                    <td><a href="{{ url('/contratacoes-temporarias/inscricoes/'.$i['inscricao_id']) }}">{{ $i['nome'] }}</a></td>
                    <td>{{ $i['protocolo'] ?? '—' }}</td>
                    <td>{{ $i['pontuacao'] }}</td>
                    <td>{{ $i['status'] }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Sem classificação. Avalie as inscrições e gere o resultado.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
