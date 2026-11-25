<?php

namespace iEducar\Packages\BuritiContratacoesTemporarias\Services;

use App\Models\LegacyPerson;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Admite/contrata classificado: pessoa + servidor + alocação + (opcional) usuário professor.
 * Não apaga classificação — apenas marca status=contratado no payload.
 */
final class AdmissaoAct
{
    /**
     * @param  array{escola_id?:int,ano?:int,criar_usuario?:bool}  $opcoes
     * @return array{servidor_id:int,escola_id:int,alocacao:bool,usuario_id:?int,ja_contratado:bool}
     */
    public static function admitir(int $inscricaoId, array $opcoes = []): array
    {
        $reg = PocRegistro::buscar($inscricaoId);
        if ($reg === null || $reg['tipo'] !== 'inscricao') {
            throw new RuntimeException('Inscrição não encontrada.');
        }
        $p = $reg['payload'];
        if (($p['status'] ?? '') === 'contratado' && ! empty($p['servidor_id'])) {
            return [
                'servidor_id' => (int) $p['servidor_id'],
                'escola_id' => (int) ($p['escola_contratacao'] ?? 0),
                'alocacao' => true,
                'usuario_id' => isset($p['usuario_id']) ? (int) $p['usuario_id'] : null,
                'ja_contratado' => true,
            ];
        }

        $nome = trim((string) ($p['nome'] ?? $reg['titulo'] ?? ''));
        $cpfRaw = preg_replace('/\D+/', '', (string) ($p['cpf'] ?? '')) ?: '';
        if ($nome === '' || strlen($cpfRaw) < 11) {
            throw new RuntimeException('Inscrição sem nome/CPF válidos para admissão.');
        }
        $cpf = (int) $cpfRaw;

        $inst = (int) (DB::table('pmieducar.instituicao')->orderBy('cod_instituicao')->value('cod_instituicao') ?: 1);
        $ano = (int) ($opcoes['ano'] ?? (DB::table('pmieducar.escola_ano_letivo')->where('andamento', 1)->orderByDesc('ano')->value('ano') ?: date('Y')));
        $escolaId = (int) ($opcoes['escola_id'] ?? self::escolaPreferida($p) ?: self::primeiraEscola());
        if ($escolaId < 1) {
            throw new RuntimeException('Informe a escola de lotação para contratar.');
        }

        $idpes = (int) (DB::table('cadastro.fisica')->where('cpf', $cpf)->value('idpes') ?: 0);
        if ($idpes < 1) {
            $pessoa = LegacyPerson::query()->create([
                'nome' => $nome,
                'tipo' => 'F',
                'situacao' => 'A',
                'origem_gravacao' => 'M',
                'operacao' => 'I',
                'data_cad' => now(),
            ]);
            $idpes = (int) $pessoa->getKey();
            DB::table('cadastro.fisica')->insert([
                'idpes' => $idpes,
                'data_nasc' => '1990-01-01',
                'sexo' => 'F',
                'cpf' => $cpf,
                'ativo' => 1,
                'operacao' => 'I',
                'origem_gravacao' => 'M',
                'data_cad' => now(),
            ]);
        } else {
            DB::table('cadastro.pessoa')->where('idpes', $idpes)->update(['nome' => $nome]);
        }

        DB::table('pmieducar.servidor')->updateOrInsert(
            ['cod_servidor' => $idpes],
            [
                'ref_cod_instituicao' => $inst,
                'carga_horaria' => 40,
                'data_cadastro' => now(),
                'ativo' => 1,
            ]
        );

        $alocou = self::garantirAlocacao($idpes, $inst, $escolaId, $ano);
        self::garantirHabilitacaoDocente($idpes, $inst, $escolaId);
        $usuarioId = null;
        if (($opcoes['criar_usuario'] ?? true) === true) {
            $usuarioId = self::garantirUsuarioProfessor($idpes, $nome, $cpfRaw, $escolaId);
        }

        // Evento de auditoria (não apaga inscrição)
        ProtocoloInscricao::evento(
            (int) ($p['processo_id'] ?? 0),
            $inscricaoId,
            isset($p['protocolo']) ? (string) $p['protocolo'] : null,
            'admissao',
            "Contratado como servidor #{$idpes} na escola {$escolaId}",
            'sistema'
        );

        $p['status'] = 'contratado';
        $p['servidor_id'] = $idpes;
        $p['escola_contratacao'] = $escolaId;
        $p['contratado_em'] = now()->toIso8601String();
        $p['usuario_id'] = $usuarioId;
        PocRegistro::atualizarPayload($inscricaoId, $p, $nome, 'contratado · servidor '.$idpes);

        return [
            'servidor_id' => $idpes,
            'escola_id' => $escolaId,
            'alocacao' => $alocou,
            'usuario_id' => $usuarioId,
            'ja_contratado' => false,
        ];
    }

