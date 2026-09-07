import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
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
        <GuestLayout>
            <Head title="Enter Your Code" />

            <div className="mb-4 text-sm text-gray-600">
                Enter the 6-digit code we emailed to {email}.
            </div>

            {status && (
                <div className="mb-4 text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <form onSubmit={submit}>
                <div>
                    <InputLabel htmlFor="email" value="Email" />

                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full"
                        autoComplete="username"
                        onChange={(e) => setData('email', e.target.value)}
                    />

                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div className="mt-4">
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

                <div className="mt-4 flex items-center justify-between">
                    <Link
                        href={route('login.code')}
                        className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Request a new code
                    </Link>

                    <PrimaryButton className="ms-4" disabled={processing}>
                        Sign In
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
