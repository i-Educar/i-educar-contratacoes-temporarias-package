<?php

namespace iEducar\Packages\BuritiContratacoesTemporarias\Providers;

use Illuminate\Support\ServiceProvider;

class ContratacoesTemporariasServiceProvider extends ServiceProvider
{
    public const string PACKAGE = 'buriti/i-educar-contratacoes-temporarias-package';

    public function register(): void
    {
        // Services são value objects — instanciar com `new` nos callers (não bind no container).
    }

    public function boot(): void
    {
    }
}
