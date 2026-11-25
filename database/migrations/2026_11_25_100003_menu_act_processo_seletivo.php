<?php

use App\Menu;
use App\Services\MenuCacheService;
use iEducar\Packages\BuritiContratacoesTemporarias\Support\Processos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $raiz = Menu::query()->where('process', Processos::RAIZ)->first();
        if ($raiz === null) {
            return;
        }

        Menu::query()->updateOrCreate(['process' => Processos::RAIZ], [
            'description' => 'Processo seletivo: documentos, comissão, inscrição, recurso, protocolo e resultado',
            'title' => 'Contratações temporárias',
        ]);

        $links = [
            [Processos::PROCESSOS, 'Editais / configuração', '/contratacoes-temporarias/processos', 'Fases, documentos e comissão', 2],
            [Processos::INSCRICOES, 'Inscrições', '/contratacoes-temporarias/inscricoes', 'Protocolo e anexos', 3],
            [Processos::AVALIACAO, 'Avaliação', '/contratacoes-temporarias/avaliacao', 'Pontuação por regra', 4],
            [Processos::RECURSOS, 'Recursos', '/contratacoes-temporarias/recursos', 'Interposição e resposta', 5],
            [Processos::RESULTADO, 'Resultado final', '/contratacoes-temporarias/resultado', 'Classificação', 6],
            [Processos::PROTOCOLO, 'Protocolo', '/contratacoes-temporarias/protocolo', 'Acompanhamento', 7],
            [Processos::COMISSAO, 'Comissão', '/contratacoes-temporarias/processos', 'Acesso à comissão de avaliação', 8],
            [Processos::CLASSIFICADOS, 'Classificação', '/contratacoes-temporarias/classificados', 'Lista pública', 9],
            [Processos::ALOCACAO, 'Alocação', '/contratacoes-temporarias/alocacao', 'Vagas × preferências', 10],
        ];

        foreach ($links as [$processo, $titulo, $link, $descricao, $ordem]) {
            Menu::query()->updateOrCreate(['process' => $processo], [
                'parent_id' => $raiz->getKey(),
                'title' => $titulo,
                'description' => $descricao,
                'link' => $link,
                'order' => $ordem,
                'type' => 3,
                'active' => true,
            ]);
        }

        $tipos = DB::table('pmieducar.tipo_usuario')->where('ativo', 1)->get(['cod_tipo_usuario']);
        foreach ($tipos as $tipo) {
            foreach (Processos::todos() as $processo) {
                $menuId = Menu::query()->where('process', $processo)->value('id');
                if ($menuId === null) {
                    continue;
                }
                DB::table('pmieducar.menu_tipo_usuario')->updateOrInsert(
                    ['ref_cod_tipo_usuario' => $tipo->cod_tipo_usuario, 'menu_id' => $menuId],
                    ['cadastra' => 1, 'visualiza' => 1, 'exclui' => 0],
                );
            }
        }

        if (class_exists(MenuCacheService::class)) {
            app(MenuCacheService::class)->flushAll();
        }
    }

    public function down(): void
    {
        foreach ([Processos::INSCRICOES, Processos::AVALIACAO, Processos::RECURSOS, Processos::RESULTADO, Processos::PROTOCOLO, Processos::COMISSAO] as $processo) {
            $menu = Menu::query()->where('process', $processo)->first();
            if ($menu === null) {
                continue;
            }
            DB::table('pmieducar.menu_tipo_usuario')->where('menu_id', $menu->getKey())->delete();
            $menu->delete();
        }
    }
};
