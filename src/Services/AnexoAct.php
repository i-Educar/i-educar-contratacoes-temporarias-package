<?php

namespace iEducar\Packages\BuritiContratacoesTemporarias\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class AnexoAct
{
    public const string DISK = 'local';

    public const string PREFIXO = 'act/anexos';

    public static function maxKb(): int
    {
        return max(256, (int) config('act.anexo_max_kb', 5120));
    }

    /**
     * @param  list<string>  $extensoesPermitidas
     * @return array{id:int,nome:string,path:string,extensao:string,tamanho:int}
     */
    public static function salvar(
        UploadedFile $file,
        int $processoId,
        string $contexto,
        ?int $contextoId = null,
        ?string $codigoDocumento = null,
        array $extensoesPermitidas = ['pdf'],
        ?string $autor = null,
    ): array {
        $ext = mb_strtolower($file->getClientOriginalExtension() ?: 'bin');
        $permitidas = array_map('strtolower', $extensoesPermitidas);
        if ($permitidas !== [] && ! in_array($ext, $permitidas, true)) {
            throw new \InvalidArgumentException('Extensão .'.$ext.' não permitida. Use: '.implode(', ', $permitidas));
        }
        if ($file->getSize() > self::maxKb() * 1024) {
            throw new \InvalidArgumentException('Arquivo excede '.self::maxKb().' KB.');
        }

        $nomeOriginal = $file->getClientOriginalName();
        $base = Str::slug(pathinfo($nomeOriginal, PATHINFO_FILENAME)) ?: 'anexo';
        $relativo = $file->storeAs(
            self::PREFIXO.'/'.$processoId.'/'.$contexto,
            Str::uuid()->toString().'-'.$base.'.'.$ext,
            self::DISK
        );
        if ($relativo === false) {
            throw new \RuntimeException('Falha ao gravar arquivo.');
        }

        $id = PocRegistro::gravar('anexo', $nomeOriginal, $contexto, [
            'processo_id' => $processoId,
            'contexto' => $contexto,
            'contexto_id' => $contextoId,
            'codigo_documento' => $codigoDocumento,
            'nome' => $nomeOriginal,
            'path' => $relativo,
            'extensao' => $ext,
            'mime' => (string) $file->getMimeType(),
            'tamanho' => (int) $file->getSize(),
            'autor' => $autor,
            'em' => now()->toIso8601String(),
        ]);

        return [
            'id' => $id,
            'nome' => $nomeOriginal,
            'path' => $relativo,
            'extensao' => $ext,
            'tamanho' => (int) $file->getSize(),
        ];
    }

    /**
     * @return list<array{id:int,nome:string,extensao:string,tamanho:int,codigo_documento:?string,contexto:string,contexto_id:?int}>
     */
    public static function listar(int $processoId, ?string $contexto = null, ?int $contextoId = null): array
    {
        $out = [];
        foreach (PocRegistro::listar('anexo', 800) as $r) {
            $p = $r['payload'];
            if ((int) ($p['processo_id'] ?? 0) !== $processoId) {
                continue;
            }
            if ($contexto !== null && (string) ($p['contexto'] ?? '') !== $contexto) {
                continue;
            }
            if ($contextoId !== null && (int) ($p['contexto_id'] ?? 0) !== $contextoId) {
                continue;
            }
            $out[] = [
                'id' => $r['id'],
                'nome' => (string) ($p['nome'] ?? $r['titulo']),
                'extensao' => (string) ($p['extensao'] ?? 'pdf'),
                'tamanho' => (int) ($p['tamanho'] ?? 0),
                'codigo_documento' => isset($p['codigo_documento']) ? (string) $p['codigo_documento'] : null,
                'contexto' => (string) ($p['contexto'] ?? ''),
                'contexto_id' => isset($p['contexto_id']) ? (int) $p['contexto_id'] : null,
            ];
        }

        return $out;
    }

    public static function caminhoAbsoluto(int $anexoId): ?string
    {
        $reg = PocRegistro::buscar($anexoId);
        if ($reg === null || $reg['tipo'] !== 'anexo') {
            return null;
        }
        $path = (string) ($reg['payload']['path'] ?? '');
        if ($path === '' || ! Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        return Storage::disk(self::DISK)->path($path);
    }

    public static function meta(int $anexoId): ?array
    {
        $reg = PocRegistro::buscar($anexoId);
        if ($reg === null || $reg['tipo'] !== 'anexo') {
            return null;
        }
        $p = $reg['payload'];

        return [
            'id' => $reg['id'],
            'nome' => (string) ($p['nome'] ?? $reg['titulo']),
            'mime' => (string) ($p['mime'] ?? 'application/octet-stream'),
            'processo_id' => (int) ($p['processo_id'] ?? 0),
        ];
    }
}
