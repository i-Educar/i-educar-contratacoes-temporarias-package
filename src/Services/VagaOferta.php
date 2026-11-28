<?php

namespace iEducar\Packages\BuritiContratacoesTemporarias\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Vagas/cargos vinculados a processos ACT, com dados da escola para o portal.
 *
 * @phpstan-type Vaga array{
 *   id:int,
 *   registro_id:int,
 *   processo_id:int,
 *   titulo:string,
 *   cargo:string,
 *   carga_horaria:string,
 *   quantidade:int,
 *   escola_id:?int,
 *   escola:string,
 *   endereco:string,
 *   segmento:string,
 *   turmas:string,
 *   detalhes:string
 * }
 */
final class VagaOferta
{
    /**
     * @return list<Vaga>
     */
    public static function doProcesso(int $processoId): array
    {
        $out = [];
        foreach (PocRegistro::listar('vaga', 300) as $r) {
            $p = $r['payload'];
            if (! empty($p['inativa'])) {
                continue;
            }
            $pid = (int) ($p['processo_id'] ?? 0);
            if ($processoId > 0 && $pid !== $processoId) {
                continue;
            }
            $out[] = self::normalizar($r);
        }

        usort($out, static fn (array $a, array $b): int => [$a['cargo'], $a['escola'], $a['id']] <=> [$b['cargo'], $b['escola'], $b['id']]);

        return $out;
    }

    /**
     * @return list<Vaga>
     */
    public static function todas(?int $processoId = null): array
    {
        return self::doProcesso($processoId ?? 0);
    }

    /**
     * Cargos distintos do processo (para vitrine ao candidato).
     *
     * @return list<array{cargo:string,quantidade:int,escolas:int}>
     */
    public static function cargosResumo(int $processoId): array
    {
        $mapa = [];
        foreach (self::doProcesso($processoId) as $v) {
            $c = $v['cargo'] !== '' ? $v['cargo'] : 'Cargo não informado';
            if (! isset($mapa[$c])) {
                $mapa[$c] = ['cargo' => $c, 'quantidade' => 0, 'escolas' => []];
            }
            $mapa[$c]['quantidade'] += max(1, $v['quantidade']);
            if ($v['escola'] !== '') {
                $mapa[$c]['escolas'][$v['escola']] = true;
            }
        }

        $out = [];
        foreach ($mapa as $row) {
            $out[] = [
                'cargo' => $row['cargo'],
                'quantidade' => (int) $row['quantidade'],
                'escolas' => count($row['escolas']),
            ];
        }

        return $out;
    }

    /**
     * @param  array{cargo:string,escola_id?:int,escola?:string,quantidade?:int,carga_horaria?:string,segmento?:string,turmas?:string,endereco?:string}  $dados
     */
    public static function cadastrar(int $processoId, array $dados): int
    {
        $cargo = trim((string) ($dados['cargo'] ?? ''));
        if ($cargo === '') {
            throw new \InvalidArgumentException('Informe o cargo da vaga.');
        }

        $escolaId = isset($dados['escola_id']) ? (int) $dados['escola_id'] : 0;
        $escolaNome = trim((string) ($dados['escola'] ?? ''));
        $endereco = trim((string) ($dados['endereco'] ?? ''));
        $segmento = trim((string) ($dados['segmento'] ?? ''));
        $turmas = trim((string) ($dados['turmas'] ?? ''));

        if ($escolaId > 0) {
            $info = self::detalheEscola($escolaId);
            if ($info !== null) {
                $escolaNome = $escolaNome !== '' ? $escolaNome : $info['nome'];
                $endereco = $endereco !== '' ? $endereco : $info['endereco'];
                $segmento = $segmento !== '' ? $segmento : $info['segmento'];
                $turmas = $turmas !== '' ? $turmas : $info['turmas'];
            }
        }

        $titulo = $cargo.($escolaNome !== '' ? ' — '.$escolaNome : '');
        $quantidade = max(1, (int) ($dados['quantidade'] ?? 1));
        $carga = trim((string) ($dados['carga_horaria'] ?? ''));

        $id = PocRegistro::gravar('vaga', $titulo, 'Processo #'.$processoId, [
            'processo_id' => $processoId,
            'vaga_id' => null,
            'cargo' => $cargo,
            'escola_id' => $escolaId > 0 ? $escolaId : null,
            'escola' => $escolaNome,
            'endereco' => $endereco,
            'segmento' => $segmento,
            'turmas' => $turmas,
            'quantidade' => $quantidade,
            'carga_horaria' => $carga,
        ]);
        PocRegistro::atualizarPayload($id, array_merge(
            PocRegistro::buscar($id)['payload'] ?? [],
            ['vaga_id' => $id]
        ));

        return $id;
    }

