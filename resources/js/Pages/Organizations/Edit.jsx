import Card from '@/Components/UI/Card';
import FileDropzone from '@/Components/FileDropzone';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import OrganizationLogo from '@/Components/OrganizationLogo';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SelectInput from '@/Components/SelectInput';
import TextareaInput from '@/Components/TextareaInput';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { validateFile } from '@/utils/validateFile';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

const LOGO_EXTENSIONS = ['.jpg', '.jpeg', '.png', '.webp'];
const LOGO_MAX_BYTES = 2 * 1024 * 1024;

export default function Edit({ organization, categories, accountManagers }) {
    const [removeLogo, setRemoveLogo] = useState(false);
    const { data, setData, put, processing, errors, setError, clearErrors } = useForm({
        name: organization.name,
        logo: null,
        remove_logo: false,
        category: organization.category,
        website: organization.website ?? '',
        email: organization.email ?? '',
        phone: organization.phone ?? '',
        account_manager_id: organization.account_manager_id ?? '',
        notes: organization.notes ?? '',
    });

    function handleLogoChange(file) {
        const error = validateFile(file, { extensions: LOGO_EXTENSIONS, maxSizeBytes: LOGO_MAX_BYTES });

        if (error) {
            setError('logo', error);
            return;
        }

        clearErrors('logo');
        setRemoveLogo(false);
        setData((prev) => ({ ...prev, logo: file, remove_logo: false }));
    }

    function handleRemoveLogo() {
        setRemoveLogo(true);
        setData((prev) => ({ ...prev, logo: null, remove_logo: true }));
    }

    function submit(e) {
        e.preventDefault();
        put(route('organizations.update', organization.id));
    }

    return (
        <AuthenticatedLayout
            header={`Edit ${organization.name}`}
            back={{ href: route('organizations.show', organization.id), label: `Back to ${organization.name}` }}
        >
            <Head title={`Edit ${organization.name}`} />

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
                        <InputLabel htmlFor="logo" value="Logo" />

                        {organization.logo_url && !removeLogo && !data.logo && (
                            <div className="mt-1 flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                                <OrganizationLogo organization={organization} size="lg" />
                                <button
                                    type="button"
                                    onClick={handleRemoveLogo}
                                    className="text-sm font-medium text-rose-600 hover:text-rose-500"
                                >
                                    Remove Logo
                                </button>
                            </div>
                        )}

                        {(!organization.logo_url || removeLogo || data.logo) && (
                            <div className="mt-1">
                                <FileDropzone
                                    id="logo"
                                    accept="image/*"
                                    value={data.logo}
                                    onChange={handleLogoChange}
                                    hint="JPG, PNG, or WEBP, up to 2 MB"
                                />
                            </div>
                        )}

                        <InputError className="mt-2" message={errors.logo} />
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
                        <PrimaryButton disabled={processing}>Save Changes</PrimaryButton>
                        <Link href={route('organizations.show', organization.id)}>
                            <SecondaryButton type="button">Cancel</SecondaryButton>
                        </Link>
                    </div>
                </form>
            </Card>
        </AuthenticatedLayout>
    );
}
