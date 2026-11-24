#!/usr/bin/env php
<?php
declare(strict_types=1);

require __DIR__.'/../src/Services/ChamadaAct.php';

$ok=0; $fail=0;
$assert = static function(bool $c, string $m) use (&$ok,&$fail): void {
  echo ($c? '  OK  ':' FAIL ').$m."\n";
  $c ? $ok++ : $fail++;
};

$r = \iEducar\Packages\BuritiContratacoesTemporarias\Services\ChamadaAct::alocar([
  ['candidato_id'=>1,'classificacao'=>1,'preferencias'=>[10,11]],
  ['candidato_id'=>2,'classificacao'=>2,'preferencias'=>[11,10]],
], [10,11]);
$assert(count($r['alocacoes'])===2, 'ACT aloca 2');
$assert($r['alocacoes'][0]['vaga_id']===10, 'primeiro pega preferência');

echo "\nResultado: $ok ok, $fail falhas\n";
exit($fail>0?1:0);
