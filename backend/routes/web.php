<?php

use App\Http\Controllers\OpenApiController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/docs', [OpenApiController::class, 'ui']);
Route::get('/docs/openapi.yaml', [OpenApiController::class, 'spec']);
