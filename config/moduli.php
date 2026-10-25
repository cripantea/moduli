<?php

/*
 * Limiti d'uso contro abusi e costi incontrollati (AI, spazio su S3, registrazioni automatiche).
 * Le aziende in prova hanno limiti stretti: per provare servono 1-2 moduli.
 * I clienti paganti hanno limiti alti, pensati solo per fermare script o errori.
 */
return [
    'limits' => [
        'trial' => [
            'templates'        => 3,    // template salvati
            'compiled'         => 20,   // compilazioni salvate (senza abbonamento non si scaricano)
            'uploads_per_day'  => 10,
            'ai_per_day'       => 5,
        ],
        'paid' => [
            'uploads_per_day'  => 200,
            'ai_per_day'       => 100,
        ],
        // Tetto complessivo di estrazioni AI al giorno per tutte le aziende in prova insieme.
        'trial_ai_per_day_total' => 300,
        'registrations_per_ip_per_hour' => 5,
        'registrations_per_day_total'   => 200,
    ],
];
