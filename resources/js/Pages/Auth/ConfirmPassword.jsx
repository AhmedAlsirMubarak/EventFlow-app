import AuthButton from '@/Components/Auth/AuthButton';
import FloatingField from '@/Components/Auth/FloatingField';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('password.confirm'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout
            title="Confirm your password"
            subtitle="This is a secure area. Please confirm your password before continuing."
        >
            <Head title="Confirm Password" />

            <form onSubmit={submit} className="space-y-4">
                <FloatingField
                    id="password"
                    label="Password"
                    type="password"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    autoComplete="current-password"
                    autoFocus
                    error={errors.password}
                />

                <AuthButton disabled={processing}>Confirm</AuthButton>
            </form>
        </GuestLayout>
    );
}
