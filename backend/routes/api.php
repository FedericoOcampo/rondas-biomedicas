<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RondaController;

Route::post('/rondas', [RondaController::class, 'store']);
Route::get('/rondas/ultima', [RondaController::class, 'ultimaRonda']);
Route::get('/rondas/fecha/{fecha}', [RondaController::class, 'buscarPorFecha']);
Route::patch('/rondas/{id}/firma-lider', [RondaController::class, 'agregarFirmaLider']);
Route::get('/dashboard', [RondaController::class, 'dashboard']);
Route::get('/rondas/anteriores/{fecha}', [RondaController::class, 'rondasAnteriores']);