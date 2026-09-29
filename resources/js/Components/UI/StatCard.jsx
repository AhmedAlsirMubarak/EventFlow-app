import { Link } from '@inertiajs/react';

const accents = {
    indigo: 'bg-indigo-50 text-indigo-600',
    emerald: 'bg-emerald-50 text-emerald-600',
    amber: 'bg-amber-50 text-amber-600',
    rose: 'bg-rose-50 text-rose-600',
    sky: 'bg-sky-50 text-sky-600',
};

const hintTones = {
    muted: 'text-slate-400',
    alert: 'font-medium text-rose-600',
};

/**
 * A headline number. `hint` adds one line of context under the value
 * ("2 overdue"); `href` makes the whole tile a link to where you'd act on it.
 */
export default function StatCard({ label, value, icon, accent = 'indigo', hint, hintTone = 'muted', href }) {
    const body = (
        <div className="group flex h-full items-center gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md sm:p-5">
            {icon && (
                // Hidden on phones, where tiles sit two to a row and need the width for the text.
                <div
                    className={`hidden h-12 w-12 shrink-0 items-center justify-center rounded-xl transition group-hover:scale-105 sm:flex ${accents[accent]}`}
                >
                    {icon}
                </div>
            )}
            <div className="min-w-0">
                <p className="truncate text-sm font-medium text-slate-500">{label}</p>
                <p className="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{value}</p>
                {hint && <p className={`mt-0.5 line-clamp-2 text-xs sm:truncate ${hintTones[hintTone]}`}>{hint}</p>}
            </div>
        </div>
    );

    return href ? (
        <Link href={href} className="block rounded-2xl outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40">
            {body}
        </Link>
    ) : (
        body
    );
}
