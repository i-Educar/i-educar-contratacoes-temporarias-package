<?php

namespace iEducar\Packages\BuritiContratacoesTemporarias\Services;

final class RecursoProcesso
{
    public const ABERTO = 'aberto';

    public const RESPONDIDO = 'respondido';

    public const DEFERIDO = 'deferido';

    public const INDEFERIDO = 'indeferido';

    /**
     * @return list<array{id:int,processo_id:int,inscricao_id:int,protocolo:?string,candidato:string,motivo:string,status:string,resposta:?string,anexo_ids:list<int>,resposta_anexo_ids:list<int>}>
     */
    public static function doProcesso(int $processoId): array
    {
        $out = [];
        foreach (PocRegistro::listar('recurso', 300) as $r) {
            $p = $r['payload'];
            if ((int) ($p['processo_id'] ?? 0) !== $processoId) {
                continue;
            }
            $out[] = [
                'id' => $r['id'],
                'processo_id' => $processoId,
                'inscricao_id' => (int) ($p['inscricao_id'] ?? 0),
                'protocolo' => isset($p['protocolo']) ? (string) $p['protocolo'] : null,
                'candidato' => (string) ($p['candidato'] ?? $r['titulo']),
                'motivo' => (string) ($p['motivo'] ?? ''),
                'status' => (string) ($p['status'] ?? self::ABERTO),
                'resposta' => isset($p['resposta']) ? (string) $p['resposta'] : null,
                'anexo_ids' => array_map('intval', (array) ($p['anexo_ids'] ?? [])),
                'resposta_anexo_ids' => array_map('intval', (array) ($p['resposta_anexo_ids'] ?? [])),
                'aberto_em' => (string) ($p['aberto_em'] ?? $r['created_at'] ?? ''),
                'respondido_em' => isset($p['respondido_em']) ? (string) $p['respondido_em'] : null,
                'respondido_por' => isset($p['respondido_por']) ? (string) $p['respondido_por'] : null,
            ];
        }

        return $out;
    }

    public static function abrir(int $processoId, int $inscricaoId, string $candidato, string $motivo, ?string $protocolo, string $autor): int
    {
        $id = PocRegistro::gravar('recurso', $candidato, $protocolo, [
            'processo_id' => $processoId,
            'inscricao_id' => $inscricaoId,
            'protocolo' => $protocolo,
            'candidato' => $candidato,
            'motivo' => $motivo,
            'status' => self::ABERTO,
            'resposta' => null,
            'anexo_ids' => [],
            'resposta_anexo_ids' => [],
            'aberto_em' => now()->toIso8601String(),
            'autor' => $autor,
        ]);
        ProtocoloInscricao::evento($processoId, $inscricaoId, $protocolo, 'recurso', 'Recurso interposto', $autor);

        return $id;
    }

    public static function responder(int $recursoId, string $resposta, string $status, string $autor, array $anexoIds = []): bool
    {
        $reg = PocRegistro::buscar($recursoId);
        if ($reg === null || $reg['tipo'] !== 'recurso') {
            return false;
        }
        if (! in_array($status, [self::RESPONDIDO, self::DEFERIDO, self::INDEFERIDO], true)) {
            $status = self::RESPONDIDO;
        }
        $p = $reg['payload'];
        $p['resposta'] = $resposta;
        $p['status'] = $status;
        $p['respondido_em'] = now()->toIso8601String();
        $p['respondido_por'] = $autor;
        $p['resposta_anexo_ids'] = array_map('intval', $anexoIds);
        PocRegistro::atualizarPayload($recursoId, $p, meta: $status);
        ProtocoloInscricao::evento(
            (int) $p['processo_id'],
            (int) ($p['inscricao_id'] ?? 0),
            isset($p['protocolo']) ? (string) $p['protocolo'] : null,
            'resposta_recurso',
            'Recurso '.$status.': '.mb_substr($resposta, 0, 100),
            $autor
        );

        return true;
    }

    public static function rotulo(string $status): string
    {
        return match ($status) {
            self::DEFERIDO => 'Deferido',
            self::INDEFERIDO => 'Indeferido',
            self::RESPONDIDO => 'Respondido',
            default => 'Aberto',
        };
    }
}
