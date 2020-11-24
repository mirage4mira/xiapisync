<?php

namespace App\Webhook;

use Illuminate\Http\Request;
use Spatie\WebhookClient\WebhookConfig;
use Spatie\WebhookClient\WebhookResponse\RespondsToWebhook as SpatieRespondsToWebhook;

Class RespondsToWebhook implements SpatieRespondsToWebhook
{
    public function respondToValidWebhook(Request $request, WebhookConfig $config){
        return response(200);
    }
}