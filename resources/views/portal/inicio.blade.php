@extends('act::portal.layout')
@section('titulo', 'Editais abertos — Contratações')

@section('conteudo')
    <h1>Inscreva-se nos editais abertos</h1>
    <p class="lead">
        Como escolher uma vaga de emprego: leia o edital, envie seus documentos e guarde o protocolo.
        Pessoas de fora da rede municipal também podem se candidatar por aqui.
    </p>

    <div class="portal-passos">
        <article class="passo">
            <i class="fa fa-file-text-o" aria-hidden="true"></i>
            <strong>1. Escolha o edital</strong>
            <p>Veja o prazo e a área (PEB, apoio, etc.).</p>
        </article>
        <article class="passo">
            <i class="fa fa-upload" aria-hidden="true"></i>
            <strong>2. Envie os papéis</strong>
            <p>Diploma, comprovantes e o que o edital pedir — como anexar no WhatsApp.</p>
        </article>
        <article class="passo">
            <i class="fa fa-ticket" aria-hidden="true"></i>
            <strong>3. Guarde o protocolo</strong>
            <p>Com ele você acompanha a inscrição e o resultado.</p>
        </article>
    </div>

    <section class="cartao">
        <h2><i class="fa fa-unlock"></i> Com inscrição aberta agora</h2>
        @forelse(($abertos ?? []) as $p)
            <article class="portal-edital-linha" style="padding:12px 0;border-top:1px solid var(--linha)">
                <div>
                    <strong style="display:block">{{ $p['titulo'] }}</strong>
                    <span style="color:var(--suave);font-size:.9rem">
                        {{ $p['numero'] ?: 'Edital' }}
                        · {{ $p['inscricao_inicio'] ?? '—' }} a {{ $p['inscricao_fim'] ?? '—' }}
                    </span>
                    @if(!empty($p['descricao']))
                        <p style="margin:6px 0 0;color:var(--suave);font-size:.9rem">{{ \Illuminate\Support\Str::limit($p['descricao'], 180) }}</p>
                    @endif
                </div>
                <a class="botao" href="{{ url('/contratacoes-temporarias/candidato/inscrever/'.$p['id']) }}">
                    <i class="fa fa-pencil"></i> Quero me inscrever
                </a>
            </article>
        @empty
            <p class="flash warn" style="margin:0">Nenhum edital com inscrição aberta neste momento. Volte depois ou use «Acompanhar» se já tiver protocolo.</p>
        @endforelse
    </section>

    @if(!empty($outros))
        <section class="cartao">
            <h2><i class="fa fa-history"></i> Outros processos (consulta)</h2>
            <ul style="margin:0;padding-left:18px;color:var(--suave)">
                @foreach($outros as $p)
                    <li style="margin:6px 0">
                        <strong style="color:var(--tinta)">{{ $p['titulo'] }}</strong>
                        — <span class="badge warn">{{ \iEducar\Packages\BuritiContratacoesTemporarias\Services\FluxoProcesso::rotulo($p['status']) }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection
