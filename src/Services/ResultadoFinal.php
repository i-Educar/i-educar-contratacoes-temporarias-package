<?php

namespace iEducar\Packages\BuritiContratacoesTemporarias\Services;

final class ResultadoFinal
{
    /**
     * Calcula classificação a partir das pontuações das inscrições.
     *
     * @return list<array{inscricao_id:int,nome:string,protocolo:?string,pontuacao:float,classificacao:int,status:string}>
     */
    public static function calcular(int $processoId, bool $persistir = true): array
    {
        $lista = [];
        foreach (PocRegistro::listar('inscricao', 500) as $r) {
            $p = $r['payload'];
            if ((int) ($p['processo_id'] ?? 0) !== $processoId) {
                continue;
            }
            if (in_array((string) ($p['status'] ?? ''), ['eliminado', 'indeferido'], true)) {
                continue;
            }
            $lista[] = [
                'inscricao_id' => $r['id'],
                'nome' => (string) ($p['nome'] ?? $r['titulo']),
                'protocolo' => isset($p['protocolo']) ? (string) $p['protocolo'] : null,
                'pontuacao' => (float) ($p['pontuacao'] ?? 0),
                'classificacao' => 0,
                'status' => (string) ($p['status'] ?? 'inscrito'),
                'reg' => $r,
            ];
        }

        usort($lista, static function (array $a, array $b): int {
            if ($a['pontuacao'] === $b['pontuacao']) {
                return $a['inscricao_id'] <=> $b['inscricao_id'];
            }

            return $b['pontuacao'] <=> $a['pontuacao'];
        });

        $rank = 1;
        $out = [];
        foreach ($lista as $item) {
            $item['classificacao'] = $rank;
            if ($persistir) {
                $p = $item['reg']['payload'];
                $p['classificacao'] = $rank;
                $p['status'] = 'classificado';
                PocRegistro::atualizarPayload($item['inscricao_id'], $p);
            }
            unset($item['reg']);
            $out[] = $item;
            $rank++;
        }

        if ($persistir) {
            PocRegistro::gravar('resultado', 'Resultado processo #'.$processoId, now()->toDateTimeString(), [
                'processo_id' => $processoId,
                'gerado_em' => now()->toIso8601String(),
                'itens' => $out,
                'tipo' => 'classificacao',
            ]);
            ProtocoloInscricao::evento($processoId, null, null, 'resultado', 'Resultado gerado com '.count($out).' classificado(s)', 'sistema');
        }

        return $out;
    }

    /**
     * @return list<array{inscricao_id:int,nome:string,protocolo:?string,pontuacao:float,classificacao:int,status:string}>
     */
    public static function ultimo(int $processoId): array
    {
        foreach (PocRegistro::listar('resultado', 50) as $r) {
            $p = $r['payload'];
            if ((int) ($p['processo_id'] ?? 0) === $processoId) {
                return array_map(static function (array $i): array {
                    return [
                        'inscricao_id' => (int) ($i['inscricao_id'] ?? 0),
                        'nome' => (string) ($i['nome'] ?? ''),
                        'protocolo' => isset($i['protocolo']) ? (string) $i['protocolo'] : null,
                        'pontuacao' => (float) ($i['pontuacao'] ?? 0),
                        'classificacao' => (int) ($i['classificacao'] ?? 0),
                        'status' => (string) ($i['status'] ?? 'classificado'),
                    ];
                }, (array) ($p['itens'] ?? []));
            }
        }

        return self::calcular($processoId, false);
    }
}
