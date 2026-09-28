import { Link } from '@inertiajs/react';

/**
 * "← Back to …" link shown above detail and form pages. Used by both layouts
 * via their `back` ({ href, label }) prop.
 */
export default function BackLink({ href, label }) {
    return (
        <Link
            href={href}
            className="group mb-5 inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-indigo-600"
        >
            <svg
                viewBox="0 0 20 20"
                fill="currentColor"
                className="h-4 w-4 transition group-hover:-translate-x-0.5"
                aria-hidden="true"
            >
                <path
                    fillRule="evenodd"
                    d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z"
                    clipRule="evenodd"
                />
            </svg>
            {label}
        </Link>
    );
}
