import { IconCheckCircle, IconExclamationTriangle } from '@/Components/Icons';
import { router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

/**
 * Renders the session's flash status/error message, shared globally via
 * HandleInertiaRequests so every redirect's ->with('status'|'error', ...) is
 * actually seen, not just the handful of pages that used to check for it locally.
 */
export default function FlashBanner() {
    const { flash } = usePage().props;
    const [dismissed, setDismissed] = useState(false);

    const message = flash?.error ?? flash?.status;
    const isError = Boolean(flash?.error);
    const undoUrl = !isError ? flash?.undo : null;
    const [undoing, setUndoing] = useState(false);

    function undo() {
        setUndoing(true);
        router.post(undoUrl, {}, { preserveScroll: true, onFinish: () => setUndoing(false) });
    }

    // A fresh flash message (new status/error text) should always show, even if the
    // previous one was dismissed.
    useEffect(() => {
        setDismissed(false);
    }, [message]);

    if (!message || dismissed) {
        return null;
    }

    const Icon = isError ? IconExclamationTriangle : IconCheckCircle;

    return (
        <div
            role="status"
            className={`mb-6 flex items-start gap-3 rounded-lg px-4 py-3 text-sm font-medium ${
                isError ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700'
            }`}
        >
            <Icon className="mt-0.5 h-5 w-5 shrink-0" />
            <span className="flex-1">{message}</span>
            {undoUrl && (
                <button
                    type="button"
                    onClick={undo}
                    disabled={undoing}
                    className="shrink-0 rounded-md bg-white/70 px-2.5 py-0.5 text-sm font-semibold text-emerald-800 ring-1 ring-emerald-600/20 transition hover:bg-white disabled:opacity-50"
                >
                    Undo
                </button>
            )}
            <button
                type="button"
                onClick={() => setDismissed(true)}
                className="shrink-0 rounded-md p-0.5 opacity-60 hover:opacity-100"
                aria-label="Dismiss message"
            >
                <svg viewBox="0 0 20 20" fill="currentColor" className="h-4 w-4">
                    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                </svg>
            </button>
        </div>
    );
}
