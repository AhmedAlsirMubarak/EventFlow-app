import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import PortalLayout from '@/Layouts/PortalLayout';
import { Head, useForm } from '@inertiajs/react';

export default function Show({ document, acknowledgment, canDownload }) {
    const { data, setData, post, processing, errors } = useForm({ name: '' });

    function submit(e) {
        e.preventDefault();
        post(route('portal.documents.acknowledge', document.id));
    }

    return (
        <PortalLayout header={document.title} back={{ href: route('portal.dashboard'), label: 'Back to Documents' }}>
            <Head title={document.title} />

            <div className="space-y-6">
                <Card title="Document Details">
                    <dl className="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div>
                            <dt className="text-sm text-slate-500">Category</dt>
                            <dd className="mt-1">
                                <Badge color="indigo">{document.category}</Badge>
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm text-slate-500">Event</dt>
                            <dd className="mt-1 text-sm text-slate-900">
                                {document.event.name}
                            </dd>
                        </div>
                        {document.description && (
                            <div className="sm:col-span-2">
                                <dt className="text-sm text-slate-500">
                                    Description
                                </dt>
                                <dd className="mt-1 whitespace-pre-line text-sm text-slate-900">
                                    {document.description}
                                </dd>
                            </div>
                        )}
                    </dl>

                    {document.current_version && canDownload && (
                        <a
                            href={route(
                                'portal.document-versions.download',
                                document.current_version.id,
                            )}
                            className="mt-6 inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                        >
                            Download {document.current_version.original_filename}
                        </a>
                    )}

                    {document.current_version && !canDownload && (
                        <p className="mt-6 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-700">
                            Please acknowledge this document below before
                            downloading it.
                        </p>
                    )}
                </Card>

                {document.acknowledgment_required && (
                    <Card title="Acknowledgment">
                        {acknowledgment ? (
                            <p className="text-sm text-emerald-700">
                                You acknowledged this document as{' '}
                                <strong>{acknowledgment.name}</strong> on{' '}
                                {acknowledgment.acknowledged_at}.
                            </p>
                        ) : (
                            <form onSubmit={submit} className="space-y-4">
                                <p className="text-sm text-slate-600">
                                    I acknowledge that I have received and
                                    reviewed this document.
                                </p>
                                <div>
                                    <InputLabel
                                        htmlFor="name"
                                        value="Type your full name to confirm"
                                    />
                                    <TextInput
                                        id="name"
                                        className="mt-1 block w-full"
                                        value={data.name}
                                        onChange={(e) =>
                                            setData('name', e.target.value)
                                        }
                                        required
                                    />
                                    <InputError
                                        className="mt-2"
                                        message={errors.name}
                                    />
                                </div>
                                <PrimaryButton disabled={processing}>
                                    Acknowledge Document
                                </PrimaryButton>
                            </form>
                        )}
                    </Card>
                )}
            </div>
        </PortalLayout>
    );
}
