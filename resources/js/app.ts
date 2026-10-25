import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import CookieBanner from './Components/CookieBanner.vue';
import { funnel, pageView, track, type TrackEvent } from './tracking';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, DefineComponent, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Fusion Moduli';

createInertiaApp({
    title: (title) => (!title ? appName : title.includes(appName) ? title : `${title} - ${appName}`),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => [h(App, props), h(CookieBanner)] })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

const LANDINGS = ['Landing', 'Prova'];
let priceObserver: IntersectionObserver | null = null;

// Pixel Meta: una visualizzazione per pagina più gli eventi indicati dal server (registrazione, acquisto).
router.on('navigate', (event) => {
    const page = event.detail.page;
    pageView();
    for (const e of (page.props.track as TrackEvent[] | undefined) ?? []) {
        track(e);
    }

    if (page.component === 'Auth/Register') {
        track({ event: 'ApreRegistrazione', custom: true });
    }

    // Arriva a vedere il prezzo: interesse vero, per Meta è ViewContent.
    priceObserver?.disconnect();
    priceObserver = null;
    if (LANDINGS.includes(page.component) && 'IntersectionObserver' in window) {
        requestAnimationFrame(() => {
            const price = document.querySelector('[data-funnel="prezzo"]');
            if (!price) return;
            priceObserver = new IntersectionObserver((entries) => {
                if (!entries.some((e) => e.isIntersecting)) return;
                priceObserver?.disconnect();
                if (funnel('prezzo')) track({ event: 'ViewContent', params: { content_name: 'Prezzo Fusion Moduli' } });
            }, { threshold: 0.3 });
            priceObserver.observe(price);
        });
    }
});

// Clic su "Inizia ora" (qualsiasi pulsante verso la registrazione) dalle landing: per Meta è Lead.
document.addEventListener('click', (event) => {
    const link = (event.target as Element | null)?.closest?.('a[href]') as HTMLAnchorElement | null;
    if (!link || new URL(link.href, location.href).pathname !== '/register') return;
    if (!['/', '/prova'].includes(location.pathname)) return;
    if (funnel('clic')) track({ event: 'Lead' });
}, true);