    /** @param array<string,mixed> $payload */
    private static function escolaPreferida(array $payload): int
    {
        $prefs = (array) ($payload['preferencias'] ?? []);
        foreach ($prefs as $pref) {
            if (is_array($pref) && ! empty($pref['escola_id'])) {
                return (int) $pref['escola_id'];
            }
            if (is_numeric($pref)) {
                return (int) $pref;
            }
        }

        return 0;
    }

    private static function primeiraEscola(): int
    {
        return (int) (DB::table('pmieducar.escola')->where('ativo', 1)->orderBy('cod_escola')->value('cod_escola') ?: 0);
    }

    private static function garantirAlocacao(int $idpes, int $inst, int $escola, int $ano): bool
    {
        if (! Schema::hasTable('pmieducar.servidor_alocacao')) {
            return false;
        }
        $exists = DB::table('pmieducar.servidor_alocacao')
            ->where('ref_cod_servidor', $idpes)
            ->where('ref_cod_escola', $escola)
            ->where('ano', $ano)
            ->where('ativo', 1)
            ->exists();
        if ($exists) {
            return true;
        }
        $base = [
            'ref_ref_cod_instituicao' => $inst,
            'ref_cod_escola' => $escola,
            'ref_cod_servidor' => $idpes,
            'ref_usuario_cad' => (int) (auth()->id() ?: 1),
            'periodo' => 3,
            'ano' => $ano,
            'data_admissao' => now()->toDateString(),
            'data_cadastro' => now(),
            'ativo' => 1,
        ];
        foreach (['40:00:00', 40] as $carga) {
            try {
                DB::table('pmieducar.servidor_alocacao')->insert($base + ['carga_horaria' => $carga]);

                return true;
            } catch (\Throwable) {
            }
        }

        return false;
    }

