import AuthButton from '@/Components/Auth/AuthButton';
import FloatingField from '@/Components/Auth/FloatingField';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function VerifyLoginCode({ email, status }) {
    const { data, setData, post, processing, errors } = useForm({
        email,
        code: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('login.code.update'));
    };

    return (
        <GuestLayout
            title="Check your email"
            subtitle={`Enter the 6-digit code we sent to ${email}.`}
            status={status}
            footer={
                <>
                    Didn&apos;t get it?{' '}
                    <Link href={route('login.code')} className="font-medium text-indigo-600 hover:text-indigo-500">
                        Request a new code
                    </Link>
                </>
            }
        >
            <Head title="Enter Your Code" />

            <form onSubmit={submit} className="space-y-4">
                <FloatingField
                    id="email"
                    label="Email"
                    type="email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    autoComplete="username"
                    error={errors.email}
                />

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

                <AuthButton disabled={processing}>Sign in</AuthButton>
            </form>
        </GuestLayout>
    );
}
