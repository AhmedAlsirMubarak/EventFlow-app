import Card from '@/Components/UI/Card';
import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Edit({ user, assignedRoles, availableRoles }) {
    const { data, setData, put, processing, errors } = useForm({
        name: user.name,
        email: user.email,
        password: '',
        password_confirmation: '',
        roles: assignedRoles,
    });

    function toggleRole(role) {
        setData(
            'roles',
            data.roles.includes(role)
                ? data.roles.filter((r) => r !== role)
                : [...data.roles, role],
        );
    }

    function submit(e) {
        e.preventDefault();
        put(route('admin.users.update', user.id));
    }

    return (
        <AuthenticatedLayout
            header={`Edit ${user.name}`}
            back={{ href: route('admin.users.index'), label: 'Back to Users' }}
        >
            <Head title={`Edit ${user.name}`} />

            <Card className="max-w-2xl">
                <form onSubmit={submit} className="space-y-6">
                    <div>
                        <InputLabel htmlFor="name" value="Name" />
                        <TextInput
                            id="name"
                            className="mt-1 block w-full"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            required
                            autoFocus
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
                        />
                        <InputError className="mt-2" message={errors.email} />
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="password"
                            value="New Password (optional)"
                        />
                        <TextInput
                            id="password"
                            type="password"
                            className="mt-1 block w-full"
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                            placeholder="Leave blank to keep current password"
                        />
                        <InputError
                            className="mt-2"
                            message={errors.password}
                        />
                    </div>

                    {data.password && (
                        <div>
                            <InputLabel
                                htmlFor="password_confirmation"
                                value="Confirm New Password"
                            />
                            <TextInput
                                id="password_confirmation"
                                type="password"
                                className="mt-1 block w-full"
                                value={data.password_confirmation}
                                onChange={(e) =>
                                    setData(
                                        'password_confirmation',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
                    )}

                    <div>
                        <InputLabel value="Roles" />
                        <div className="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                            {availableRoles.map((role) => (
                                <label
                                    key={role}
                                    className="flex items-center gap-2 text-sm text-slate-700"
                                >
                                    <Checkbox
                                        checked={data.roles.includes(role)}
                                        onChange={() => toggleRole(role)}
                                    />
                                    {role}
                                </label>
                            ))}
                        </div>
                        <InputError className="mt-2" message={errors.roles} />
                    </div>

                    <div className="flex items-center gap-3">
                        <PrimaryButton disabled={processing}>Save Changes</PrimaryButton>
                        <Link href={route('admin.users.index')}>
                            <SecondaryButton type="button">Cancel</SecondaryButton>
                        </Link>
                    </div>
                </form>
            </Card>
        </AuthenticatedLayout>
    );
}
