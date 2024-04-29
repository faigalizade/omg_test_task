<?php

use App\Http\Controllers\CurrencyController;
use Illuminate\Support\Facades\Route;

Route::get('/rates', [CurrencyController::class, 'getRates']);
