import AuthButton from '@/Components/Auth/AuthButton';
import FloatingField from '@/Components/Auth/FloatingField';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

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
        <GuestLayout
            title="Welcome back to EventFlow"
            subtitle="Sign in to manage your events and stakeholders."
            status={status}
            footer={
                <>
                    Stakeholder?{' '}
                    <Link href={route('login.code')} className="font-medium text-indigo-600 hover:text-indigo-500">
                        Sign in with an emailed code instead
                    </Link>
                </>
            }
        >
            <Head title="Log in" />

            <form onSubmit={submit} className="space-y-4">
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
                    <span className="text-sm text-slate-600">Remember sign in details</span>
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

                <AuthButton disabled={processing}>Log in</AuthButton>
            </form>
        </GuestLayout>
    );
}
