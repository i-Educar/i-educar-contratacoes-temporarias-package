<?php

namespace iEducar\Packages\BuritiContratacoesTemporarias\Services;

final class DocumentoRegra
{
    /**
     * @return list<array{id:int,processo_id:int,codigo:string,nome:string,obrigatorio:bool,pontuacao_max:float,extensoes:list<string>,descricao:string}>
     */
    public static function doProcesso(int $processoId): array
    {
        $out = [];
        foreach (PocRegistro::listar('documento_regra', 300) as $r) {
            $p = $r['payload'];
            if ((int) ($p['processo_id'] ?? 0) !== $processoId) {
                continue;
            }
            $out[] = self::map($r);
        }
        usort($out, static fn ($a, $b) => strcmp($a['codigo'], $b['codigo']));

        return $out;
    }

    /**
     * @param  list<string>  $extensoes
     */
    public static function gravar(
        int $processoId,
        string $codigo,
        string $nome,
        bool $obrigatorio,
        float $pontuacaoMax,
        array $extensoes = ['pdf'],
        string $descricao = '',
    ): int {
        $codigo = mb_strtoupper(trim($codigo));
        $extensoes = array_values(array_filter(array_map(
            static fn ($e) => mb_strtolower(trim((string) $e, ". \t")),
            $extensoes
        )));
        if ($extensoes === []) {
            $extensoes = ['pdf'];
        }

        return PocRegistro::gravar('documento_regra', $nome, $codigo.' · máx '.$pontuacaoMax, [
            'processo_id' => $processoId,
            'codigo' => $codigo,
            'nome' => $nome,
            'obrigatorio' => $obrigatorio,
            'pontuacao_max' => max(0, $pontuacaoMax),
            'extensoes' => $extensoes,
            'descricao' => $descricao,
            'ativo' => true,
        ]);
    }

    public static function seedPadrao(int $processoId): void
    {
        if (self::doProcesso($processoId) !== []) {
            return;
        }
        $padrao = [
            ['RG', 'Documento de identidade', true, 0, ['pdf', 'jpg', 'png'], 'Frente e verso'],
            ['CPF', 'CPF', true, 0, ['pdf'], ''],
            ['DIPLOMA', 'Diploma / comprovante de formação', true, 10, ['pdf'], 'Graduação ou habilitação'],
            ['POS', 'Pós-graduação', false, 5, ['pdf'], 'Especialização, mestrado ou doutorado'],
            ['EXP', 'Comprovante de experiência', false, 10, ['pdf'], 'Até 2 pontos por ano, máx 10'],
            ['CURSOS', 'Cursos de formação continuada', false, 5, ['pdf'], ''],
        ];
        foreach ($padrao as [$cod, $nome, $ob, $pts, $ext, $desc]) {
            self::gravar($processoId, $cod, $nome, $ob, (float) $pts, $ext, $desc);
        }
    }

    /**
     * @param  array{id:int,tipo:string,titulo:string,meta:?string,payload:array<string,mixed>,created_at:?string}  $r
     * @return array{id:int,processo_id:int,codigo:string,nome:string,obrigatorio:bool,pontuacao_max:float,extensoes:list<string>,descricao:string}
     */
    private static function map(array $r): array
    {
        $p = $r['payload'];

        return [
            'id' => $r['id'],
            'processo_id' => (int) ($p['processo_id'] ?? 0),
            'codigo' => (string) ($p['codigo'] ?? ''),
            'nome' => (string) ($p['nome'] ?? $r['titulo']),
            'obrigatorio' => (bool) ($p['obrigatorio'] ?? false),
            'pontuacao_max' => (float) ($p['pontuacao_max'] ?? 0),
            'extensoes' => array_map('strval', (array) ($p['extensoes'] ?? ['pdf'])),
            'descricao' => (string) ($p['descricao'] ?? ''),
        ];
    }
}
