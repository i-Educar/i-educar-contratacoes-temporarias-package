@extends('act::portal.layout')
@section('titulo', 'Resultados publicados')

@section('conteudo')
    <h1>Resultados publicados</h1>
    <p class="lead">
        Baixe o PDF de cada edital com classificação e identificação parcial —
        publicidade administrativa com proteção de dados pessoais (LGPD).
        A lista completa não fica na interface do sistema.
    </p>

    @include('buriti-ui::setor-contato', ['setor' => $setor ?? 'act'])

    @include('buriti-ui::lista-resultados-pdf', [
        'titulo' => 'Editais com resultado disponível',
        'lead' => 'Disponível após resultado preliminar, final, homologação ou encerramento das fases.',
        'itens' => $resultadosPdf ?? [],
        'vazio' => 'Nenhum edital com resultado disponível para download neste momento.',
    ])

    <p class="acoes">
        <a class="botao claro" href="{{ url('/contratacoes-temporarias/candidato') }}"><i class="fa fa-home"></i> Voltar aos editais</a>
        <a class="botao claro" href="{{ url('/contratacoes-temporarias/candidato/protocolo') }}"><i class="fa fa-search"></i> Acompanhar protocolo</a>
    </p>
@endsection
