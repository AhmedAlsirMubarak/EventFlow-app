import Card from '@/Components/UI/Card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({ mustVerifyEmail, status }) {
    const { auth } = usePage().props;

    return (
        <AuthenticatedLayout header="Profile">
            <Head title="Profile" />

            <div className="mx-auto max-w-3xl space-y-6">
                <Card>
                    <UpdateProfileInformationForm
                        mustVerifyEmail={mustVerifyEmail}
                        status={status}
                        className="max-w-xl"
                    />
                </Card>

                <Card>
                    <UpdatePasswordForm className="max-w-xl" />
                </Card>

                {auth.user.user_type === 'internal' && (
                    <Card>
                        <div className="max-w-xl">
                            <h2 className="text-lg font-medium text-slate-900">
                                Two-Factor Authentication
                            </h2>
                            <p className="mt-1 text-sm text-slate-600">
                                Add an authenticator-app code as a second sign-in step.
                            </p>
                            <Link
                                href={route('two-factor.show')}
                                className="mt-4 inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                            >
                                Manage Two-Factor Authentication
                            </Link>
                        </div>
                    </Card>
                )}

                <Card>
                    <DeleteUserForm className="max-w-xl" />
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
