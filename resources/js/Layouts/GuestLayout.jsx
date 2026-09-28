import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';

/**
 * Split-screen shell shared by every signed-out page (login, forgot/reset
 * password, stakeholder code sign-in, two-factor, etc.) so they all look like
 * one product.
 */
export default function GuestLayout({ title, subtitle, status, footer, children }) {
    return (
        <div className="flex min-h-screen bg-white">
            <div
                className="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-slate-900 bg-cover bg-center p-10 text-white lg:flex"
                style={{ backgroundImage: "url('/images/test.jpg')" }}
            >
                <div className="absolute inset-0 bg-linear-to-t from-slate-900/90 via-slate-900/50 to-slate-900/20" />

                <Link href="/" className="relative flex items-center gap-2">
                    <ApplicationLogo className="h-8 w-8 fill-current text-white" />
                    <span className="text-lg font-semibold">EventFlow</span>
                </Link>

                <div className="relative max-w-sm">
                    <p className="text-2xl font-semibold leading-snug">
                        &ldquo;Coordinate every event, stakeholder, and document in one place.&rdquo;
                    </p>
                </div>
            </div>

            <div className="flex w-full flex-col items-center justify-center px-6 py-12 lg:w-1/2">
                <div className="w-full max-w-sm">
                    <Link href="/" className="mb-8 flex items-center gap-2 lg:hidden">
                        <ApplicationLogo className="h-8 w-8 fill-current text-indigo-600" />
                        <span className="text-lg font-semibold text-slate-900">EventFlow</span>
                    </Link>

                    {title && <h1 className="text-2xl font-bold text-slate-900">{title}</h1>}
                    {subtitle && <p className="mt-1 text-sm text-slate-500">{subtitle}</p>}

                    {status && (
                        <div className="mt-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                            {status}
                        </div>
                    )}

                    <div className="mt-8">{children}</div>

                    {footer && (
                        <div className="mt-6 border-t border-slate-100 pt-4 text-center text-sm text-slate-500">
                            {footer}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
