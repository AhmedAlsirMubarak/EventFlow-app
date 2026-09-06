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

export default function Edit({ event, statuses }) {
    const { data, setData, put, processing, errors } = useForm({
        name: event.name,
        code: event.code,
        description: event.description ?? '',
        start_date: event.start_date ?? '',
        end_date: event.end_date ?? '',
        status: event.status,
    });

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
                            className="bg-indigo-600 text-sm normal-case tracking-normal hover:bg-indigo-500 focus:bg-indigo-500 active:bg-indigo-700"
                        >
                            Save Changes
                        </PrimaryButton>
                        <Link href={route('events.show', event.id)}>
                            <SecondaryButton
                                type="button"
                                className="text-sm normal-case tracking-normal"
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
