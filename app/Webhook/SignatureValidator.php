<?php

namespace App\Webhook;

use Illuminate\Http\Request;
use Spatie\WebhookClient\WebhookConfig;
use Spatie\WebhookClient\Exceptions\WebhookFailed;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator as SpatieSignatureValidator;

class SignatureValidator implements SpatieSignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {   
        $signature = $request->header('authorization');

        if (! $signature) {
            return false;
        }

        $signingSecret = shopee_partner_key();

        if (empty($signingSecret)) {
            throw WebhookFailed::signingSecretNotSet();
        }

        $computedSignature = hash_hmac('sha256','https://9ozb17y6m1q93pqmpupgms.hooks.webhookrelay.com'.'|'.$request->getContent(), $signingSecret);
        
        return hash_equals($signature, $computedSignature);
    }
}
