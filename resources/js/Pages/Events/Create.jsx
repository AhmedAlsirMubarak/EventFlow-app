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

function slugify(value) {
    return value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
}

export default function Create({ statuses }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        code: '',
        description: '',
        start_date: '',
        end_date: '',
        status: statuses[0] ?? '',
    });

    function submit(e) {
        e.preventDefault();
        post(route('events.store'));
    }

    return (
        <AuthenticatedLayout header="New Event">
            <Head title="New Event" />

            <Card className="max-w-2xl">
                <form onSubmit={submit} className="space-y-6">
                    <div>
                        <InputLabel htmlFor="name" value="Event Name" />
                        <TextInput
                            id="name"
                            className="mt-1 block w-full"
                            value={data.name}
                            onChange={(e) => {
                                setData((prev) => ({
                                    ...prev,
                                    name: e.target.value,
                                    code: prev.code === slugify(prev.name)
                                        ? slugify(e.target.value)
                                        : prev.code,
                                }));
                            }}
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
                            Create Event
                        </PrimaryButton>
                        <Link href={route('events.index')}>
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
