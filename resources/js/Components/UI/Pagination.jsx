import { Link } from '@inertiajs/react';

/**
 * Renders the `links` array from a Laravel paginator response
 * (`$query->paginate(...)`, e.g. `{ data, links, ... }`).
 */
export default function Pagination({ links }) {
    if (links.length <= 3) {
        // Only prev/current/next — nothing to page through.
        return null;
    }

    return (
        <div className="mt-4 flex flex-wrap items-center gap-2">
            {links.map((link, index) => (
                <Link
                    key={index}
                    href={link.url ?? '#'}
                    preserveScroll
                    className={`rounded-md px-3 py-1.5 text-sm ${
                        link.active
                            ? 'bg-indigo-600 text-white'
                            : 'text-slate-600 hover:bg-slate-100'
                    } ${!link.url ? 'pointer-events-none opacity-40' : ''}`}
                    dangerouslySetInnerHTML={{ __html: link.label }}
                />
            ))}
        </div>
    );
}