    private static function garantirUsuarioProfessor(int $idpes, string $nome, string $cpf, int $escolaId): ?int
    {
        $tipoProfessor = (int) (DB::table('pmieducar.tipo_usuario')->where('nivel', 4)->where('ativo', 1)->orderBy('cod_tipo_usuario')->value('cod_tipo_usuario') ?: 5);
        $inst = (int) (DB::table('pmieducar.instituicao')->orderBy('cod_instituicao')->value('cod_instituicao') ?: 1);
        $matricula = $cpf !== '' ? $cpf : ('prof'.$idpes);
        $senha = Hash::make($cpf !== '' ? $cpf : 'mudar123');

        // Login i-Educar: portal.funcionario (matricula/senha)
        if (Schema::hasTable('portal.funcionario')) {
            $ja = DB::table('portal.funcionario')->where('ref_cod_pessoa_fj', $idpes)->exists();
            if (! $ja) {
                try {
                    DB::table('portal.funcionario')->insert([
                        'ref_cod_pessoa_fj' => $idpes,
                        'matricula' => $matricula,
                        'senha' => $senha,
                        'ativo' => 1,
                        'email' => $matricula.'@laguna.local',
                        'force_reset_password' => true,
                        'data_troca_senha' => now()->toDateString(),
                    ]);
                } catch (\Throwable) {
                    // matricula duplicada — tenta variante
                    try {
                        DB::table('portal.funcionario')->insert([
                            'ref_cod_pessoa_fj' => $idpes,
                            'matricula' => 'p'.$idpes,
                            'senha' => $senha,
                            'ativo' => 1,
                            'email' => 'p'.$idpes.'@laguna.local',
                            'force_reset_password' => true,
                        ]);
                    } catch (\Throwable) {
                    }
                }
            } else {
                DB::table('portal.funcionario')->where('ref_cod_pessoa_fj', $idpes)->update(['ativo' => 1]);
            }
        }

        if (Schema::hasTable('pmieducar.usuario')) {
            $existente = DB::table('pmieducar.usuario')->where('cod_usuario', $idpes)->exists();
            if (! $existente) {
                try {
                    DB::table('pmieducar.usuario')->insert([
                        'cod_usuario' => $idpes,
                        'ref_cod_instituicao' => $inst,
                        'ref_cod_tipo_usuario' => $tipoProfessor,
                        'ref_funcionario_cad' => 1,
                        'data_cadastro' => now(),
                        'ativo' => 1,
                    ]);
                } catch (\Throwable) {
                    return null;
                }
            } else {
                DB::table('pmieducar.usuario')->where('cod_usuario', $idpes)->update([
                    'ativo' => 1,
                    'ref_cod_tipo_usuario' => $tipoProfessor,
                ]);
            }
        }

        if (Schema::hasTable('users')) {
            $email = $matricula.'@laguna.local';
            $u = DB::table('users')->where('email', $email)->first();
            if (! $u) {
                try {
                    DB::table('users')->insert([
                        'name' => $nome,
                        'email' => $email,
                        'password' => $senha,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } catch (\Throwable) {
                }
            }
        }

        if (Schema::hasTable('pmieducar.escola_usuario')) {
            $has = DB::table('pmieducar.escola_usuario')
                ->where('ref_cod_usuario', $idpes)
                ->where('ref_cod_escola', $escolaId)
                ->exists();
            if (! $has) {
                try {
                    DB::table('pmieducar.escola_usuario')->insert([
                        'ref_cod_usuario' => $idpes,
                        'ref_cod_escola' => $escolaId,
                        'escola_atual' => 1,
                    ]);
                } catch (\Throwable) {
                }
            }
        }

        return $idpes;
    }

    private static function garantirHabilitacaoDocente(int $idpes, int $inst, int $escolaId): void
    {
        if (! Schema::hasTable('pmieducar.servidor_funcao') || ! Schema::hasTable('pmieducar.funcao')) {
            return;
        }
        $funcao = (int) (DB::table('pmieducar.funcao')->where('ativo', 1)->where('professor', 1)->orderBy('cod_funcao')->value('cod_funcao')
            ?: DB::table('pmieducar.funcao')->where('ativo', 1)->orderBy('cod_funcao')->value('cod_funcao')
            ?: 0);
        if ($funcao < 1) {
            try {
                $funcao = (int) DB::table('pmieducar.funcao')->insertGetId([
                    'ref_usuario_cad' => (int) (auth()->id() ?: 1),
                    'nm_funcao' => 'Professor',
                    'abreviatura' => 'Prof',
                    'professor' => 1,
                    'data_cadastro' => now(),
                    'ativo' => 1,
                    'ref_cod_instituicao' => $inst,
                ], 'cod_funcao');
            } catch (\Throwable) {
                return;
            }
        }
        if ($funcao < 1) {
            return;
        }

        $sfId = DB::table('pmieducar.servidor_funcao')
            ->where('ref_cod_servidor', $idpes)
            ->where('ref_cod_funcao', $funcao)
            ->value('cod_servidor_funcao');
        if (! $sfId) {
            try {
                $sfId = DB::table('pmieducar.servidor_funcao')->insertGetId([
                    'ref_ref_cod_instituicao' => $inst,
                    'ref_cod_servidor' => $idpes,
                    'ref_cod_funcao' => $funcao,
                ], 'cod_servidor_funcao');
            } catch (\Throwable) {
                return;
            }
        }

        if (! Schema::hasTable('pmieducar.servidor_disciplina')) {
            return;
        }
        $cursos = collect();
        if (Schema::hasTable('pmieducar.escola_curso')) {
            $cursos = DB::table('pmieducar.escola_curso')
                ->where('ref_cod_escola', $escolaId)
                ->where('ativo', 1)
                ->limit(5)
                ->pluck('ref_cod_curso');
        }
        if ($cursos->isEmpty() && Schema::hasTable('pmieducar.curso')) {
            $cursos = DB::table('pmieducar.curso')->where('ativo', 1)->orderBy('cod_curso')->limit(3)->pluck('cod_curso');
        }
        $disciplinas = DB::table('modules.componente_curricular')->orderBy('id')->limit(12)->pluck('id');
        foreach ($cursos as $curso) {
            foreach ($disciplinas as $disc) {
                try {
                    $exists = DB::table('pmieducar.servidor_disciplina')
                        ->where('ref_cod_servidor', $idpes)
                        ->where('ref_cod_disciplina', (int) $disc)
                        ->where('ref_cod_curso', (int) $curso)
                        ->exists();
                    if ($exists) {
                        continue;
                    }
                    // FK: ref_cod_funcao → servidor_funcao.cod_servidor_funcao
                    DB::table('pmieducar.servidor_disciplina')->insert([
                        'ref_cod_disciplina' => (int) $disc,
                        'ref_ref_cod_instituicao' => $inst,
                        'ref_cod_servidor' => $idpes,
                        'ref_cod_curso' => (int) $curso,
                        'ref_cod_funcao' => (int) $sfId,
                    ]);
                } catch (\Throwable) {
                }
            }
        }
    }
}
