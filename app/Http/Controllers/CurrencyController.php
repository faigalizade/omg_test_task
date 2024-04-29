<?php

namespace App\Http\Controllers;

use App\Services\RateParserService;

class CurrencyController extends Controller
{
    public function getRates()
    {
        return (new RateParserService())();
    }
}
