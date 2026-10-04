import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import Toaster from './components/Toaster.vue';
import { initializeTheme } from './composables/useAppearance';
import { listenForFlashMessages, type Flash } from './composables/useToasts';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        // Toasts live outside the pages, so they stay put while pages change
        createApp({ render: () => [h(App, props), h(Toaster)] })
            .use(plugin)
            .mount(el);

        listenForFlashMessages(props.initialPage.props.flash as Flash);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
