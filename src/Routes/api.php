<?php

use Illuminate\Support\Facades\Route;
use OperationGpt\Http\Controllers\ChatController;

Route::middleware(['web'])->group(function () {
    // UI Chat Page
    Route::get('/operation-gpt', [ChatController::class, 'index'])->name('operation-gpt.index');
    
    // API Endpoint
    Route::post('/api/operation-gpt/chat', [ChatController::class, 'chat'])->name('operation-gpt.chat');
});