    public static function remover(int $registroId): bool
    {
        $r = PocRegistro::buscar($registroId);
        if ($r === null || ($r['tipo'] ?? '') !== 'vaga') {
            return false;
        }
        if (method_exists(PocRegistro::class, 'excluir')) {
            return (bool) PocRegistro::excluir($registroId);
        }
        // Fallback: marca inativa no payload
        $p = $r['payload'];
        $p['inativa'] = true;
        PocRegistro::atualizarPayload($registroId, $p, meta: 'Inativa');

        return true;
    }

    /**
     * Catálogo de escolas para o formulário de vagas.
     *
     * @return list<array{id:int,nome:string,endereco:string,segmento:string,turmas:string,meta:string}>
     */
    public static function escolasCatalogo(int $limite = 120): array
    {
        if (! Schema::hasTable('pmieducar.escola')) {
            return [];
        }

        try {
            $rows = DB::table('pmieducar.escola as e')
                ->join('cadastro.juridica as j', 'j.idpes', '=', 'e.ref_idpes')
                ->where('e.ativo', 1)
                ->orderBy('j.fantasia')
                ->limit($limite)
                ->get(['e.cod_escola', 'j.fantasia']);

            $out = [];
            foreach ($rows as $r) {
                $id = (int) $r->cod_escola;
                $nome = (string) $r->fantasia;
                $endereco = '';
                try {
                    if (Schema::hasTable('relatorio.view_dados_escola')) {
                        $v = DB::table('relatorio.view_dados_escola')->where('cod_escola', $id)->first(['logradouro', 'numero', 'bairro', 'municipio']);
                        if ($v) {
                            $endereco = implode(', ', array_filter([
                                trim((string) ($v->logradouro ?? '')),
                                ! empty($v->numero) ? 'nº '.$v->numero : null,
                                trim((string) ($v->bairro ?? '')),
                                trim((string) ($v->municipio ?? '')),
                            ]));
                        }
                    }
                } catch (Throwable) {
                }
                $segmento = '';
                try {
                    if (Schema::hasTable('pmieducar.escola_curso') && Schema::hasTable('pmieducar.curso')) {
                        $segmento = DB::table('pmieducar.escola_curso as ec')
                            ->join('pmieducar.curso as c', 'c.cod_curso', '=', 'ec.ref_cod_curso')
                            ->where('ec.ref_cod_escola', $id)
                            ->where('ec.ativo', 1)
                            ->orderBy('c.nm_curso')
                            ->limit(4)
                            ->pluck('c.nm_curso')
                            ->implode(', ');
                    }
                } catch (Throwable) {
                }
                $out[] = [
                    'id' => $id,
                    'nome' => $nome,
                    'endereco' => $endereco,
                    'segmento' => $segmento,
                    'turmas' => '',
                    'meta' => trim(implode(' · ', array_filter([$segmento !== '' ? $segmento : null, $endereco !== '' ? $endereco : null]))),
                ];
            }

            return $out;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array{nome:string,endereco:string,segmento:string,turmas:string}|null
     */
    public static function detalheEscola(int $escolaId): ?array
    {
        if ($escolaId < 1) {
            return null;
        }

        $nome = '';
        $endereco = '';
        $segmento = '';
        $turmas = '';

        try {
            if (Schema::hasTable('relatorio.view_dados_escola')) {
                $v = DB::table('relatorio.view_dados_escola')->where('cod_escola', $escolaId)->first();
                if ($v) {
                    $nome = (string) ($v->nome ?? '');
                    $partes = array_filter([
                        trim((string) ($v->logradouro ?? '')),
                        ! empty($v->numero) ? 'nº '.$v->numero : null,
                        trim((string) ($v->bairro ?? '')),
                        trim((string) ($v->municipio ?? '')),
                    ]);
                    $endereco = implode(', ', $partes);
                }
            }
        } catch (Throwable) {
        }

        if ($nome === '') {
            try {
                $row = DB::table('pmieducar.escola as e')
                    ->join('cadastro.juridica as j', 'j.idpes', '=', 'e.ref_idpes')
                    ->where('e.cod_escola', $escolaId)
                    ->first(['j.fantasia']);
                $nome = $row ? (string) $row->fantasia : '';
            } catch (Throwable) {
                return null;
            }
        }

        try {
            if (Schema::hasTable('pmieducar.escola_curso') && Schema::hasTable('pmieducar.curso')) {
                $cursos = DB::table('pmieducar.escola_curso as ec')
                    ->join('pmieducar.curso as c', 'c.cod_curso', '=', 'ec.ref_cod_curso')
                    ->where('ec.ref_cod_escola', $escolaId)
                    ->where('ec.ativo', 1)
                    ->where('c.ativo', 1)
                    ->orderBy('c.nm_curso')
                    ->limit(8)
                    ->pluck('c.nm_curso')
                    ->map(static fn ($n) => (string) $n)
                    ->all();
                $segmento = implode(', ', $cursos);
            }
        } catch (Throwable) {
        }

        try {
            if (Schema::hasTable('pmieducar.turma')) {
                $ano = (int) (DB::table('pmieducar.escola_ano_letivo')
                    ->where('ref_cod_escola', $escolaId)
                    ->where('andamento', 1)
                    ->orderByDesc('ano')
                    ->value('ano') ?: date('Y'));
                $lista = DB::table('pmieducar.turma')
                    ->where('ref_ref_cod_escola', $escolaId)
                    ->where('ativo', 1)
                    ->where('ano', $ano)
                    ->orderBy('nm_turma')
                    ->limit(10)
                    ->pluck('nm_turma')
                    ->map(static fn ($n) => (string) $n)
                    ->all();
                $turmas = implode(', ', $lista);
                if (count($lista) >= 10) {
                    $turmas .= '…';
                }
            }
        } catch (Throwable) {
        }

        if ($nome === '') {
            return null;
        }

        return [
            'nome' => $nome,
            'endereco' => $endereco,
            'segmento' => $segmento,
            'turmas' => $turmas,
        ];
    }

    /**
     * @param  array{id:int,titulo:string,tipo?:string,meta?:?string,payload:array<string,mixed>}  $r
     * @return Vaga
     */
    private static function normalizar(array $r): array
    {
        $p = $r['payload'];
        if (! empty($p['inativa'])) {
            // ainda listamos? skip via filtro externo — marcamos quantidade 0
        }
        $escolaId = isset($p['escola_id']) ? (int) $p['escola_id'] : (isset($p['cod_escola']) ? (int) $p['cod_escola'] : null);
        $escola = trim((string) ($p['escola'] ?? ''));
        $endereco = trim((string) ($p['endereco'] ?? ''));
        $segmento = trim((string) ($p['segmento'] ?? ''));
        $turmas = trim((string) ($p['turmas'] ?? ''));
        $cargo = trim((string) ($p['cargo'] ?? ''));

        if ($escolaId && ($endereco === '' || $segmento === '' || $turmas === '' || $escola === '')) {
            $info = self::detalheEscola($escolaId);
            if ($info !== null) {
                $escola = $escola !== '' ? $escola : $info['nome'];
                $endereco = $endereco !== '' ? $endereco : $info['endereco'];
                $segmento = $segmento !== '' ? $segmento : $info['segmento'];
                $turmas = $turmas !== '' ? $turmas : $info['turmas'];
            }
        }

        $titulo = (string) $r['titulo'];
        if ($cargo === '' && str_contains($titulo, '—')) {
            $cargo = trim(explode('—', $titulo, 2)[0]);
        }
        if ($escola === '' && str_contains($titulo, '—')) {
            $escola = trim(explode('—', $titulo, 2)[1] ?? '');
        }

        $detalhes = trim(implode(' · ', array_filter([
            $cargo !== '' ? $cargo : null,
            $segmento !== '' ? $segmento : null,
            $endereco !== '' ? $endereco : null,
        ])));

        return [
            'id' => (int) ($p['vaga_id'] ?? $r['id']),
            'registro_id' => (int) $r['id'],
            'processo_id' => (int) ($p['processo_id'] ?? 0),
            'titulo' => $titulo,
            'cargo' => $cargo,
            'carga_horaria' => trim((string) ($p['carga_horaria'] ?? '')),
            'quantidade' => max(0, (int) ($p['quantidade'] ?? 1)),
            'escola_id' => $escolaId ?: null,
            'escola' => $escola,
            'endereco' => $endereco,
            'segmento' => $segmento,
            'turmas' => $turmas,
            'detalhes' => $detalhes,
            'inativa' => ! empty($p['inativa']),
        ];
    }
}
