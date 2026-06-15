import "./bootstrap";
import "../css/app.css";
import "primeicons/primeicons.css";

import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import PrimeVue from "primevue/config";
import ToastService from "primevue/toastservice";
import NightLedgerPreset from "./theme/nightLedgerPreset";

const appName =
    window.document.getElementsByTagName("title")[0]?.innerText || "Laravel";

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue")
        ) as any,
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(PrimeVue, {
                theme: {
                    preset: NightLedgerPreset,
                    options: { darkModeSelector: ".app-dark" },
                },
            })
            .use(ToastService)
            .mount(el);
    },
    progress: {
        color: "#4B5563",
    },
});
