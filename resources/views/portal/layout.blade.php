<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Contratações temporárias')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ url('/intranet/styles/font-awesome.css') }}">
    <style>
        :root {
            --cor: {{ $identidade['cor'] ?? '#7c2d3a' }};
            --destaque: {{ $identidade['destaque'] ?? '#a33d4d' }};
            --clara: {{ $identidade['clara'] ?? '#f8e8eb' }};
            --fundo: #f3f0f1;
            --tinta: #1a242b;
            --suave: #5b6b76;
            --linha: #d5c8cb;
            --ok: #17663f;
            --ok-fundo: #e5f6ec;
            --erro: #9b2c2c;
            --erro-fundo: #fdeceb;
            --ambar: #b86e00;
            --cor-tela: var(--cor);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh;
            font-family: "Manrope", "Segoe UI", sans-serif; color: var(--tinta);
            background:
                radial-gradient(900px 360px at 10% -10%, color-mix(in srgb, var(--clara) 85%, transparent), transparent 55%),
                linear-gradient(180deg, #faf7f8, var(--fundo));
            line-height: 1.5;
        }
        header { background: linear-gradient(105deg, var(--cor), var(--destaque)); color: #fff; }
        .faixa { height: 6px; background: color-mix(in srgb, #f3c15b 70%, var(--destaque)); }
        .marca { display: flex; gap: 12px; align-items: center; color: #fff; text-decoration: none; min-width: 0; }
        .marca .ico {
            width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center;
            background: rgba(255,255,255,.16); font-size: 1.15rem; flex: none;
        }
        .marca strong { display: block; font-size: 1.05rem; letter-spacing: -.01em; }
        .marca small { display: block; opacity: .88; font-weight: 500; }
        .portal-nav a {
            display: inline-flex; align-items: center; gap: 8px;
            color: #fff; text-decoration: none; border: 1px solid rgba(255,255,255,.35);
            border-radius: 999px; padding: 8px 14px; font-weight: 700; font-size: .88rem;
        }
        .portal-nav a:hover { background: rgba(255,255,255,.12); }
        h1 { margin: 0 0 8px; font-size: clamp(1.35rem, 2.6vw, 1.9rem); line-height: 1.2; letter-spacing: -.02em; }
        .lead { margin: 0 0 20px; font-size: 1.02rem; color: var(--suave); max-width: 52rem; }
        .cartao {
            background: #fff; border: 1px solid var(--linha); border-radius: 16px;
            padding: 18px 20px; margin: 0 0 14px; box-shadow: 0 10px 24px rgba(44, 18, 24, .05);
            border-top: 3px solid var(--cor);
        }
        .cartao h2 { margin: 0 0 10px; font-size: 1.08rem; display: flex; align-items: center; gap: 8px; }
        .passo { background: #fff; border: 1px solid var(--linha); border-radius: 14px; padding: 14px 16px; }
        .passo i {
            width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center;
            background: var(--clara); color: var(--cor); margin-bottom: 8px;
        }
        .passo strong { display: block; margin-bottom: 4px; }
        .passo p { margin: 0; color: var(--suave); font-size: .9rem; }
        .botao, button.botao {
            display: inline-flex; gap: 8px; align-items: center; background: var(--cor); color: #fff;
            border: 0; border-radius: 10px; padding: 10px 16px; font: inherit; font-weight: 700;
            text-decoration: none; cursor: pointer;
        }
        .botao.claro { background: #fff; color: var(--cor); border: 1px solid color-mix(in srgb, var(--cor) 35%, var(--linha)); }
        .acoes { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; }
        .flash { border-radius: 12px; padding: 12px 14px; margin: 0 0 16px; }
        .flash.ok { background: var(--ok-fundo); color: var(--ok); }
        .flash.erro { background: var(--erro-fundo); color: var(--erro); }
        .flash.warn { background: #fff6e8; color: var(--ambar); }
        .badge {
            display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 999px;
            font-size: .75rem; font-weight: 700; background: var(--clara); color: var(--cor);
        }
        .badge.ok { background: var(--ok-fundo); color: var(--ok); }
        .badge.warn { background: #fff6e8; color: var(--ambar); }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; align-items: end; }
        .form-grid label { display: grid; gap: 4px; font-size: .82rem; font-weight: 700; color: var(--suave); }
        .form-grid input, .form-grid select, .form-grid textarea {
            min-height: 40px; padding: 8px 10px; border: 1px solid var(--linha); border-radius: 10px;
            font: inherit; background: #fff; color: var(--tinta); width: 100%;
        }
        .form-grid textarea { min-height: 80px; resize: vertical; }
        .form-grid .col-wide { grid-column: 1 / -1; }
        .protocolo-grande { font-size: 1.55rem; letter-spacing: .04em; font-weight: 800; margin: 8px 0; color: var(--cor); }
        footer.rodape-portal {
            background: #fff; border-top: 1px solid var(--linha); color: var(--suave);
            padding: 16px 0; font-size: .82rem; text-align: center;
        }
    </style>
    @include('buriti-ui::portal-estilos')
    @include('buriti-ui::estilos')
</head>
<body class="portal-shell">
    <header>
        <div class="faixa"></div>
        <div class="portal-topo-inner">
            <a class="marca" href="{{ url('/contratacoes-temporarias/candidato') }}">
                <span class="ico" aria-hidden="true"><i class="fa {{ $identidade['icone'] ?? 'fa-briefcase' }}"></i></span>
                <span>
                    <strong>{{ $municipio ?? 'Rede municipal' }}</strong>
                    <small>{{ $identidade['nome'] ?? 'Contratações temporárias' }} · inscrição</small>
                </span>
            </a>
            <nav class="portal-nav" aria-label="Portal do candidato">
                <a href="{{ url('/contratacoes-temporarias/candidato') }}"><i class="fa fa-home"></i> Editais</a>
                <a href="{{ url('/contratacoes-temporarias/candidato/protocolo') }}"><i class="fa fa-search"></i> Acompanhar</a>
                <a href="{{ url('/login') }}"><i class="fa fa-sign-in"></i> Área da SME</a>
            </nav>
        </div>
    </header>
    <main class="portal-main">
        @if(session('ok')) <p class="flash ok">{{ session('ok') }}</p> @endif
        @if(session('erro')) <p class="flash erro">{{ session('erro') }}</p> @endif
        @yield('conteudo')
    </main>
    <footer class="rodape-portal">
        <div class="portal-rodape-inner">
            Portal do candidato · {{ $identidade['descricao'] ?? 'Editais e inscrições' }} · não é necessário login da rede
        </div>
    </footer>
</body>
</html>
