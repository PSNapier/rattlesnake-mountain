import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import CookieConsent from './components/CookieConsent.vue';
import { initializeTheme } from './composables/useAppearance';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
	interface ImportMetaEnv {
		readonly VITE_APP_NAME: string;
		[key: string]: string | boolean | undefined;
	}

	interface ImportMeta {
		readonly env: ImportMetaEnv;
		readonly glob: <T>(
			pattern: string,
		) => Record<string, () => Promise<T>>;
	}
}

const appName = 'Rattlesnake Mountain';

createInertiaApp({
	title: (title) => `${title} - ${appName}`,
	resolve: (name) =>
		resolvePageComponent(
			`./pages/${name}.vue`,
			import.meta.glob<DefineComponent>('./pages/**/*.vue'),
		),
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
		color: '#4B5563',
	},
});

// This will set light / dark mode on page load...
initializeTheme();
