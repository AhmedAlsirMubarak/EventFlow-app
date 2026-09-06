import Card from '@/Components/UI/Card';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SelectInput from '@/Components/SelectInput';
import TextareaInput from '@/Components/TextareaInput';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Create({ categories, accountManagers }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        category: categories[0] ?? '',
        website: '',
        email: '',
        phone: '',
        account_manager_id: '',
        notes: '',
    });

    function submit(e) {
        e.preventDefault();
        post(route('organizations.store'));
    }

    return (
        <AuthenticatedLayout header="New Organization">
            <Head title="New Organization" />

            <Card className="max-w-2xl">
                <form onSubmit={submit} className="space-y-6">
                    <div>
                        <InputLabel htmlFor="name" value="Organization Name" />
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
                        <InputLabel htmlFor="category" value="Category" />
                        <SelectInput
                            id="category"
                            className="mt-1 block w-full"
                            value={data.category}
                            onChange={(e) =>
                                setData('category', e.target.value)
                            }
                        >
                            {categories.map((category) => (
                                <option key={category} value={category}>
                                    {category}
                                </option>
                            ))}
                        </SelectInput>
                        <InputError
                            className="mt-2"
                            message={errors.category}
                        />
                    </div>

                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="email" value="Email" />
                            <TextInput
                                id="email"
                                type="email"
                                className="mt-1 block w-full"
                                value={data.email}
                                onChange={(e) =>
                                    setData('email', e.target.value)
                                }
                            />
                            <InputError
                                className="mt-2"
                                message={errors.email}
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="phone" value="Phone" />
                            <TextInput
                                id="phone"
                                className="mt-1 block w-full"
                                value={data.phone}
                                onChange={(e) =>
                                    setData('phone', e.target.value)
                                }
                            />
                            <InputError
                                className="mt-2"
                                message={errors.phone}
                            />
                        </div>
                    </div>

                    <div>
                        <InputLabel htmlFor="website" value="Website" />
                        <TextInput
                            id="website"
                            className="mt-1 block w-full"
                            placeholder="https://"
                            value={data.website}
                            onChange={(e) =>
                                setData('website', e.target.value)
                            }
                        />
                        <InputError
                            className="mt-2"
                            message={errors.website}
                        />
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="account_manager_id"
                            value="Account Manager"
                        />
                        <SelectInput
                            id="account_manager_id"
                            className="mt-1 block w-full"
                            value={data.account_manager_id}
                            onChange={(e) =>
                                setData('account_manager_id', e.target.value)
                            }
                        >
                            <option value="">Unassigned</option>
                            {accountManagers.map((manager) => (
                                <option key={manager.id} value={manager.id}>
                                    {manager.name}
                                </option>
                            ))}
                        </SelectInput>
                        <InputError
                            className="mt-2"
                            message={errors.account_manager_id}
                        />
                    </div>

                    <div>
                        <InputLabel htmlFor="notes" value="Internal Notes" />
                        <TextareaInput
                            id="notes"
                            rows={4}
                            className="mt-1 block w-full"
                            value={data.notes}
                            onChange={(e) =>
                                setData('notes', e.target.value)
                            }
                        />
                        <InputError className="mt-2" message={errors.notes} />
                    </div>

                    <div className="flex items-center gap-3">
                        <PrimaryButton
                            disabled={processing}
                            className="!bg-indigo-600 !text-sm !normal-case !tracking-normal hover:!bg-indigo-500 focus:!bg-indigo-500 active:!bg-indigo-700"
                        >
                            Create Organization
                        </PrimaryButton>
                        <Link href={route('organizations.index')}>
                            <SecondaryButton
                                type="button"
                                className="!text-sm !normal-case !tracking-normal"
                            >
                                Cancel
                            </SecondaryButton>
                        </Link>
                    </div>
                </form>
            </Card>
        </AuthenticatedLayout>
    );
}
