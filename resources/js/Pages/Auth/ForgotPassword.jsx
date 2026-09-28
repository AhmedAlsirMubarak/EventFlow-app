import AuthButton from '@/Components/Auth/AuthButton';
import FloatingField from '@/Components/Auth/FloatingField';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function ForgotPassword({ status }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('password.email'));
    };

    return (
        <GuestLayout
            title="Forgot your password?"
            subtitle="Enter your email and we'll send you a link to choose a new one."
            status={status}
            footer={
                <>
                    Remembered it?{' '}
                    <Link href={route('login')} className="font-medium text-indigo-600 hover:text-indigo-500">
                        Back to sign in
                    </Link>
                </>
            }
        >
            <Head title="Forgot Password" />

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

                <AuthButton disabled={processing}>Email password reset link</AuthButton>
            </form>
        </GuestLayout>
    );
}
