<?php

namespace App\Jobs;

use App\Services\Sms\EskizSmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSmsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $phone,
        public string $message
    ) {}

    public function handle(EskizSmsService $smsService): void
    {
        $smsService->send($this->phone, $this->message);
    }
}
