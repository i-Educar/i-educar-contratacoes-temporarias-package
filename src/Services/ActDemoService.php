<?php

namespace iEducar\Packages\BuritiContratacoesTemporarias\Services;

use Illuminate\Support\Facades\DB;

final class ActDemoService
{
    public const DEMO_TAG = 'laguna-carga-v1';

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

        $processoId = PocRegistro::gravar('processo', 'Edital ACT PEB I — Laguna 2026', 'Demo SME', [
            'numero' => 'ACT-2026-001',
            'cargo' => 'PEB I',
            'status' => 'inscricao',
            'publicado_em' => now()->toDateString(),
            'demo' => self::DEMO_TAG,
        ]);

        $vagas = 0;
        foreach (['EMEF Anita Garibaldi', 'EMEF Polo Norte', 'EMEI Lagoa'] as $i => $escola) {
            PocRegistro::gravar('vaga', 'PEB I — '.$escola, 'Processo #'.$processoId, [
                'vaga_id' => $i + 1,
                'processo_id' => $processoId,
                'cargo' => 'PEB I',
                'escola' => $escola,
                'demo' => self::DEMO_TAG,
            ]);
            $vagas++;
        }

        $insc = 0;
        foreach ([
            ['Elena Martins', '11122233301'],
            ['Fábio Rocha', '22233344402'],
            ['Gina Alves', '33344455503'],
            ['Hugo Prado', '44455566604'],
        ] as $i => [$nome, $cpf]) {
            $id = ProtocoloInscricao::abrir($processoId, [
                'nome' => $nome,
                'cpf' => $cpf,
                'email' => strtolower(str_replace(' ', '.', $nome)).'@demo.laguna.sc.gov.br',
                'telefone' => '4899999'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'autor' => 'Demo ACT',
                'preferencias' => [1, 2, min(3, $vagas)],
            ]);
            $reg = PocRegistro::buscar($id);
            if ($reg !== null) {
                $p = $reg['payload'];
                $p['demo'] = self::DEMO_TAG;
                PocRegistro::atualizarPayload($id, $p);
            }
            $insc++;
        }

        return [
            'processos' => 1,
            'vagas' => $vagas,
            'inscricoes' => $insc,
            'pulado' => false,
            'mensagem' => "processos=1 vagas={$vagas} inscricoes={$insc}",
        ];
    }

    private function jaPovoado(): bool
    {
        foreach (PocRegistro::listar('processo', 30) as $r) {
            if (($r['payload']['demo'] ?? null) === self::DEMO_TAG) {
                return true;
            }
        }

        return false;
    }

    private function limparDemo(): void
    {
        foreach (['processo', 'vaga', 'inscricao', 'candidato', 'protocolo_evento', 'resultado'] as $tipo) {
            foreach (PocRegistro::listar($tipo, 500) as $r) {
                if (($r['payload']['demo'] ?? null) === self::DEMO_TAG) {
                    DB::table(PocRegistro::TABELA)->where('id', $r['id'])->delete();
                }
            }
        }
    }
}
