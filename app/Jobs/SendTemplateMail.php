<?php

namespace App\Jobs;

use App\Services\Mail\TemplateMailer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTemplateMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    /**
     * @param  string|array<int, string>  $to
     */
    public function __construct(
        private readonly string $slug,
        private readonly string|array $to,
        private readonly array $data = [],
    ) {}

    public function handle(TemplateMailer $mailer): void
    {
        $mailer->send($this->slug, $this->to, $this->data);
    }
}
