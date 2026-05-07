<?php

namespace App\Services\Sms;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class EskizSmsService
{
    private function baseUrl(): string
    {
        return config('services.eskiz.base_url');
    }

    private function pendingRequest(): PendingRequest
    {
        $req = Http::timeout((int) config('services.eskiz.request_timeout_seconds'))
            ->connectTimeout((int) config('services.eskiz.connect_timeout_seconds'));

        if (! config('services.eskiz.verify_ssl', true)) {
            return $req->withoutVerifying();
        }

        return $req;
    }

    private function getToken(): string
    {
        $response = $this->pendingRequest()
            ->asForm()
            ->post("{$this->baseUrl()}/auth/login", [
                'email' => config('services.eskiz.email'),
                'password' => config('services.eskiz.password'),
            ]);

        $token = $response->json('data.token');
        if (! $response->successful() || ! $token) {
            logger()->warning('eskiz.login_failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException('Eskiz token olishda xatolik.');
        }

        return $token;
    }

    public function send(string $phone, string $message): void
    {
        $response = $this->pendingRequest()
            ->withToken($this->getToken())
            ->asForm()
            ->post("{$this->baseUrl()}/message/sms/send", [
                'mobile_phone' => ltrim($phone, '+'),
                'message' => $message,
                'from' => config('services.eskiz.sms_from'),
            ]);

        $json = $response->json();
        $eskizStatus = is_array($json) ? ($json['status'] ?? null) : null;

        if (! $response->successful() || (is_string($eskizStatus) && strtolower($eskizStatus) === 'error')) {
            logger()->warning('eskiz.sms_failed', [
                'http_status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException(
                is_array($json) && ! empty($json['message'])
                    ? (string) $json['message']
                    : 'Eskiz SMS yubormadi.'
            );
        }
    }
}
