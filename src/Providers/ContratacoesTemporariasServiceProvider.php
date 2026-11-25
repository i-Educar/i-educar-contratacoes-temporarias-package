<?php

namespace iEducar\Packages\BuritiContratacoesTemporarias\Providers;

use App\Support\PublicModuleRegistry;
use iEducar\Packages\BuritiContratacoesTemporarias\Console\ActDemoCommand;
use iEducar\Packages\BuritiContratacoesTemporarias\Support\Identidade;
use iEducar\Packages\BuritiContratacoesTemporarias\Support\MenuFluxo;
use iEducar\Packages\BuritiContratacoesTemporarias\Support\PortalAberto;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ContratacoesTemporariasServiceProvider extends ServiceProvider
{
    public const string PACKAGE = 'buriti/i-educar-contratacoes-temporarias-package';

    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__, 2).'/config/act.php', 'act');
    }

    public function boot(): void
    {
        if (class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\BuritiUi::class)) {
            \iEducar\Packages\BuritiLagunaAmbiente\Support\BuritiUi::registrar();
        }
        $root = dirname(__DIR__, 2);
        $this->loadViewsFrom($root.'/resources/views', 'act');

        PublicModuleRegistry::register('act-candidato', [
            'package' => self::PACKAGE,
            'titulo' => (string) config('act.public.titulo', 'Contratações temporárias'),
            'descricao' => 'Portal do candidato — inscrição em editais ACT (também para quem não é da rede)',
            'url' => static fn (): ?string => PortalAberto::url(),
            'icone' => Identidade::ICONE,
            'css_class' => 'public-module',
            'aparecer_na_home' => (bool) config('act.public.aparecer_na_home', true),
            'ordem' => (int) config('act.public.ordem', 65),
        ]);

        if ($this->app->runningInConsole()) {
            if (\App\Support\Tenancy\PackageTenant::allows(self::PACKAGE)) {
                $this->loadMigrationsFrom($root.'/database/migrations');
            }
            $this->commands([ActDemoCommand::class]);
        }

        // Portal público (candidato) — só middleware web, sem menu institucional
        Route::middleware(['web'])->group($root.'/routes/portal.php');

        Route::middleware([
            'web',
            'ieducar.navigation',
            'ieducar.footer',
            'ieducar.xssbypass',
            'ieducar.suspended',
            'auth',
            'ieducar.checkresetpassword',
        ])->group($root.'/routes/web.php');

        View::composer('act::*', function ($view): void {
            $view->with('identidade', Identidade::tokens());
            $view->with('menuGrupos', MenuFluxo::grupos([
                'painel' => true,
                'processos' => true,
                'inscricoes' => true,
                'avaliacao' => true,
                'recursos' => true,
                'resultado' => true,
                'protocolo' => true,
                'classificados' => true,
                'alocacao' => true,
                'admitir' => true,
            ]));
        });
    }
}
