<?php

use iEducar\Packages\BuritiContratacoesTemporarias\Http\Controllers\CandidatoPortalController;
use Illuminate\Support\Facades\Route;

Route::prefix('contratacoes-temporarias/candidato')->group(function (): void {
    Route::get('/', [CandidatoPortalController::class, 'inicio'])->name('act.candidato.inicio');
    Route::get('/inscrever/{id}', [CandidatoPortalController::class, 'inscrever'])->whereNumber('id')->name('act.candidato.inscrever');
    Route::post('/inscrever/{id}', [CandidatoPortalController::class, 'salvar'])->whereNumber('id');
    Route::get('/comprovante/{id}', [CandidatoPortalController::class, 'comprovante'])->whereNumber('id')->name('act.candidato.comprovante');
    Route::get('/protocolo', [CandidatoPortalController::class, 'protocolo'])->name('act.candidato.protocolo');
});
