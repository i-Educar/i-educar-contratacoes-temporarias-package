<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Acesso externo (login / home guest)
    |--------------------------------------------------------------------------
    |
    | aparecer_na_home  Mostra o botão na tela de login para candidatos de fora
    |                   da rede (inscrição sem conta no i-Educar).
    | exigir_inscricao_aberta  Só libera a URL quando houver edital em fase de
    |                          inscrição — espelha o portal do transporte.
    |
    */
    'public' => [
        'aparecer_na_home' => (bool) env('ACT_APARECER_NA_HOME', true),
        'exigir_inscricao_aberta' => (bool) env('ACT_EXIGIR_INSCRICAO_ABERTA', true),
        'titulo' => env('ACT_HOME_TITULO', 'Contratações temporárias'),
        'ordem' => (int) env('ACT_HOME_ORDEM', 65),
    ],

    /*
    |--------------------------------------------------------------------------
    | PoC operacional — ACT (Admissão de Caráter Temporário)
    |--------------------------------------------------------------------------
    |
    | chamada_aberta        Quando true, mostra o formulário "criar edital"
    |                       em /contratacoes-temporarias/processos e aceita
    |                       o POST correspondente.
    | sla_preferencias      Quantas preferências (1..N) o candidato pode
    |                       declarar ao se inscrever. Usado no formulário
    |                       e como tamanho máximo do array persistido.
    | seed_nucleo           Quando true (padrão), se poc_registro estiver
    |                       vazio, popula vagas e candidatos a partir de
    |                       pmieducar.escola / pmieducar.servidor (rede real).
    |
    */
    'chamada_aberta' => (bool) env('ACT_CHAMADA_ABERTA', true),
    'sla_preferencias' => (int) env('ACT_SLA_PREFERENCIAS', 3),
    'seed_nucleo' => (bool) env('ACT_SEED_NUCLEO', true),
    'anexo_max_kb' => (int) env('ACT_ANEXO_MAX_KB', 5120),
    'seed_documentos_padrao' => (bool) env('ACT_SEED_DOCS', true),
];
