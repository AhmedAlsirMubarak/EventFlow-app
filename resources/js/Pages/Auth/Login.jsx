import ApplicationLogo from '@/Components/ApplicationLogo';
import InputError from '@/Components/InputError';
import { Head, Link, useForm } from '@inertiajs/react';

function FloatingField({ id, label, type = 'text', value, onChange, autoComplete, autoFocus, error }) {
    return (
        <div>
            <div
                className={`rounded-xl border px-4 py-2.5 transition focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-500/20 ${
                    error ? 'border-rose-400' : 'border-slate-200'
                }`}
            >
                <label htmlFor={id} className="block text-xs font-medium text-slate-400">
                    {label}
                </label>
                <input
                    id={id}
                    type={type}
                    value={value}
                    onChange={onChange}
                    autoComplete={autoComplete}
                    autoFocus={autoFocus}
                    className="block w-full border-0 p-0 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0"
                />
            </div>
            <InputError message={error} className="mt-1.5" />
        </div>
    );
}

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <div className="flex min-h-screen">
            <Head title="Log in" />

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
                        &ldquo;Coordinate every event, stakeholder, and document in one
                        place.&rdquo;
                    </p>
                </div>
            </div>

            <div className="flex w-full flex-col items-center justify-center px-6 py-12 lg:w-1/2">
                <div className="w-full max-w-sm">
                    <h1 className="text-2xl font-bold text-slate-900">
                        Welcome back to EventFlow
                    </h1>
                    <p className="mt-1 text-sm text-slate-500">
                        Sign in to manage your events and stakeholders.
                    </p>

                    {status && (
                        <div className="mt-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                            {status}
                        </div>
                    )}

                    <form onSubmit={submit} className="mt-8 space-y-4">
                        <FloatingField
                            id="email"
                            label="Email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            autoComplete="username"
                            autoFocus
                            error={errors.email}
                        />

                        <FloatingField
                            id="password"
                            label="Password"
                            type="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            autoComplete="current-password"
                            error={errors.password}
                        />

                        {canResetPassword && (
                            <Link
                                href={route('password.request')}
                                className="inline-block text-sm font-medium text-indigo-600 hover:text-indigo-500"
                            >
                                Forgot password?
                            </Link>
                        )}

                        <label className="flex cursor-pointer items-center justify-between pt-2">
                            <span className="text-sm text-slate-600">
                                Remember sign in details
                            </span>
                            <span className="relative inline-flex shrink-0">
                                <input
                                    type="checkbox"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                    className="peer sr-only"
                                />
                                <span className="h-6 w-11 rounded-full bg-slate-200 transition peer-checked:bg-indigo-600" />
                                <span className="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5" />
                            </span>
                        </label>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-full bg-indigo-600 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Log in
                        </button>
                    </form>

                    <div className="mt-6 border-t border-slate-100 pt-4 text-center text-sm text-slate-500">
                        Stakeholder?{' '}
                        <Link
                            href={route('login.code')}
                            className="font-medium text-indigo-600 hover:text-indigo-500"
                        >
                            Sign in with an emailed code instead
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    );
}
