<?php

namespace App\Services;

use App\Jobs\ParseRatesJob;
use App\Models\Rate;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RateParserService
{
    public function __invoke()
    {
        if (Cache::has('rates')) {
            return [
                'success' => true,
                'rates' => Cache::get('rates')
            ];
        }

        try {
            $this->parse();
            return [
                'success' => true,
                'rates' => Cache::get('rates'),
            ];
        } catch (Exception $exception) {
            // Retry parse after 5 seconds
            dispatch(new ParseRatesJob())->delay(5);
            return [
                'success' => false,
                'message' => 'Something went wrong. Please try after 10 seconds',
                'error' => $exception->getMessage(),
            ];
        }
    }

    public function parse()
    {
        $xml = file_get_contents(env('CURRENCY_RATE_URL', 'http://www.cbr.ru/scripts/XML_daily.asp'));
        $data = json_decode(json_encode(simplexml_load_string($xml)), true);
        unset($xml);
        Cache::set('last_parse_date', Carbon::parse($data['@attributes']['Date'])->startOfDay());
        $rates = $data['Valute'];
        unset($data);
        $data = [];
        foreach ($rates as $rate) {
            $data[] = [
                'external_id' => $rate['@attributes']['ID'],
                'num_code' => $rate['NumCode'],
                'char_code' => $rate['CharCode'],
                'nominal' => $rate['Nominal'],
                'name' => $rate['Name'],
                'value' => (float)str_replace(',', '.', $rate['Value']),
                'rate' => (float)str_replace(',', '.', $rate['VunitRate']),
            ];
        }

        Rate::query()->upsert($data, ['external_id']);
        // Or use TTL for Cache
        Cache::put('rates', function () {
            return DB::table('rates')->get();
        }, now()->addDay()->startOfDay());
    }
}
