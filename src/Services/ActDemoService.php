<?php

namespace iEducar\Packages\BuritiContratacoesTemporarias\Services;

use Illuminate\Support\Facades\DB;

final class ActDemoService
{
    public const DEMO_TAG = 'laguna-carga-v2';

    /**
     * @return array{processos:int,vagas:int,inscricoes:int,pulado:bool,mensagem:string}
     */
    public function executar(bool $force = false): array
    {
        $vazio = ['processos' => 0, 'vagas' => 0, 'inscricoes' => 0, 'pulado' => false, 'mensagem' => ''];
        if (! PocRegistro::disponivel()) {
            $vazio['mensagem'] = 'schema act ausente';

            return $vazio;
        }
        if (! $force && $this->jaPovoado()) {
            $vazio['pulado'] = true;
            $vazio['mensagem'] = 'já povoado (use --force)';

            return $vazio;
        }
        if ($force) {
            $this->limparDemo();
        }

        $escolas = $this->escolasRede();
        $cenarios = [
            [
                'titulo' => 'Edital ACT PEB I — Laguna 2026',
                'numero' => 'ACT-2026-001',
                'cargo' => 'PEB I',
                'status' => 'inscricao',
                'area' => 'Anos iniciais',
                'candidatos' => [
                    ['Elena Martins', '11122233301'],
                    ['Fábio Rocha', '22233344402'],
                    ['Gina Alves', '33344455503'],
                    ['Hugo Prado', '44455566604'],
                    ['Íris Campos', '55566677705'],
                ],
            ],
            [
                'titulo' => 'Edital ACT Professor de Matemática — 2026',
                'numero' => 'ACT-2026-002',
                'cargo' => 'Professor de Matemática',
                'status' => 'avaliacao',
                'area' => 'Anos finais',
                'candidatos' => [
                    ['João Pedro Lima', '66677788806'],
                    ['Karina Souza', '77788899907'],
                    ['Lucas Andrade', '88899900008'],
                ],
            ],
            [
                'titulo' => 'Edital Monitor de Transporte Escolar — 2026',
                'numero' => 'ACT-2026-003',
                'cargo' => 'Monitor de transporte',
                'status' => 'resultado',
                'area' => 'Transporte',
                'candidatos' => [
                    ['Marina Costa', '99900011109'],
                    ['Nélson Dias', '00011122210'],
                ],
            ],
            [
                'titulo' => 'Edital Auxiliar de Educação Infantil — 2026',
                'numero' => 'ACT-2026-004',
                'cargo' => 'Auxiliar de EI',
                'status' => 'aberto',
                'area' => 'Educação infantil',
                'candidatos' => [
                    ['Olívia Fernandes', '11223344511'],
                    ['Paulo Henrique', '22334455612'],
                    ['Queila Borges', '33445566713'],
                    ['Rafael Nunes', '44556677814'],
                ],
            ],
        ];

        $processos = 0;
        $vagas = 0;
        $insc = 0;

        foreach ($cenarios as $ci => $c) {
            $processoId = PocRegistro::gravar('processo', $c['titulo'], 'Demo SME · '.$c['area'], [
                'numero' => $c['numero'],
                'cargo' => $c['cargo'],
                'status' => $c['status'],
                'area' => $c['area'],
                'publicado_em' => now()->subDays(10 - $ci)->toDateString(),
                'inscricao_inicio' => now()->subDays(8)->toDateString(),
                'inscricao_fim' => now()->addDays(20)->toDateString(),
                'demo' => self::DEMO_TAG,
            ]);
            $processos++;

            $escolasDoEdital = array_slice($escolas, 0, min(4, count($escolas)));
            if ($escolasDoEdital === []) {
                $escolasDoEdital = ['EMEF Anita Garibaldi', 'EMEF Polo Norte', 'EMEI Lagoa'];
            }
            $vagaIds = [];
            foreach ($escolasDoEdital as $i => $escola) {
                $vid = $i + 1 + ($ci * 10);
                PocRegistro::gravar('vaga', $c['cargo'].' — '.$escola, 'Processo #'.$processoId, [
                    'vaga_id' => $vid,
                    'processo_id' => $processoId,
                    'cargo' => $c['cargo'],
                    'escola' => $escola,
                    'quantidade' => 1 + ($i % 2),
                    'demo' => self::DEMO_TAG,
                ]);
                $vagaIds[] = $vid;
                $vagas++;
            }

            foreach ($c['candidatos'] as $i => [$nome, $cpf]) {
                $prefs = array_slice($vagaIds, 0, min(3, count($vagaIds)));
                $id = ProtocoloInscricao::abrir($processoId, [
                    'nome' => $nome,
                    'cpf' => $cpf,
                    'email' => strtolower(str_replace(' ', '.', $nome)).'@demo.laguna.sc.gov.br',
                    'telefone' => '4899'.str_pad((string) (1000 + $ci * 10 + $i), 4, '0', STR_PAD_LEFT),
                    'autor' => 'Demo ACT',
                    'preferencias' => $prefs,
                ]);
                $reg = PocRegistro::buscar($id);
                if ($reg !== null) {
                    $p = $reg['payload'];
                    $p['demo'] = self::DEMO_TAG;
                    $p['status'] = match ($c['status']) {
                        'avaliacao' => $i === 0 ? 'classificado' : 'em_avaliacao',
                        'resultado' => $i === 0 ? 'admitido' : 'lista_espera',
                        default => 'inscrito',
                    };
                    PocRegistro::atualizarPayload($id, $p);
                }
                $insc++;
            }
        }

        return [
            'processos' => $processos,
            'vagas' => $vagas,
            'inscricoes' => $insc,
            'pulado' => false,
            'mensagem' => "processos={$processos} vagas={$vagas} inscricoes={$insc}",
        ];
    }

    /** @return list<string> */
    private function escolasRede(): array
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('pmieducar.escola')) {
                return [];
            }

            return DB::table('pmieducar.escola as e')
                ->join('cadastro.juridica as j', 'j.idpes', '=', 'e.ref_idpes')
                ->where('e.ativo', 1)
                ->orderBy('e.cod_escola')
                ->limit(8)
                ->pluck('j.fantasia')
                ->map(static fn ($n) => (string) $n)
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function jaPovoado(): bool
    {
        foreach (PocRegistro::listar('processo', 50) as $r) {
            if (($r['payload']['demo'] ?? null) === self::DEMO_TAG) {
                return true;
            }
        }

        return false;
    }

    private function limparDemo(): void
    {
        foreach (['processo', 'vaga', 'inscricao', 'candidato', 'protocolo_evento', 'resultado'] as $tipo) {
            foreach (PocRegistro::listar($tipo, 800) as $r) {
                $tag = $r['payload']['demo'] ?? null;
                if (in_array($tag, [self::DEMO_TAG, 'laguna-carga-v1'], true)) {
                    DB::table(PocRegistro::TABELA)->where('id', $r['id'])->delete();
                }
            }
        }
    }
}
