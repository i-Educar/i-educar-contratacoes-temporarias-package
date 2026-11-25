@extends('act::layout')
@section('titulo', 'Início — Processo seletivo')
@section('migalha', 'Início')
@section('icone-tela', 'fa-briefcase')
@section('titulo-tela', 'Contratações temporárias')
@section('lead')
    Do edital ao resultado: inscrições (também pelo portal público), pontuação, recursos e distribuição de vagas.
@endsection
@section('acoes-topo')
    <a class="botao" href="{{ url('/contratacoes-temporarias/processos') }}"><i class="fa fa-gavel"></i> Processos</a>
    <a class="botao claro" href="{{ url('/contratacoes-temporarias/inscricoes') }}"><i class="fa fa-user-plus"></i> Inscrições</a>
    <a class="botao claro" href="{{ url('/contratacoes-temporarias/candidato') }}" target="_blank" rel="noopener"><i class="fa fa-external-link"></i> Portal candidato</a>
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
