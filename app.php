<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;

class CommissionCalculator
{
    private $client;
    private $exchangeRatesApiUrl = 'https://api.exchangeratesapi.io/latest';
    private $binListApiUrl = 'https://lookup.binlist.net/';
    private $euCountries = [
        'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK'
    ];

    public function __construct()
    {
        $this->client = new Client();
    }

    public function calculateCommissions($inputFile)
    {
        foreach (explode("\n", file_get_contents($inputFile)) as $row) {
            if (empty($row)) break;

            $transaction = json_decode($row, true);
            $bin = $transaction['bin'];
            $amount = $transaction['amount'];
            $currency = $transaction['currency'];

            $binResults = $this->fetchBinData($bin);
            if (!$binResults) {
                die('Error fetching BIN data!');
            }

            $isEu = $this->isEu($binResults->country->alpha2);
            $rate = $this->fetchExchangeRate($currency);

            $amntFixed = $currency == 'EUR' || $rate == 0 ? $amount : $amount / $rate;
            $commission = $amntFixed * ($isEu ? 0.01 : 0.02);

            echo ceil($commission * 100) / 100;
            print "\n";
        }
    }

    private function fetchBinData($bin)
    {
        $response = $this->client->get($this->binListApiUrl . $bin);
        return json_decode($response->getBody());
    }

    private function fetchExchangeRate($currency)
    {
        $response = $this->client->get($this->exchangeRatesApiUrl);
        $rates = json_decode($response->getBody(), true)['rates'];
        return $rates[$currency] ?? 0;
    }

    private function isEu($countryCode)
    {
        return in_array($countryCode, $this->euCountries);
    }
}

$calculator = new CommissionCalculator();
$calculator->calculateCommissions($argv[1]);
