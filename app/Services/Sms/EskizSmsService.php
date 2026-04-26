<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class EskizSmsService
{
    private string $baseUrl = 'https://notify.eskiz.uz/api';

    private function getToken(): string
    {
        $response = Http::post("{$this->baseUrl}/auth/login", [
            'email' => config('services.eskiz.email'),
            'password' => config('services.eskiz.password'),
        ]);

        $token = $response->json('data.token');
        if (! $response->successful() || ! $token) {
            throw new RuntimeException('Eskiz token olishda xatolik yuz berdi.');
        }

        return $token;
    }

    public function send(string $phone, string $message): bool
    {
        $response = Http::withToken($this->getToken())
            ->post("{$this->baseUrl}/message/sms/send", [
                'mobile_phone' => ltrim($phone, '+'),
                'message' => $message,
                'from' => '4546',
            ]);

        return $response->successful();
    }
}
