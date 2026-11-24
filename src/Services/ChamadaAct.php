<?php

namespace iEducar\Packages\BuritiContratacoesTemporarias\Services;

final class ChamadaAct
{
    /** @param list<array{candidato_id:int,classificacao:int,preferencias:list<int>}> $fila */
    public static function alocar(array $fila, array $vagasAbertas): array {
        $aloc=[]; $rest=$vagasAbertas;
        usort($fila, fn($a,$b)=>$a['classificacao']<=>$b['classificacao']);
        foreach ($fila as $c) {
            foreach ($c['preferencias'] as $vaga) {
                if (in_array($vaga, $rest, true)) {
                    $aloc[]=['candidato_id'=>$c['candidato_id'],'vaga_id'=>$vaga];
                    $rest=array_values(array_diff($rest,[$vaga]));
                    break;
                }
            }
        }
        return ['alocacoes'=>$aloc,'vagas_restantes'=>$rest];
    }
}
