import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import DataTable from '@/Components/UI/DataTable';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { POLL_INTERVAL } from '@/pollInterval';
import { Head, Link, router, useForm, usePoll } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const emptyContact = {
    full_name: '',
    job_title: '',
    department: '',
    email: '',
    mobile: '',
    preferred_channel: '',
};

export default function Show({
    organization,
    contacts,
    preferredChannels,
    status,
    resetLink,
}) {
    usePoll(POLL_INTERVAL, { preserveScroll: true });

    const [modalOpen, setModalOpen] = useState(false);
    const [editingContact, setEditingContact] = useState(null);

    // Latched into local state so the one-time flash isn't lost to a
    // background poll refresh before the user has copied it.
    const [displayedResetLink, setDisplayedResetLink] = useState(resetLink);
    useEffect(() => {
        if (resetLink) {
            setDisplayedResetLink(resetLink);
        }
    }, [resetLink]);

    const { data, setData, post, put, processing, errors, reset, clearErrors } =
        useForm(emptyContact);

    function openCreateModal() {
        setEditingContact(null);
        reset();
        clearErrors();
        setModalOpen(true);
    }

    function openEditModal(contact) {
        setEditingContact(contact);
        setData({
            full_name: contact.full_name,
            job_title: contact.job_title ?? '',
            department: contact.department ?? '',
            email: contact.email ?? '',
            mobile: contact.mobile ?? '',
            preferred_channel: contact.preferred_channel ?? '',
        });
        clearErrors();
        setModalOpen(true);
    }

    function closeModal() {
        setModalOpen(false);
    }

    function submit(e) {
        e.preventDefault();

        const options = { onSuccess: closeModal };

        if (editingContact) {
            put(route('contacts.update', editingContact.id), options);
        } else {
            post(
                route('organizations.contacts.store', organization.id),
                options,
            );
        }
    }

    function destroyContact(contact) {
        if (confirm(`Remove ${contact.full_name} from this organization?`)) {
            router.delete(route('contacts.destroy', contact.id));
        }
    }

    function grantPortalAccess(contact) {
        router.post(route('contacts.portal-access', contact.id));
    }

    function resendPortalAccess(contact) {
        router.post(route('contacts.portal-access.resend', contact.id));
    }

    const columns = [
        { key: 'full_name', header: 'Name' },
        { key: 'job_title', header: 'Title' },
        { key: 'email', header: 'Email' },
        { key: 'mobile', header: 'Mobile' },
        {
            key: 'preferred_channel',
            header: 'Preferred Channel',
            render: (contact) =>
                contact.preferred_channel ? (
                    <Badge color="slate">{contact.preferred_channel}</Badge>
                ) : (
                    '—'
                ),
        },
        {
            key: 'portal_access',
            header: 'Portal Access',
            render: (contact) =>
                contact.user_id ? (
                    <div className="flex items-center gap-2">
                        <Badge color="emerald">Active</Badge>
                        <button
                            type="button"
                            onClick={() => resendPortalAccess(contact)}
                            className="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                        >
                            Resend Link
                        </button>
                    </div>
                ) : (
                    <button
                        type="button"
                        onClick={() => grantPortalAccess(contact)}
                        className="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                    >
                        Grant Access
                    </button>
                ),
        },
        {
            key: 'actions',
            header: '',
            render: (contact) => (
                <div className="flex justify-end gap-3 text-sm">
                    <button
                        type="button"
                        onClick={() => openEditModal(contact)}
                        className="font-medium text-indigo-600 hover:text-indigo-500"
                    >
                        Edit
                    </button>
                    <button
                        type="button"
                        onClick={() => destroyContact(contact)}
                        className="font-medium text-rose-600 hover:text-rose-500"
                    >
                        Remove
                    </button>
                </div>
            ),
        },
    ];

    return (
        <AuthenticatedLayout header={organization.name}>
            <Head title={organization.name} />

            {status && (
                <div className="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    {status}
                </div>
            )}

            {displayedResetLink && (
                <div className="mb-4 rounded-lg bg-indigo-50 px-4 py-3 text-sm text-indigo-700">
                    <div className="flex items-start justify-between gap-4">
                        <p className="font-medium">
                            Portal access link (share this with the contact —
                            email delivery isn&apos;t wired up yet):
                        </p>
                        <button
                            type="button"
                            onClick={() => setDisplayedResetLink(null)}
                            className="shrink-0 text-xs font-medium text-indigo-500 hover:text-indigo-700"
                        >
                            Dismiss
                        </button>
                    </div>
                    <p className="mt-1 break-all font-mono text-xs">
                        {displayedResetLink}
                    </p>
                </div>
            )}

            <div className="space-y-6">
                <Card
                    title="Organization Details"
                    actions={
                        <Link
                            href={route('organizations.edit', organization.id)}
                            className="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                        >
                            Edit
                        </Link>
                    }
                >
                    <dl className="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div>
                            <dt className="text-sm text-slate-500">Category</dt>
                            <dd className="mt-1">
                                <Badge color="indigo">
                                    {organization.category}
                                </Badge>
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm text-slate-500">
                                Account Manager
                            </dt>
                            <dd className="mt-1 text-sm text-slate-900">
                                {organization.account_manager?.name ?? '—'}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm text-slate-500">Email</dt>
                            <dd className="mt-1 text-sm text-slate-900">
                                {organization.email ?? '—'}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm text-slate-500">Phone</dt>
                            <dd className="mt-1 text-sm text-slate-900">
                                {organization.phone ?? '—'}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm text-slate-500">Website</dt>
                            <dd className="mt-1 text-sm text-slate-900">
                                {organization.website ?? '—'}
                            </dd>
                        </div>
                        {organization.notes && (
                            <div className="sm:col-span-2">
                                <dt className="text-sm text-slate-500">
                                    Internal Notes
                                </dt>
                                <dd className="mt-1 whitespace-pre-line text-sm text-slate-900">
                                    {organization.notes}
                                </dd>
                            </div>
                        )}
                    </dl>
                </Card>

                <Card
                    title="Contacts"
                    actions={
                        <button
                            type="button"
                            onClick={openCreateModal}
                            className="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500"
                        >
                            Add Contact
                        </button>
                    }
                    className="p-0"
                >
                    <DataTable
                        columns={columns}
                        rows={contacts}
                        emptyMessage="No contacts recorded for this organization yet."
                    />
                </Card>
            </div>

            <Modal show={modalOpen} onClose={closeModal} maxWidth="md">
                <form onSubmit={submit} className="space-y-4 p-6">
                    <h3 className="text-lg font-semibold text-slate-900">
                        {editingContact ? 'Edit Contact' : 'Add Contact'}
                    </h3>

                    <div>
                        <InputLabel htmlFor="full_name" value="Full Name" />
                        <TextInput
                            id="full_name"
                            className="mt-1 block w-full"
                            value={data.full_name}
                            onChange={(e) =>
                                setData('full_name', e.target.value)
                            }
                            required
                            autoFocus
                        />
                        <InputError
                            className="mt-2"
                            message={errors.full_name}
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel htmlFor="job_title" value="Job Title" />
                            <TextInput
                                id="job_title"
                                className="mt-1 block w-full"
                                value={data.job_title}
                                onChange={(e) =>
                                    setData('job_title', e.target.value)
                                }
                            />
                        </div>
                        <div>
                            <InputLabel
                                htmlFor="department"
                                value="Department"
                            />
                            <TextInput
                                id="department"
                                className="mt-1 block w-full"
                                value={data.department}
                                onChange={(e) =>
                                    setData('department', e.target.value)
                                }
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
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
                            <InputLabel htmlFor="mobile" value="Mobile" />
                            <TextInput
                                id="mobile"
                                className="mt-1 block w-full"
                                value={data.mobile}
                                onChange={(e) =>
                                    setData('mobile', e.target.value)
                                }
                            />
                        </div>
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="preferred_channel"
                            value="Preferred Channel"
                        />
                        <SelectInput
                            id="preferred_channel"
                            className="mt-1 block w-full"
                            value={data.preferred_channel}
                            onChange={(e) =>
                                setData('preferred_channel', e.target.value)
                            }
                        >
                            <option value="">Not specified</option>
                            {preferredChannels.map((channel) => (
                                <option key={channel} value={channel}>
                                    {channel}
                                </option>
                            ))}
                        </SelectInput>
                    </div>

                    <div className="flex items-center gap-3 pt-2">
                        <PrimaryButton
                            disabled={processing}
                            className="!bg-indigo-600 !text-sm !normal-case !tracking-normal hover:!bg-indigo-500 focus:!bg-indigo-500 active:!bg-indigo-700"
                        >
                            {editingContact ? 'Save Changes' : 'Add Contact'}
                        </PrimaryButton>
                        <SecondaryButton
                            type="button"
                            onClick={closeModal}
                            className="!text-sm !normal-case !tracking-normal"
                        >
                            Cancel
                        </SecondaryButton>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
