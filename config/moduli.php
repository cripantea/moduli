<?php

/*
 * Limiti d'uso contro abusi e costi incontrollati (AI, spazio su S3, registrazioni automatiche).
 * Le aziende in prova hanno limiti stretti: per provare servono 1-2 moduli.
 * I clienti paganti hanno limiti alti, pensati solo per fermare script o errori.
 */
return [
    // Chi vende il servizio (Termini di servizio). La sede legale si imposta nel .env.
    'company' => [
        'name'    => 'Fusion Media di Luca Gabriele Pantea',
        'vat'     => '13148540969',
        'address' => env('COMPANY_ADDRESS'),
        'email'   => env('COMPANY_EMAIL'),
    ],

    // Offerta di lancio: fino alla scadenza 29 €/mese, poi 49 €/mese. Chi si abbona prima resta a 29 €
    // (gli abbonamenti Stripe esistenti tengono il loro prezzo). Ora di Roma.
    'launch' => [
        'ends_at'        => env('LAUNCH_ENDS_AT', '2026-10-29 23:59:59'),
        'price'          => 29,
        'standard_price' => 49,
    ],

    'limits' => [
        'trial' => [
            'templates'        => 3,    // template salvati
            'compiled'         => 20,   // compilazioni salvate (senza abbonamento non si scaricano)
            'uploads_per_day'  => 10,
            'ai_per_day'       => 5,
            'assistant_per_day' => 40,  // messaggi all'assistente AI
        ],
        'paid' => [
            'templates'        => 100,
            'uploads_per_day'  => 200,
            'ai_per_day'       => 100,
            'assistant_per_day' => 300,
        ],
        // Tetto complessivo di estrazioni AI al giorno per tutte le aziende in prova insieme.
        'trial_ai_per_day_total' => 300,
        'registrations_per_ip_per_hour' => 5,
        'registrations_per_day_total'   => 200,
    ],
];
