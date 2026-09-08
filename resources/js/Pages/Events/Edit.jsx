import Card from '@/Components/UI/Card';
import EventLogo from '@/Components/EventLogo';
import FileDropzone from '@/Components/FileDropzone';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SelectInput from '@/Components/SelectInput';
import TextareaInput from '@/Components/TextareaInput';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { validateFile } from '@/utils/validateFile';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

const LOGO_EXTENSIONS = ['.jpg', '.jpeg', '.png', '.webp', '.svg'];
const LOGO_MAX_BYTES = 2 * 1024 * 1024;

export default function Edit({ event, statuses }) {
    const [removeLogo, setRemoveLogo] = useState(false);
    const { data, setData, put, processing, errors, setError, clearErrors } = useForm({
        name: event.name,
        code: event.code,
        description: event.description ?? '',
        start_date: event.start_date ?? '',
        end_date: event.end_date ?? '',
        status: event.status,
        logo: null,
        remove_logo: false,
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
        put(route('events.update', event.id));
    }

    return (
        <AuthenticatedLayout header={`Edit ${event.name}`}>
            <Head title={`Edit ${event.name}`} />

            <Card className="max-w-2xl">
                <form onSubmit={submit} className="space-y-6">
                    <div>
                        <InputLabel htmlFor="name" value="Event Name" />
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
                        <InputLabel htmlFor="code" value="Event Code" />
                        <TextInput
                            id="code"
                            className="mt-1 block w-full"
                            value={data.code}
                            onChange={(e) => setData('code', e.target.value)}
                            required
                        />
                        <InputError className="mt-2" message={errors.code} />
                    </div>

                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel
                                htmlFor="start_date"
                                value="Start Date"
                            />
                            <TextInput
                                id="start_date"
                                type="date"
                                className="mt-1 block w-full"
                                value={data.start_date}
                                onChange={(e) =>
                                    setData('start_date', e.target.value)
                                }
                            />
                            <InputError
                                className="mt-2"
                                message={errors.start_date}
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="end_date" value="End Date" />
                            <TextInput
                                id="end_date"
                                type="date"
                                className="mt-1 block w-full"
                                value={data.end_date}
                                onChange={(e) =>
                                    setData('end_date', e.target.value)
                                }
                            />
                            <InputError
                                className="mt-2"
                                message={errors.end_date}
                            />
                        </div>
                    </div>

                    <div>
                        <InputLabel htmlFor="status" value="Status" />
                        <SelectInput
                            id="status"
                            className="mt-1 block w-full"
                            value={data.status}
                            onChange={(e) =>
                                setData('status', e.target.value)
                            }
                        >
                            {statuses.map((status) => (
                                <option key={status} value={status}>
                                    {status}
                                </option>
                            ))}
                        </SelectInput>
                        <InputError className="mt-2" message={errors.status} />
                    </div>

                    <div>
                        <InputLabel htmlFor="logo" value="Event Logo" />

                        {event.logo_url && !removeLogo && !data.logo && (
                            <div className="mt-1 flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                                <EventLogo event={event} size="lg" />
                                <button
                                    type="button"
                                    onClick={handleRemoveLogo}
                                    className="text-sm font-medium text-rose-600 hover:text-rose-500"
                                >
                                    Remove Logo
                                </button>
                            </div>
                        )}

                        {(!event.logo_url || removeLogo || data.logo) && (
                            <div className="mt-1">
                                <FileDropzone
                                    id="logo"
                                    accept="image/*"
                                    value={data.logo}
                                    onChange={handleLogoChange}
                                    hint="JPG, PNG, WEBP, or SVG, up to 2 MB"
                                />
                            </div>
                        )}

                        <InputError className="mt-2" message={errors.logo} />
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="description"
                            value="Description"
                        />
                        <TextareaInput
                            id="description"
                            rows={4}
                            className="mt-1 block w-full"
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                        />
                        <InputError
                            className="mt-2"
                            message={errors.description}
                        />
                    </div>

                    <div className="flex items-center gap-3">
                        <PrimaryButton
                            disabled={processing}
                            className="!bg-indigo-600 !text-sm !normal-case !tracking-normal hover:!bg-indigo-500 focus:!bg-indigo-500 active:!bg-indigo-700"
                        >
                            Save Changes
                        </PrimaryButton>
                        <Link href={route('events.show', event.id)}>
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
