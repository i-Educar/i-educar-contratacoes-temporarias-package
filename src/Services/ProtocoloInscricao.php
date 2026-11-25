<?php

namespace iEducar\Packages\BuritiContratacoesTemporarias\Services;

final class ProtocoloInscricao
{
    public static function gerar(int $inscricaoId): string
    {
        return sprintf('ACT-%s-%06d', date('Y'), $inscricaoId);
    }

    /**
     * @param  array<string,mixed>  $dados
     */
    public static function abrir(int $processoId, array $dados): int
    {
        $nome = (string) ($dados['nome'] ?? '');
        $cpf = preg_replace('/\D+/', '', (string) ($dados['cpf'] ?? '')) ?? '';
        $id = PocRegistro::gravar('inscricao', $nome, $cpf, [
            'processo_id' => $processoId,
            'nome' => $nome,
            'cpf' => $cpf,
            'email' => (string) ($dados['email'] ?? ''),
            'telefone' => (string) ($dados['telefone'] ?? ''),
            'preferencias' => array_map('intval', (array) ($dados['preferencias'] ?? [])),
            'status' => 'inscrito',
            'pontuacao' => 0.0,
            'pontuacao_detalhe' => [],
            'classificacao' => null,
            'documentos' => [],
            'protocolo' => null,
            'inscrito_em' => now()->toIso8601String(),
            'observacao' => (string) ($dados['observacao'] ?? ''),
        ]);
        $protocolo = self::gerar($id);
        $reg = PocRegistro::buscar($id);
        if ($reg !== null) {
            $p = $reg['payload'];
            $p['protocolo'] = $protocolo;
            PocRegistro::atualizarPayload($id, $p, meta: $protocolo);
        }
        self::evento($processoId, $id, $protocolo, 'inscricao', 'Inscrição registrada', (string) ($dados['autor'] ?? $nome));

        return $id;
    }

    public static function evento(int $processoId, ?int $inscricaoId, ?string $protocolo, string $tipo, string $texto, string $autor): int
    {
        return PocRegistro::gravar('protocolo_evento', $texto, $protocolo, [
            'processo_id' => $processoId,
            'inscricao_id' => $inscricaoId,
            'protocolo' => $protocolo,
            'tipo' => $tipo,
            'texto' => $texto,
            'autor' => $autor,
            'em' => now()->toIso8601String(),
        ]);
    }

    /**
     * @return list<array{id:int,tipo:string,texto:string,autor:string,em:string,protocolo:?string,inscricao_id:?int}>
     */
    public static function timeline(?string $protocolo = null, ?int $inscricaoId = null, ?int $processoId = null): array
    {
        $out = [];
        foreach (PocRegistro::listar('protocolo_evento', 500) as $r) {
            $p = $r['payload'];
            if ($protocolo !== null && $protocolo !== '' && (string) ($p['protocolo'] ?? '') !== $protocolo) {
                continue;
            }
            if ($inscricaoId !== null && (int) ($p['inscricao_id'] ?? 0) !== $inscricaoId) {
                continue;
            }
            if ($processoId !== null && (int) ($p['processo_id'] ?? 0) !== $processoId) {
                continue;
            }
            $out[] = [
                'id' => $r['id'],
                'tipo' => (string) ($p['tipo'] ?? 'evento'),
                'texto' => (string) ($p['texto'] ?? $r['titulo']),
                'autor' => (string) ($p['autor'] ?? 'sistema'),
                'em' => (string) ($p['em'] ?? $r['created_at'] ?? ''),
                'protocolo' => isset($p['protocolo']) ? (string) $p['protocolo'] : null,
                'inscricao_id' => isset($p['inscricao_id']) ? (int) $p['inscricao_id'] : null,
            ];
        }
        usort($out, static fn ($a, $b) => strcmp($a['em'], $b['em']));

        return $out;
    }

    public static function buscarPorProtocolo(string $protocolo): ?array
    {
        $protocolo = mb_strtoupper(trim($protocolo));
        foreach (PocRegistro::listar('inscricao', 500) as $r) {
            if (mb_strtoupper((string) ($r['payload']['protocolo'] ?? '')) === $protocolo) {
                return $r;
            }
        }

        return null;
    }
}
