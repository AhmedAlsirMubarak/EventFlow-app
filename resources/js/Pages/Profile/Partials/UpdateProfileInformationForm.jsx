import FileDropzone from '@/Components/FileDropzone';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import UserAvatar from '@/Components/UserAvatar';
import { validateFile } from '@/utils/validateFile';
import { Transition } from '@headlessui/react';
import { Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

const AVATAR_EXTENSIONS = ['.jpg', '.jpeg', '.png', '.webp'];
const AVATAR_MAX_BYTES = 2 * 1024 * 1024;

export default function UpdateProfileInformation({
    mustVerifyEmail,
    status,
    className = '',
}) {
    const user = usePage().props.auth.user;
    const [removeAvatar, setRemoveAvatar] = useState(false);

    const { data, setData, patch, errors, setError, clearErrors, processing, recentlySuccessful } =
        useForm({
            name: user.name,
            email: user.email,
            avatar: null,
            remove_avatar: false,
        });

    function handleAvatarChange(file) {
        const error = validateFile(file, { extensions: AVATAR_EXTENSIONS, maxSizeBytes: AVATAR_MAX_BYTES });

        if (error) {
            setError('avatar', error);
            return;
        }

        clearErrors('avatar');
        setRemoveAvatar(false);
        setData((prev) => ({ ...prev, avatar: file, remove_avatar: false }));
    }

    function handleRemoveAvatar() {
        setRemoveAvatar(true);
        setData((prev) => ({ ...prev, avatar: null, remove_avatar: true }));
    }

    const submit = (e) => {
        e.preventDefault();

        patch(route('profile.update'));
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900">
                    Profile Information
                </h2>

                <p className="mt-1 text-sm text-gray-600">
                    Update your account's profile information and email address.
                </p>
            </header>

            <form onSubmit={submit} className="mt-6 space-y-6">
                <div>
                    <InputLabel htmlFor="avatar" value="Profile Photo" />

                    {user.avatar_url && !removeAvatar && !data.avatar && (
                        <div className="mt-1 flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                            <UserAvatar user={user} size="lg" />
                            <button
                                type="button"
                                onClick={handleRemoveAvatar}
                                className="text-sm font-medium text-rose-600 hover:text-rose-500"
                            >
                                Remove Photo
                            </button>
                        </div>
                    )}

                    {(!user.avatar_url || removeAvatar || data.avatar) && (
                        <div className="mt-1">
                            <FileDropzone
                                id="avatar"
                                accept="image/*"
                                value={data.avatar}
                                onChange={handleAvatarChange}
                                hint="JPG, PNG, or WEBP, up to 2 MB"
                            />
                        </div>
                    )}

                    <InputError className="mt-2" message={errors.avatar} />
                </div>

                <div>
                    <InputLabel htmlFor="name" value="Name" />

                    <TextInput
                        id="name"
                        className="mt-1 block w-full"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        required
                        isFocused
                        autoComplete="name"
                    />

                    <InputError className="mt-2" message={errors.name} />
                </div>

                <div>
                    <InputLabel htmlFor="email" value="Email" />

                    <TextInput
                        id="email"
                        type="email"
                        className="mt-1 block w-full"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        required
                        autoComplete="username"
                    />

                    <InputError className="mt-2" message={errors.email} />
                </div>

                {mustVerifyEmail && user.email_verified_at === null && (
                    <div>
                        <p className="mt-2 text-sm text-gray-800">
                            Your email address is unverified.
                            <Link
                                href={route('verification.send')}
                                method="post"
                                as="button"
                                className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                Click here to re-send the verification email.
                            </Link>
                        </p>

                        {status === 'verification-link-sent' && (
                            <div className="mt-2 text-sm font-medium text-green-600">
                                A new verification link has been sent to your
                                email address.
                            </div>
                        )}
                    </div>
                )}

                <div className="flex items-center gap-4">
                    <PrimaryButton disabled={processing}>Save</PrimaryButton>

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out"
                        leaveTo="opacity-0"
                    >
                        <p className="text-sm text-gray-600">
                            Saved.
                        </p>
                    </Transition>
                </div>
            </form>
        </section>
    );
}
