<?php

namespace iEducar\Packages\BuritiContratacoesTemporarias\Support;

final class MenuFluxo
{
    /**
     * @param  array<string, bool>  $permite
     * @return list<array{nome:string,itens:list<array{rotulo:string,url:string,icone:string,chave:string}>}>
     */
    public static function grupos(array $permite): array
    {
        $item = static function (string $chave, string $rotulo, string $url, string $icone) use ($permite): ?array {
            if (! ($permite[$chave] ?? true)) {
                return null;
            }

            return compact('chave', 'rotulo', 'url', 'icone');
        };
        $filtrar = static fn (array $itens): array => array_values(array_filter($itens));

        return array_values(array_filter([
            [
                'nome' => 'Processo seletivo',
                'itens' => $filtrar([
                    $item('painel', 'Início', '/contratacoes-temporarias', 'fa-th-large'),
                    $item('processos', 'Editais abertos', '/contratacoes-temporarias/processos', 'fa-gavel'),
                    $item('inscricoes', 'Inscrições', '/contratacoes-temporarias/inscricoes', 'fa-user-plus'),
                    $item('avaliacao', 'Pontuação e avaliação', '/contratacoes-temporarias/avaliacao', 'fa-calculator'),
                    $item('recursos', 'Recursos dos candidatos', '/contratacoes-temporarias/recursos', 'fa-balance-scale'),
                    $item('resultado', 'Resultado final', '/contratacoes-temporarias/resultado', 'fa-trophy'),
                ]),
            ],
            [
                'nome' => 'Consultas',
                'itens' => $filtrar([
                    $item('protocolo', 'Consultar protocolo', '/contratacoes-temporarias/protocolo', 'fa-barcode'),
                    $item('classificados', 'Lista de classificação', '/contratacoes-temporarias/classificados', 'fa-list-ol'),
                    $item('alocacao', 'Distribuir vagas', '/contratacoes-temporarias/alocacao', 'fa-exchange'),
                    $item('admitir', 'Admitir / contratar', '/contratacoes-temporarias/classificados', 'fa-handshake-o'),
                ]),
            ],
        ], static fn (array $g): bool => $g['itens'] !== []));
    }
}
