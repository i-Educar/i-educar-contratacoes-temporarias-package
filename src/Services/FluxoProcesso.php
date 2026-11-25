<?php

namespace iEducar\Packages\BuritiContratacoesTemporarias\Services;

/**
 * Ciclo do processo seletivo ACT.
 */
final class FluxoProcesso
{
    public const RASCUNHO = 'rascunho';

    public const INSCRICOES = 'inscricoes_abertas';

    public const INSCRICOES_FECHADAS = 'inscricoes_fechadas';

    public const AVALIACAO = 'em_avaliacao';

    public const PRELIMINAR = 'resultado_preliminar';

    public const RECURSOS = 'recursos';

    public const FINAL = 'resultado_final';

    public const HOMOLOGADO = 'homologado';

    public const ENCERRADO = 'encerrado';

    /** @return list<string> */
    public static function todos(): array
    {
        return [
            self::RASCUNHO,
            self::INSCRICOES,
            self::INSCRICOES_FECHADAS,
            self::AVALIACAO,
            self::PRELIMINAR,
            self::RECURSOS,
            self::FINAL,
            self::HOMOLOGADO,
            self::ENCERRADO,
        ];
    }

    public static function rotulo(string $status): string
    {
        return match ($status) {
            self::INSCRICOES => 'Inscrições abertas',
            self::INSCRICOES_FECHADAS => 'Inscrições fechadas',
            self::AVALIACAO => 'Em avaliação',
            self::PRELIMINAR => 'Resultado preliminar',
            self::RECURSOS => 'Fase de recursos',
            self::FINAL => 'Resultado final',
            self::HOMOLOGADO => 'Homologado',
            self::ENCERRADO => 'Encerrado',
            default => 'Rascunho',
        };
    }

    public static function tom(string $status): string
    {
        return match ($status) {
            self::HOMOLOGADO, self::FINAL => 'ok',
            self::ENCERRADO => 'fail',
            self::RECURSOS, self::PRELIMINAR, self::AVALIACAO => 'warn',
            self::INSCRICOES => 'ok',
            default => 'warn',
        };
    }

    /** @return list<string> */
    public static function proximos(string $atual): array
    {
        return match ($atual) {
            self::RASCUNHO => [self::INSCRICOES],
            self::INSCRICOES => [self::INSCRICOES_FECHADAS, self::RASCUNHO],
            self::INSCRICOES_FECHADAS => [self::AVALIACAO, self::INSCRICOES],
            self::AVALIACAO => [self::PRELIMINAR],
            self::PRELIMINAR => [self::RECURSOS, self::FINAL],
            self::RECURSOS => [self::FINAL],
            self::FINAL => [self::HOMOLOGADO],
            self::HOMOLOGADO => [self::ENCERRADO],
            default => [],
        };
    }

    public static function podeInscrever(string $status): bool
    {
        return $status === self::INSCRICOES;
    }

    public static function podeAvaliar(string $status): bool
    {
        return in_array($status, [self::AVALIACAO, self::PRELIMINAR, self::RECURSOS], true);
    }

    public static function podeRecurso(string $status): bool
    {
        return in_array($status, [self::PRELIMINAR, self::RECURSOS], true);
    }

    public static function podeResponderRecurso(string $status): bool
    {
        return in_array($status, [self::RECURSOS, self::PRELIMINAR], true);
    }

    public static function podeGerarResultado(string $status): bool
    {
        return in_array($status, [self::AVALIACAO, self::PRELIMINAR, self::RECURSOS, self::FINAL], true);
    }
}
