import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { renderToString } from '@vue/server-renderer';
import { createSSRApp, h } from 'vue';
import { route as ziggyRoute } from 'ziggy-js';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const pageModules = import.meta.glob('./pages/**/*.vue');

function resolvePage(name: string) {
	const exact = `./pages/${name}.vue`;
	const loader = pageModules[exact];
	if (loader) return loader();
	const lower = exact.toLowerCase();
	const key = Object.keys(pageModules).find((k) => k.toLowerCase() === lower);
	if (key) return pageModules[key]();
	throw new Error(`Page not found: ${exact}`);
}

createServer((page) =>
	createInertiaApp({
		page,
		render: renderToString,
		title: (title) => `${title} - ${appName}`,
		resolve: (name) => resolvePage(name),
		setup({ App, props, plugin }) {
			const app = createSSRApp({ render: () => h(App, props) });

			// Configure Ziggy for SSR...
			const ziggyConfig = {
				...page.props.ziggy,
				location: new URL(page.props.ziggy.location),
			};

			// Create route function...
			const route = (name: string, params?: any, absolute?: boolean) => ziggyRoute(name, params, absolute, ziggyConfig);

			// Make route function available globally...
			app.config.globalProperties.route = route;

			// Make route function available globally for SSR...
			if (typeof window === 'undefined') {
				global.route = route;
			}

			app.use(plugin);

			return app;
		},
	}),
);
