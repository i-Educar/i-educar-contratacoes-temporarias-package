<?php

namespace iEducar\Packages\BuritiContratacoesTemporarias\Support;

final class Identidade
{
    public const COR = '#7c2d3a';
    public const DESTAQUE = '#a33d4d';
    public const COR_CLARA = '#f8e8eb';
    public const ICONE = 'fa-briefcase';
    public const NOME = 'Contratações temporárias';

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
