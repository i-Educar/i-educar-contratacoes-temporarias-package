<?php

namespace iEducar\Packages\BuritiContratacoesTemporarias\Support;

final class Identidade
{
    public const COR = '#1d4ed8';
    public const DESTAQUE = '#2f6bff';
    public const COR_CLARA = '#e8eefc';
    public const ICONE = 'fa-briefcase';
    public const NOME = 'Contratações';

    public const DESCRICAO = 'Editais ACT · inscrição pública e classificação';

    /** @return array{cor:string,destaque:string,clara:string,icone:string,nome:string,descricao:string} */
    public static function tokens(): array
    {
        return [
            'cor' => self::COR,
            'destaque' => self::DESTAQUE,
            'clara' => self::COR_CLARA,
            'icone' => self::ICONE,
            'nome' => self::NOME,
            'descricao' => self::DESCRICAO,
        ];
    }
}
