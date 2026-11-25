<?php

use App\Menu;
use App\Services\MenuCacheService;
use iEducar\Packages\BuritiContratacoesTemporarias\Support\Processos;
use iEducar\Packages\BuritiLagunaAmbiente\Support\PermissaoMatriz;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $raiz = Menu::query()->where('process', Processos::RAIZ)->first();
        if ($raiz === null) {
            return;
        }

        $decisao = Menu::query()->updateOrCreate(
            ['parent_id' => $raiz->getKey(), 'title' => 'Decisão'],
            ['order' => 5, 'type' => 2, 'process' => null, 'active' => true, 'link' => null],
        );

        Menu::query()->updateOrCreate(['process' => Processos::ADMITIR], [
            'parent_id' => $decisao->getKey(),
            'title' => 'Admitir / contratar',
            'description' => 'Contratar classificado e criar vínculos para quadro/diário',
            'link' => '/contratacoes-temporarias/classificados',
            'order' => 1,
            'type' => 3,
            'active' => true,
        ]);

        PermissaoMatriz::conceder([Processos::ADMITIR], [1, 2, 11]);

        if (class_exists(MenuCacheService::class)) {
            app(MenuCacheService::class)->flushAll();
        }
    }

    public function down(): void
    {
        //
    }
};
