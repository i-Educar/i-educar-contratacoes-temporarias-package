<?php

namespace iEducar\Packages\BuritiContratacoesTemporarias\Providers;

use iEducar\Packages\BuritiContratacoesTemporarias\Services\ChamadaAct;
use Illuminate\Support\ServiceProvider;

class ContratacoesTemporariasServiceProvider extends ServiceProvider
{
    public const string PACKAGE = 'buriti/i-educar-contratacoes-temporarias-package';

    public function register(): void
    {
        $this->app->singleton(ChamadaAct::class);
    }

    public function boot(): void
    {
    }
}
