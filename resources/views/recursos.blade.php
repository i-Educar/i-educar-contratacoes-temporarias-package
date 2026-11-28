@extends('act::layout')
@section('titulo', 'Recursos')
@section('migalha', 'Recursos')
@section('icone-tela', 'fa-balance-scale')
@section('titulo-tela', 'Recursos dos candidatos')
@section('lead')
    O candidato envia o pedido com PDF; a banca registra manifestações oficiais no protocolo do recurso.
@endsection

@section('conteudo')
    @php
        $opcoesProcesso = collect($processos ?? [])->map(fn ($p) => [
            'id' => $p['id'],
            'nome' => $p['titulo'],
            'meta' => \iEducar\Packages\BuritiContratacoesTemporarias\Services\FluxoProcesso::rotulo($p['status'] ?? ''),
        ])->all();
        $opcoesInscricao = collect($inscricoes ?? [])->map(fn ($i) => [
            'id' => $i['id'],
            'nome' => ($i['protocolo'] ?? '#'.$i['id']).' · '.$i['nome'],
            'meta' => ($i['pontuacao'] ?? 0).' pts',
        ])->all();
    @endphp

    <div class="buriti-filtro-bloco">
        <h2><i class="fa fa-filter"></i> Edital</h2>
        <form class="buriti-filtro" method="get" action="{{ url('/contratacoes-temporarias/recursos') }}">
            @include('buriti-ui::select-busca', [
                'campo' => 'processo_id',
                'rotulo' => 'Edital',
                'icone' => 'fa-gavel',
                'opcoes' => $opcoesProcesso,
                'selecionado' => $processoId,
                'vazio' => 'Escolher edital',
                'onchange' => 'this.form.submit()',
                'compacto' => true,
            ])
        </form>
    </div>

    @if(!empty($podeAbrir))
        <div class="bloco">
            <h2><i class="fa fa-plus"></i> Interpor recurso</h2>
            <form class="form-grid" method="post" action="{{ url('/contratacoes-temporarias/recursos') }}" enctype="multipart/form-data">
                @csrf
                @include('buriti-ui::select-busca', [
                    'campo' => 'inscricao_id',
                    'rotulo' => 'Inscrição do candidato',
                    'icone' => 'fa-user',
                    'opcoes' => $opcoesInscricao,
                    'selecionado' => null,
                    'vazio' => '— selecionar inscrição —',
                    'placeholder' => 'Busque pelo protocolo ou nome…',
                    'ajuda' => 'Quem está pedindo revisão da nota ou da classificação.',
                    'obrigatorio' => true,
                    'colWide' => true,
                    'tamanho' => min(7, max(4, count($opcoesInscricao) ?: 4)),
                ])
                <label class="col-wide">Motivo / fundamentação <textarea name="motivo" required placeholder="Explique o pedido com suas palavras…"></textarea></label>
                @include('buriti-ui::campo-anexo', [
                    'campo' => 'anexo',
                    'rotulo' => 'Anexar PDF (opcional)',
                    'tituloAcao' => 'Soltar o PDF do recurso aqui',
                    'ajuda' => 'Anexe o PDF do recurso. Toque ou arraste o arquivo.',
                    'accept' => 'application/pdf,.pdf',
                    'icone' => 'fa-file-pdf-o',
                ])
                <div class="acoes"><button class="botao" type="submit"><i class="fa fa-paper-plane"></i> Enviar recurso</button></div>
            </form>
        </div>
    @endif

    <div class="bloco">
        <h2>Recursos ({{ count($recursos ?? []) }})</h2>
        @if(empty($recursos))
            <p style="margin:0;color:var(--suave)">Nenhum recurso neste processo.</p>
        @else
            <table>
                <thead><tr><th>Candidato</th><th>Protocolo</th><th>Status</th><th>Aberto em</th><th></th></tr></thead>
                <tbody>
                @foreach($recursos as $r)
                    <tr>
                        <td>{{ $r['candidato'] }}</td>
                        <td>{{ $r['protocolo'] ?? '—' }}</td>
                        <td><span class="badge {{ $r['status'] === 'deferido' ? 'ok' : ($r['status'] === 'aberto' ? 'warn' : 'fail') }}">{{ \iEducar\Packages\BuritiContratacoesTemporarias\Services\RecursoProcesso::rotulo($r['status']) }}</span></td>
                        <td>{{ $r['aberto_em'] }}</td>
                        <td><a class="botao claro" href="{{ url('/contratacoes-temporarias/recursos/'.$r['id']) }}">Abrir</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
