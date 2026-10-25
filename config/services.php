<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Provider per l'estrazione AI dei campi: anthropic | deepseek
    // Abbonamento Moduli (29 €/mese) con Stripe Checkout.
    'stripe' => [
        'secret'         => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'price'          => env('STRIPE_PRICE_ID'),            // 29 €/mese, prezzo di lancio
        'price_standard' => env('STRIPE_PRICE_ID_STANDARD'),   // 49 €/mese, dopo il lancio
    ],

    // Assistente AI dentro l'app (stessa chiave di Fusion CRM).
    'deepseek' => [
        'key'      => env('DEEPSEEK_API_KEY'),
        'model'    => env('DEEPSEEK_MODEL', 'deepseek-chat'),
        'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
    ],

    // Pixel Meta: si carica solo dopo il consenso ai cookie (banner nel sito).
    'meta' => [
        'pixel_id' => env('META_PIXEL_ID'),
    ],

    // Pagina di prenotazione della call di onboarding (calendario CRM Fusion).
    'onboarding' => [
        'booking_url' => env('ONBOARDING_BOOKING_URL'),
    ],

    'ai' => [
        'provider' => env('AI_PROVIDER', 'anthropic'),
    ],

    // DeepSeek tramite il suo endpoint compatibile con l'API Anthropic.
    // deepseek-flash accetta immagini (serve per le pagine annotate).
    'deepseek' => [
        'key'      => env('DEEPSEEK_API_KEY'),
        'model'    => env('DEEPSEEK_MODEL', 'deepseek-flash'),
        'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com/anthropic'),
    ],

    'anthropic' => [
        'key'    => env('ANTHROPIC_API_KEY'),
        'model'  => env('ANTHROPIC_MODEL', 'claude-opus-5-5'),
        // low | medium | high | xhigh | max — più alto = più accurato ma più lento e costoso
        'effort' => env('ANTHROPIC_EFFORT', 'medium'),
        'base_url' => env('ANTHROPIC_BASE_URL'),
    ],

    // Rilevatore neurale dei campi (FFDetr, opzionale): installalo con scripts/form-detector/setup.sh
    'form_detector' => [
        'enabled' => env('FORM_DETECTOR_ENABLED', true),
        'python'  => env('FORM_DETECTOR_PYTHON', storage_path('app/form-detector/venv/bin/python')),
        'model'   => env('FORM_DETECTOR_MODEL', storage_path('app/form-detector/ffdetr.onnx')),
    ],

];
