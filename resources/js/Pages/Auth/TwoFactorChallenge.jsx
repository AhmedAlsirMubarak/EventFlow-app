import AuthButton from '@/Components/Auth/AuthButton';
import FloatingField from '@/Components/Auth/FloatingField';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function TwoFactorChallenge({ status }) {
    const { data, setData, post, processing, errors } = useForm({ code: '' });

    const submit = (e) => {
        e.preventDefault();

        post(route('two-factor.login.store'));
    };

    return (
        <GuestLayout
            title="Two-factor authentication"
            subtitle="Enter the 6-digit code from your authenticator app."
            status={status}
            footer={
                <Link href={route('login')} className="font-medium text-indigo-600 hover:text-indigo-500">
                    Back to sign in
                </Link>
            }
        >
            <Head title="Two-Factor Authentication" />

            <form onSubmit={submit} className="space-y-4">
                <FloatingField
                    id="code"
                    label="6-digit code"
                    type="text"
                    inputMode="numeric"
                    maxLength={6}
                    value={data.code}
                    onChange={(e) => setData('code', e.target.value)}
                    autoComplete="one-time-code"
                    autoFocus
                    error={errors.code}
                    className="text-lg tracking-[0.5em]"
                />

                <AuthButton disabled={processing}>Verify</AuthButton>
            </form>
        </GuestLayout>
    );
}
