<?php

namespace iEducar\Packages\BuritiContratacoesTemporarias\Support;

final class Processos
{
    public const int RAIZ = 882000;

    public const int PAINEL = 882001;

    public const int PROCESSOS = 882002;

    public const int CLASSIFICADOS = 882003;

    public const int ALOCACAO = 882004;

    public const int INSCRICOES = 882005;

    public const int AVALIACAO = 882006;

    public const int RECURSOS = 882007;

    public const int RESULTADO = 882008;

    public const int PROTOCOLO = 882009;

    public const int COMISSAO = 882010;

    public const int ADMITIR = 882011;

    /** @return array<int, string> */
    public static function titulos(): array
    {
        return [
            self::RAIZ => 'Contratações',
            self::PAINEL => 'Painel',
            self::PROCESSOS => 'Processos seletivos',
            self::CLASSIFICADOS => 'Classificados',
            self::ALOCACAO => 'Alocação',
            self::INSCRICOES => 'Inscrições',
            self::AVALIACAO => 'Avaliação',
            self::RECURSOS => 'Recursos',
            self::RESULTADO => 'Resultado final',
            self::PROTOCOLO => 'Protocolo',
            self::COMISSAO => 'Comissão',
            self::ADMITIR => 'Admitir / contratar',
        ];
    }

    /** @return list<int> */
    public static function todos(): array
    {
        return array_keys(self::titulos());
    }
}
