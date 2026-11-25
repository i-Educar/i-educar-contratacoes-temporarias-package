<?php

namespace iEducar\Packages\BuritiContratacoesTemporarias\Services;

final class ComissaoAvaliacao
{
    /**
     * @return list<array{id:int,processo_id:int,user_id:int,nome:string,papel:string,ativo:bool}>
     */
    public static function doProcesso(int $processoId): array
    {
        $out = [];
        foreach (PocRegistro::listar('comissao', 200) as $r) {
            $p = $r['payload'];
            if ((int) ($p['processo_id'] ?? 0) !== $processoId) {
                continue;
            }
            if (! ($p['ativo'] ?? true)) {
                continue;
            }
            $out[] = [
                'id' => $r['id'],
                'processo_id' => $processoId,
                'user_id' => (int) ($p['user_id'] ?? 0),
                'nome' => (string) ($p['nome'] ?? $r['titulo']),
                'papel' => (string) ($p['papel'] ?? 'Avaliador'),
                'ativo' => true,
            ];
        }

        return $out;
    }

    public static function adicionar(int $processoId, string $nome, string $papel = 'Avaliador', int $userId = 0): int
    {
        return PocRegistro::gravar('comissao', $nome, $papel, [
            'processo_id' => $processoId,
            'user_id' => $userId,
            'nome' => $nome,
            'papel' => $papel !== '' ? $papel : 'Avaliador',
            'ativo' => true,
        ]);
    }

    public static function remover(int $id): bool
    {
        $reg = PocRegistro::buscar($id);
        if ($reg === null || $reg['tipo'] !== 'comissao') {
            return false;
        }
        $p = $reg['payload'];
        $p['ativo'] = false;

        return PocRegistro::atualizarPayload($id, $p, meta: 'inativo');
    }

    public static function ehMembro(?int $userId, int $processoId): bool
    {
        if ($userId === null || $userId <= 0) {
            return false;
        }
        foreach (self::doProcesso($processoId) as $m) {
            if ($m['user_id'] === $userId) {
                return true;
            }
        }

        return false;
    }
}
