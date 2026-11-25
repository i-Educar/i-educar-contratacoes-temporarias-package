<?php

namespace iEducar\Packages\BuritiContratacoesTemporarias\Support;

use iEducar\Packages\BuritiContratacoesTemporarias\Providers\ContratacoesTemporariasServiceProvider;
use iEducar\Packages\BuritiContratacoesTemporarias\Services\FluxoProcesso;
use iEducar\Packages\BuritiContratacoesTemporarias\Services\PocRegistro;
use Illuminate\Support\Facades\Schema;

/**
 * Botão de acesso externo no login: só aparece com edital em inscrição
 * (quando a flag exigir_inscricao_aberta estiver ligada).
 */
final class PortalAberto
{
    public static function url(): ?string
    {
        if (! self::pacoteDisponivel()) {
            return null;
        }

        $portal = url('/contratacoes-temporarias/candidato');

        if (! (bool) config('act.public.exigir_inscricao_aberta', true)) {
            return $portal;
        }

        return self::temInscricaoAberta() ? $portal : null;
    }

    public static function pacoteDisponivel(): bool
    {
        if (! class_exists(\App\Support\Tenancy\PackageTenant::class)) {
            return true;
        }

        return \App\Support\Tenancy\PackageTenant::allows(ContratacoesTemporariasServiceProvider::PACKAGE);
    }

    public static function temInscricaoAberta(): bool
    {
        if (! Schema::hasTable('act.poc_registro') && ! Schema::hasTable('poc_registro')) {
            // PoC pode gravar em schema genérico — tenta listar mesmo assim.
        }

        try {
            foreach (PocRegistro::listar('processo', 100) as $r) {
                $status = (string) (($r['payload']['status'] ?? '') ?: '');
                if (FluxoProcesso::podeInscrever($status)) {
                    return true;
                }
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
    }
}
