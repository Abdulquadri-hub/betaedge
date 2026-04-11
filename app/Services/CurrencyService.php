<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    const CACHE_DURATION = 3600; // 1 hour
    const IP_API_URL = 'https://ip-api.com/json/';

    /**
     * Detect user's country from IP and return currency info
     */
    public static function detectCurrencyFromIP(string $ip = null): array
    {
        try {
            $ip = $ip ?? request()->ip() ?? request()->getClientIp();
            
            // Check cache first
            $cacheKey = "currency_detection_{$ip}";
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            // Call ip-api.com
            $response = Http::timeout(5)->get(self::IP_API_URL . $ip);

            if (!$response->successful()) {
                Log::warning("IP Geolocation failed for IP: {$ip}", ['status' => $response->status()]);
                return self::getDefaultCurrency();
            }

            $data = $response->json();

            if ($data['status'] !== 'success') {
                Log::warning("IP Geolocation returned error", ['data' => $data]);
                return self::getDefaultCurrency();
            }

            $countryCode = $data['countryCode'] ?? 'NG';
            $currencies = config('currencies.currencies');

            $currencyData = $currencies[$countryCode] ?? $currencies['DEFAULT'];

            $result = [
                'country_code' => $countryCode,
                'country_name' => $data['country'] ?? 'Unknown',
                'city' => $data['city'] ?? '',
                'timezone' => $data['timezone'] ?? '',
                'currency_code' => $currencyData['code'],
                'currency_symbol' => $currencyData['symbol'],
                'currency_name' => $currencyData['name'],
                'exchange_rate' => $currencyData['exchange_rate'],
                'ip' => $ip,
            ];

            // Cache the result
            Cache::put($cacheKey, $result, self::CACHE_DURATION);

            return $result;

        } catch (\Exception $e) {
            Log::error('Currency detection error: ' . $e->getMessage());
            return self::getDefaultCurrency();
        }
    }

    /**
     * Get default currency (Nigeria/NGN)
     */
    public static function getDefaultCurrency(): array
    {
        $default = config('currencies.currencies.DEFAULT');
        
        return [
            'country_code' => 'NG',
            'country_name' => 'Nigeria',
            'city' => '',
            'timezone' => '',
            'currency_code' => $default['code'],
            'currency_symbol' => $default['symbol'],
            'currency_name' => $default['name'],
            'exchange_rate' => $default['exchange_rate'],
            'ip' => null,
        ];
    }

    /**
     * Convert amount from base currency (NGN) to target currency
     */
    public static function convertFromBase(float $amount, float $exchangeRate): float
    {
        return round($amount * $exchangeRate, 2);
    }

    /**
     * Convert amount from target currency back to base (NGN)
     */
    public static function convertToBase(float $amount, float $exchangeRate): float
    {
        if ($exchangeRate == 0) return 0;
        return round($amount / $exchangeRate, 2);
    }
}
