import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

/**
 * A single toast rendered outside the React tree, so it reaches every page —
 * guest/auth screens, the portal, and every admin page — without needing a
 * layout-mounted component. Before this, a failed request (session expiry,
 * a 500, a dropped connection) failed silently: nothing told the user.
 */
function showErrorToast(message) {
    const id = 'global-error-toast';
    document.getElementById(id)?.remove();

    const toast = document.createElement('div');
    toast.id = id;
    toast.setAttribute('role', 'alert');
    toast.className =
        'fixed bottom-4 right-4 z-50 max-w-sm rounded-lg bg-rose-600 px-4 py-3 text-sm font-medium text-white shadow-lg';
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => toast.remove(), 6000);
}

router.on('invalid', (event) => {
    // A non-Inertia response (a raw HTML error page, typically) — Inertia's default
    // behavior is a full-page modal dumping that HTML, which is unusable for an end
    // user and can leak a stack trace if APP_DEBUG is on. Show a plain message instead.
    const status = event.detail.response?.status;
    const message = {
        403: "You don't have permission to do that.",
        404: 'That page could not be found.',
        419: 'Your session expired. Please refresh the page and try again.',
        429: 'Too many requests — please wait a moment and try again.',
    }[status] ?? 'Something went wrong on the server. Please try again.';

    event.preventDefault();
    showErrorToast(message);
});

router.on('exception', (event) => {
    // A network failure or a client-side error while processing the visit.
    console.error(event.detail.exception);
    event.preventDefault();
    showErrorToast('A network error occurred. Please check your connection and try again.');
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});
