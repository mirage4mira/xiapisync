<?php

namespace App\Webhook;

use \Spatie\WebhookClient\ProcessWebhookJob as SpatieProcessWebhookJob;

class ProcessWebhookJob extends SpatieProcessWebhookJob
{
    public function handle()
    {   
        \Log::alert($this->webhookCall);

    }
}