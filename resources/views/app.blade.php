<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="index, follow">

        <!-- Default SEO — overridden per-page via <Head> in Vue -->
        <title inertia>Fusion Moduli — Compila e genera documenti più velocemente</title>
        <meta name="description" content="Digitalizza i moduli della tua azienda. Compila i dati attraverso procedure guidate, visualizza il documento in tempo reale e genera il file finale.">

        <!-- Open Graph -->
        <meta property="og:type" content="website">
        <meta property="og:locale" content="it_IT">
        <meta property="og:site_name" content="Fusion Moduli">
        <meta property="og:title" content="Fusion Moduli — Compila e genera documenti più velocemente">
        <meta property="og:description" content="Digitalizza i moduli della tua azienda. Compila i dati attraverso procedure guidate, visualizza il documento in tempo reale e genera il file finale.">

        <!-- Twitter / X -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="Fusion Moduli — Compila e genera documenti più velocemente">
        <meta name="twitter:description" content="Digitalizza i moduli della tua azienda. Compila i dati attraverso procedure guidate, visualizza il documento in tempo reale e genera il file finale.">

        <!-- Favicon -->
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="alternate icon" href="/favicon.ico">
        <link rel="apple-touch-icon" href="/favicon.svg">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- ============================================================
             TRACKING — uncomment and configure when ready
             ============================================================

        Google Analytics 4
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"></script>
        <script>
          window.dataLayer = window.dataLayer || [];
          function gtag(){dataLayer.push(arguments);}
          gtag('js', new Date());
          gtag('config', 'G-XXXXXXXXXX');
        </script>

        Meta Pixel
        <script>
          !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
          n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
          n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
          t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
          document,'script','https://connect.facebook.net/en_US/fbevents.js');
          fbq('init', 'YOUR_PIXEL_ID');
          fbq('track', 'PageView');
        </script>
        <noscript><img height="1" width="1" style="display:none"
          src="https://www.facebook.com/tr?id=YOUR_PIXEL_ID&ev=PageView&noscript=1"/></noscript>

        ============================================================ -->

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
