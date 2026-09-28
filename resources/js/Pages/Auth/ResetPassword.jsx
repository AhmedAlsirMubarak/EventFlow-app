import AuthButton from '@/Components/Auth/AuthButton';
import FloatingField from '@/Components/Auth/FloatingField';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

// Also the landing page for a stakeholder's portal-access invite email, which
// sends them here to choose their first password — hence the neutral wording.
export default function ResetPassword({ token, email }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        token: token,
        email: email,
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('password.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout
            title="Set your password"
            subtitle="Choose a password to finish signing in to EventFlow."
            footer={
                <Link href={route('login')} className="font-medium text-indigo-600 hover:text-indigo-500">
                    Back to sign in
                </Link>
            }
        >
            <Head title="Set Password" />

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
                    id="password"
                    label="New password"
                    type="password"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    autoComplete="new-password"
                    autoFocus
                    error={errors.password}
                />

                <FloatingField
                    id="password_confirmation"
                    label="Confirm new password"
                    type="password"
                    value={data.password_confirmation}
                    onChange={(e) => setData('password_confirmation', e.target.value)}
                    autoComplete="new-password"
                    error={errors.password_confirmation}
                />

                <AuthButton disabled={processing}>Save password</AuthButton>
            </form>
        </GuestLayout>
    );
}
