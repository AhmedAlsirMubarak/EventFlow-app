import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';

export default function TwoFactorChallenge({ status }) {
    const { data, setData, post, processing, errors } = useForm({ code: '' });

    const submit = (e) => {
        e.preventDefault();

        post(route('two-factor.login.store'));
    };

    return (
        <GuestLayout>
            <Head title="Two-Factor Authentication" />

            <div className="mb-4 text-sm text-gray-600">
                Enter the 6-digit code from your authenticator app.
            </div>

            {status && (
                <div className="mb-4 text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <form onSubmit={submit}>
                <div>
                    <InputLabel htmlFor="code" value="6-Digit Code" />

                    <TextInput
                        id="code"
                        type="text"
                        name="code"
                        inputMode="numeric"
                        maxLength={6}
                        value={data.code}
                        className="mt-1 block w-full tracking-widest"
                        autoComplete="one-time-code"
                        isFocused={true}
                        onChange={(e) => setData('code', e.target.value)}
                    />

                    <InputError message={errors.code} className="mt-2" />
                </div>

                <div className="mt-4 flex items-center justify-end">
                    <PrimaryButton className="ms-4" disabled={processing}>
                        Verify
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
