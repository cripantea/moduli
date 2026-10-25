<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="index, follow">

        <!-- Default SEO — overridden per-page via <Head> in Vue -->
        <title inertia>Fusion Moduli: compila i tuoi moduli PDF in 30 secondi, senza Adobe</title>
        <meta name="description" content="Carica il modulo PDF che compili ogni giorno: l'AI trova i campi, tu lo compili in 30 secondi e ogni documento resta in archivio. 29 €/mese, rimborso entro 30 giorni.">

        <!-- Open Graph -->
        <meta property="og:type" content="website">
        <meta property="og:locale" content="it_IT">
        <meta property="og:site_name" content="Fusion Moduli">
        <meta property="og:title" content="Fusion Moduli: compila i tuoi moduli PDF in 30 secondi, senza Adobe">
        <meta property="og:description" content="Carica il modulo PDF che compili ogni giorno: l'AI trova i campi, tu lo compili in 30 secondi e ogni documento resta in archivio. 29 €/mese, rimborso entro 30 giorni.">

        <!-- Twitter / X -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="Fusion Moduli: compila i tuoi moduli PDF in 30 secondi, senza Adobe">
        <meta name="twitter:description" content="Carica il modulo PDF che compili ogni giorno: l'AI trova i campi, tu lo compili in 30 secondi e ogni documento resta in archivio. 29 €/mese, rimborso entro 30 giorni.">

        <!-- Favicon -->
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="alternate icon" href="/favicon.ico">
        <link rel="apple-touch-icon" href="/favicon.svg">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Pixel Meta: caricato da resources/js/tracking.ts solo dopo il consenso ai cookie -->
        <meta name="meta-pixel" content="{{ config('services.meta.pixel_id') }}">

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
