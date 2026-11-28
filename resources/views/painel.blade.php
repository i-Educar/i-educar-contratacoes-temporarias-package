@extends('act::layout')
@section('titulo', 'Início — Processo seletivo')
@section('migalha', 'Início')
@section('icone-tela', 'fa-briefcase')
@section('titulo-tela', 'Contratações temporárias')
@section('lead')
    Do edital ao resultado: inscrições (também pelo portal público), pontuação, recursos e distribuição de vagas.
@endsection
@section('acoes-topo')
    <a class="botao icone" href="{{ url('/contratacoes-temporarias/processos') }}" title="Processos" aria-label="Processos"><i aria-hidden="true" class="fa fa-gavel"></i></a>
    <a class="botao claro icone" href="{{ url('/contratacoes-temporarias/inscricoes') }}" title="Inscrições" aria-label="Inscrições"><i aria-hidden="true" class="fa fa-user-plus"></i></a>
    <a class="botao claro icone" href="{{ url('/contratacoes-temporarias/candidato') }}" target="_blank" rel="noopener" title="Portal candidato" aria-label="Portal candidato"><i aria-hidden="true" class="fa fa-external-link"></i></a>
@endsection

@section('conteudo')
    @include('buriti-ui::painel-corpo', [
        'kpis' => $kpis ?? [],
        'atalhos' => $atalhos ?? [],
        'destaques' => $destaques ?? [],
        'analise' => $analise ?? [],
        'analiseTitulo' => $analiseTitulo ?? 'Análise',
        'resumo' => $resumo ?? null,
        'fluxoTitulo' => $fluxoTitulo ?? 'Fluxo do módulo',
    ])
@endsection
