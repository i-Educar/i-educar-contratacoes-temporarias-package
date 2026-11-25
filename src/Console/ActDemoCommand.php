<?php

namespace iEducar\Packages\BuritiContratacoesTemporarias\Console;

use iEducar\Packages\BuritiContratacoesTemporarias\Services\ActDemoService;
use Illuminate\Console\Command;

class ActDemoCommand extends Command
{
    protected $signature = 'act:demo {--force : Recria processo/vagas/inscrições demo}';

    protected $description = 'PoC — povoa processo ACT com vagas e inscrições';

    public function handle(ActDemoService $demo): int
    {
        $this->info('Contratações temporárias · carga demo');
        $r = $demo->executar((bool) $this->option('force'));
        if ($r['pulado']) {
            $this->warn('  '.$r['mensagem']);

            return self::SUCCESS;
        }
        if (str_contains($r['mensagem'], 'ausente')) {
            $this->error('  '.$r['mensagem']);

            return self::FAILURE;
        }
        $this->line('  '.$r['mensagem']);

        return self::SUCCESS;
    }
}
