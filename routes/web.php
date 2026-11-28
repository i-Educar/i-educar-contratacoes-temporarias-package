<?php

use iEducar\Packages\BuritiContratacoesTemporarias\Http\Controllers\ActController;
use Illuminate\Support\Facades\Route;

Route::prefix('contratacoes-temporarias')->group(function (): void {
    Route::get('/', [ActController::class, 'index']);

    Route::get('/processos', [ActController::class, 'processos']);
    Route::post('/processos', [ActController::class, 'salvarProcesso']);
    Route::get('/processos/{id}', [ActController::class, 'detalheProcesso'])->whereNumber('id');
    Route::post('/processos/{id}/status', [ActController::class, 'alterarStatusProcesso'])->whereNumber('id');
    Route::post('/processos/{id}/documentos', [ActController::class, 'salvarDocumento'])->whereNumber('id');
    Route::post('/processos/{id}/vagas', [ActController::class, 'salvarVaga'])->whereNumber('id');
    Route::post('/processos/{id}/vagas/{vagaId}/remover', [ActController::class, 'removerVaga'])->whereNumber('id')->whereNumber('vagaId');
    Route::post('/processos/{id}/comissao', [ActController::class, 'salvarComissao'])->whereNumber('id');
    Route::post('/processos/{id}/comissao/{membroId}/remover', [ActController::class, 'removerComissao'])->whereNumber('id')->whereNumber('membroId');

    Route::get('/inscricoes', [ActController::class, 'inscricoes']);
    Route::post('/inscricoes', [ActController::class, 'salvarInscricao']);
    Route::get('/inscricoes/{id}', [ActController::class, 'detalheInscricao'])->whereNumber('id');
    Route::post('/inscricoes/{id}/anexo', [ActController::class, 'anexarDocumentoInscricao'])->whereNumber('id');

    Route::get('/avaliacao', [ActController::class, 'avaliacao']);
    Route::post('/avaliacao/{id}', [ActController::class, 'salvarAvaliacao'])->whereNumber('id');

    Route::get('/recursos', [ActController::class, 'recursos']);
    Route::post('/recursos', [ActController::class, 'abrirRecurso']);
    Route::get('/recursos/{id}', [ActController::class, 'detalheRecurso'])->whereNumber('id');
    Route::post('/recursos/{id}/responder', [ActController::class, 'responderRecurso'])->whereNumber('id');

    Route::get('/resultado', [ActController::class, 'resultado']);
    Route::post('/resultado/gerar', [ActController::class, 'gerarResultado']);

    Route::get('/protocolo', [ActController::class, 'protocolo']);
    Route::get('/anexo/{id}', [ActController::class, 'baixarAnexo'])->whereNumber('id');

    Route::get('/classificados', [ActController::class, 'classificados']);
    Route::post('/classificados/exportar', [ActController::class, 'exportarClassificados']);
    Route::get('/alocacao', [ActController::class, 'alocacao']);
    Route::post('/admitir/{id}', [ActController::class, 'admitir'])->whereNumber('id');
});
