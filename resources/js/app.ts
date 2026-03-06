import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import CookieConsent from './components/CookieConsent.vue';
import { initializeTheme } from './composables/useAppearance';

const appName = 'Rattlesnake Mountain';

const pageModules = import.meta.glob<DefineComponent>('./pages/**/*.vue');

/** Resolve page with case-insensitive fallback for Windows/Linux path mismatch. */
function resolvePage(name: string) {
	const exact = `./pages/${name}.vue`;
	const loader = pageModules[exact];
	if (loader) return loader();

	// Fallback: find by case-insensitive path match
	const lower = exact.toLowerCase();
	const key = Object.keys(pageModules).find((k) => k.toLowerCase() === lower);
	if (key) return (pageModules as Record<string, () => Promise<DefineComponent>>)[key]();
	throw new Error(`Page not found: ${exact}`);
}

createInertiaApp({
	title: (title) => `${title} - ${appName}`,
	resolve: (name) => resolvePage(name),
	setup({ el, App, props, plugin }) {
		const app = createApp({
			render: () =>
				h('div', [
					h(App, props),
					h(CookieConsent),
				]),
		})
			.use(plugin)
			.use(ZiggyVue);

		app.mount(el);

		return app;
	},
	progress: {
		color: '#1f90bb', // shakespeare-500
	},
});

// This will set light / dark mode on page load...
initializeTheme();
