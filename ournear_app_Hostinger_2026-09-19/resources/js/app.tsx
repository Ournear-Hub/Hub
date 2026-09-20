import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import type { ComponentType } from 'react';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'OurNear Talent';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: async (name) => {
        const pages = import.meta.glob('./Pages/**/*.tsx');
        const importer = pages[`./Pages/${name}.tsx`];

        if (!importer) {
            throw new Error(`Inertia page not found: ${name}`);
        }

        const module = (await importer()) as { default: ComponentType };

        return module.default;
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: '#214449',
    },
});
