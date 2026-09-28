import AuthButton from '@/Components/Auth/AuthButton';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function VerifyEmail({ status }) {
    const { post, processing } = useForm({});

    const submit = (e) => {
        e.preventDefault();

        post(route('verification.send'));
    };

    return (
        <GuestLayout
            title="Verify your email"
            subtitle="Click the link we emailed you to verify your address. Didn't get it? We can send another."
            status={
                status === 'verification-link-sent'
                    ? 'A new verification link has been sent to your email address.'
                    : null
            }
            footer={
                <Link
                    href={route('logout')}
                    method="post"
                    as="button"
                    className="font-medium text-indigo-600 hover:text-indigo-500"
                >
                    Log out
                </Link>
            }
        >
            <Head title="Email Verification" />

            <form onSubmit={submit}>
                <AuthButton disabled={processing}>Resend verification email</AuthButton>
            </form>
        </GuestLayout>
    );
}
