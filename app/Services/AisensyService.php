<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class AisensyService
{
    protected string $apiKey;

    protected string $apiUrl;

    public function __construct()
    {
        $this->apiKey = env('AISENSY_API_KEY', config('services.aisensy.api_key'));

        $this->apiUrl = Setting::get(
            'aisensy_url',
            env('AISENSY_URL', config('services.aisensy.url'))
        );
    }

    public function send(string $phone, array $templateParams = [], ?string $templateName = null): array
    {
        if (empty($this->apiKey) || empty($this->apiUrl)) {
            return ['status' => 'failed', 'error' => 'Aisensy API key or URL not configured.'];
        }

        $phone = $this->normalizeIndianMobile($phone);

        if (! $phone) {
            return ['status' => 'failed', 'error' => 'Invalid Indian mobile number.'];
        }

        $campaignName = $templateName ?? config('services.aisensy.template', 'DEFAULT_TEMPLATE');

        $url = $this->normalizeUrl($this->apiUrl);

        $userName = $this->sanitizeUserName($templateParams[0] ?? 'User');

        $payload = [
            'apiKey' => $this->apiKey,
            'campaignName' => $campaignName,
            'destination' => $phone,
            'userName' => $userName,
            'templateParams' => $templateParams,
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            $data = $response->json();

            if ($response->successful()) {
                return ['status' => 'success', 'response' => $data];
            }

            return [
                'status' => 'failed',
                'error' => $data['message'] ?? $response->body(),
                'response' => $data,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function normalizeIndianMobile(string $phone): ?string
    {
        if ($phone === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if (strlen($digits) === 10) {
            return '+91'.$digits;
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return '+'.$digits;
        }

        if (str_starts_with($phone, '+91') && strlen($digits) === 12) {
            return '+91'.substr($digits, 2);
        }

        return null;
    }

    protected function sanitizeUserName(string $name): string
    {
        $clean = preg_replace('/[^A-Za-z0-9 ]+/', '', $name) ?? 'User';
        $clean = trim($clean);

        if ($clean === '') {
            $clean = 'User';
        }

        return mb_substr($clean, 0, 20);
    }

    protected function normalizeUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if ($path === null || $path === '' || $path === '/') {
            return rtrim($url, '/').'/campaign/t1/api/v2';
        }

        return $url;
    }
}

