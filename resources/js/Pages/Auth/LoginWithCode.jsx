import AuthButton from '@/Components/Auth/AuthButton';
import FloatingField from '@/Components/Auth/FloatingField';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function LoginWithCode({ status }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('login.code.store'));
    };

    return (
        <GuestLayout
            title="Stakeholder sign in"
            subtitle="Enter your email and we'll send you a one-time code — no password needed."
            status={status}
            footer={
                <>
                    Team member?{' '}
                    <Link href={route('login')} className="font-medium text-indigo-600 hover:text-indigo-500">
                        Sign in with your password
                    </Link>
                </>
            }
        >
            <Head title="Sign In With a Code" />

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

                <AuthButton disabled={processing}>Email me a code</AuthButton>
            </form>
        </GuestLayout>
    );
}
