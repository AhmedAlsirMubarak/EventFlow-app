import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import DataTable from '@/Components/UI/DataTable';
import FileDropzone from '@/Components/FileDropzone';
import { IconChatBubble } from '@/Components/Icons';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { POLL_INTERVAL } from '@/pollInterval';
import { useConfirm } from '@/utils/useConfirm';
import { validateFile } from '@/utils/validateFile';
import { Head, Link, router, useForm, usePoll } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const CSV_EXTENSIONS = ['.csv', '.txt'];
const CSV_MAX_BYTES = 2 * 1024 * 1024;

const COUNTRY_CODES = [
    { code: '+968', label: '+968 Oman' },
    { code: '+971', label: '+971 UAE' },
    { code: '+966', label: '+966 Saudi Arabia' },
    { code: '+974', label: '+974 Qatar' },
    { code: '+973', label: '+973 Bahrain' },
    { code: '+965', label: '+965 Kuwait' },
    { code: '+20', label: '+20 Egypt' },
    { code: '+962', label: '+962 Jordan' },
    { code: '+961', label: '+961 Lebanon' },
    { code: '+964', label: '+964 Iraq' },
    { code: '+967', label: '+967 Yemen' },
    { code: '+90', label: '+90 Turkey' },
    { code: '+44', label: '+44 UK' },
    { code: '+1', label: '+1 USA / Canada' },
    { code: '+91', label: '+91 India' },
    { code: '+92', label: '+92 Pakistan' },
    { code: '+63', label: '+63 Philippines' },
    { code: '+94', label: '+94 Sri Lanka' },
    { code: '+880', label: '+880 Bangladesh' },
];
const DEFAULT_COUNTRY_CODE = '+968';

function splitMobile(mobile) {
    if (!mobile) {
        return { countryCode: DEFAULT_COUNTRY_CODE, localNumber: '' };
    }

    const compact = mobile.replace(/\s+/g, '');
    const match = [...COUNTRY_CODES]
        .sort((a, b) => b.code.length - a.code.length)
        .find((c) => compact.startsWith(c.code));

    if (match) {
        return { countryCode: match.code, localNumber: compact.slice(match.code.length) };
    }

    return { countryCode: DEFAULT_COUNTRY_CODE, localNumber: mobile };
}

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
    resetLink,
    importErrors,
}) {
    usePoll(POLL_INTERVAL, { preserveScroll: true });

    const [modalOpen, setModalOpen] = useState(false);
    const [editingContact, setEditingContact] = useState(null);
    const [importModalOpen, setImportModalOpen] = useState(false);
    const [contactSearch, setContactSearch] = useState('');
    const importForm = useForm({ file: null });

    const filteredContacts = contacts.filter((contact) => {
        if (!contactSearch.trim()) {
            return true;
        }

        const term = contactSearch.trim().toLowerCase();

        return [contact.full_name, contact.email, contact.job_title, contact.department, contact.mobile]
            .filter(Boolean)
            .some((field) => field.toLowerCase().includes(term));
    });

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
    const [countryCode, setCountryCode] = useState(DEFAULT_COUNTRY_CODE);
    const [localNumber, setLocalNumber] = useState('');

    function handleCountryCodeChange(e) {
        const code = e.target.value;
        setCountryCode(code);
        setData('mobile', localNumber ? `${code} ${localNumber}` : '');
    }

    function handleLocalNumberChange(e) {
        const value = e.target.value;
        setLocalNumber(value);
        setData('mobile', value ? `${countryCode} ${value}` : '');
    }

    function openCreateModal() {
        setEditingContact(null);
        reset();
        clearErrors();
        setCountryCode(DEFAULT_COUNTRY_CODE);
        setLocalNumber('');
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
        const parsed = splitMobile(contact.mobile ?? '');
        setCountryCode(parsed.countryCode);
        setLocalNumber(parsed.localNumber);
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

    const { confirm, dialogProps } = useConfirm();

    function destroyContact(contact) {
        confirm({
            title: 'Remove contact',
            message: `Remove ${contact.full_name} from this organization?`,
            confirmLabel: 'Remove',
            danger: true,
        }).then((confirmed) => {
            if (confirmed) {
                router.delete(route('contacts.destroy', contact.id));
            }
        });
    }

    function openImportModal() {
        importForm.reset();
        importForm.clearErrors();
        setImportModalOpen(true);
    }

    function submitImport(e) {
        e.preventDefault();
        importForm.post(route('organizations.contacts.import', organization.id), {
            onSuccess: () => setImportModalOpen(false),
        });
    }

    function handleImportFileChange(file) {
        const error = validateFile(file, { extensions: CSV_EXTENSIONS, maxSizeBytes: CSV_MAX_BYTES });

        if (error) {
            importForm.setError('file', error);
            return;
        }

        importForm.clearErrors('file');
        importForm.setData('file', file);
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
        {
            key: 'email',
            header: 'Email',
            render: (contact) =>
                contact.email ? (
                    <a
                        href={`mailto:${contact.email}`}
                        className="text-indigo-600 hover:text-indigo-500"
                    >
                        {contact.email}
                    </a>
                ) : (
                    '—'
                ),
        },
        {
            key: 'mobile',
            header: 'Mobile',
            render: (contact) =>
                contact.mobile ? (
                    <span className="flex items-center gap-2">
                        <a href={`tel:${contact.mobile}`} className="text-indigo-600 hover:text-indigo-500">
                            {contact.mobile}
                        </a>
                        {contact.preferred_channel === 'WhatsApp' && (
                            <a
                                href={`https://wa.me/${contact.mobile.replace(/\D/g, '')}`}
                                target="_blank"
                                rel="noopener noreferrer"
                                title="Message on WhatsApp"
                                className="text-emerald-600 hover:text-emerald-500"
                            >
                                <IconChatBubble className="h-4 w-4" />
                            </a>
                        )}
                    </span>
                ) : (
                    '—'
                ),
        },
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
        <AuthenticatedLayout
            header={organization.name}
            back={{ href: route('organizations.index'), label: 'Back to Organizations' }}
        >
            <Head title={organization.name} />

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
                        <div className="flex flex-wrap items-center gap-2">
                            <a
                                href={route('organizations.contacts.export', organization.id)}
                                className="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                            >
                                Export CSV
                            </a>
                            <SecondaryButton type="button" onClick={openImportModal}>
                                Import CSV
                            </SecondaryButton>
                            <button
                                type="button"
                                onClick={openCreateModal}
                                className="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500"
                            >
                                Add Contact
                            </button>
                        </div>
                    }
                    className="p-0"
                >
                    {importErrors?.length > 0 && (
                        <div className="border-b border-amber-100 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            <p className="font-medium">Some rows were skipped:</p>
                            <ul className="mt-1 list-inside list-disc">
                                {importErrors.map((error, index) => (
                                    <li key={index}>{error}</li>
                                ))}
                            </ul>
                        </div>
                    )}
                    <div className="border-b border-slate-100 p-4">
                        <TextInput
                            type="search"
                            placeholder="Search contacts by name, email, title, department, or mobile…"
                            className="block w-full max-w-sm"
                            value={contactSearch}
                            onChange={(e) => setContactSearch(e.target.value)}
                        />
                    </div>
                    <DataTable
                        columns={columns}
                        rows={filteredContacts}
                        emptyMessage={
                            contactSearch.trim()
                                ? 'No contacts match your search.'
                                : 'No contacts recorded for this organization yet.'
                        }
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

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
                        <div className="mt-1 flex gap-2">
                            <SelectInput
                                id="mobile_country_code"
                                className="w-36 shrink-0"
                                value={countryCode}
                                onChange={handleCountryCodeChange}
                            >
                                {COUNTRY_CODES.map((c) => (
                                    <option key={c.code} value={c.code}>
                                        {c.label}
                                    </option>
                                ))}
                            </SelectInput>
                            <TextInput
                                id="mobile"
                                className="block w-full"
                                value={localNumber}
                                onChange={handleLocalNumberChange}
                                placeholder="99822690"
                            />
                        </div>
                        <InputError className="mt-2" message={errors.mobile} />
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
                        <PrimaryButton disabled={processing}>
                            {editingContact ? 'Save Changes' : 'Add Contact'}
                        </PrimaryButton>
                        <SecondaryButton type="button" onClick={closeModal}>
                            Cancel
                        </SecondaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={importModalOpen} onClose={() => setImportModalOpen(false)} maxWidth="md">
                <form onSubmit={submitImport} className="space-y-4 p-6">
                    <h3 className="text-lg font-semibold text-slate-900">Import Contacts from CSV</h3>
                    <p className="text-sm text-slate-600">
                        The first row must be a header including at least{' '}
                        <code className="rounded bg-slate-100 px-1">full_name</code>. Optional columns:{' '}
                        <code className="rounded bg-slate-100 px-1">job_title</code>,{' '}
                        <code className="rounded bg-slate-100 px-1">department</code>,{' '}
                        <code className="rounded bg-slate-100 px-1">email</code>,{' '}
                        <code className="rounded bg-slate-100 px-1">mobile</code>,{' '}
                        <code className="rounded bg-slate-100 px-1">preferred_channel</code>.
                    </p>

                    <a
                        href={route('contacts.import-template')}
                        className="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-500"
                    >
                        Download CSV Template
                    </a>

                    <div>
                        <InputLabel htmlFor="file" value="CSV File" />
                        <div className="mt-1">
                            <FileDropzone
                                id="file"
                                accept=".csv,text/csv"
                                value={importForm.data.file}
                                onChange={handleImportFileChange}
                                hint="CSV file, up to 2 MB"
                            />
                        </div>
                        <InputError className="mt-2" message={importForm.errors.file} />
                    </div>

                    <div className="flex items-center gap-3 pt-2">
                        <PrimaryButton disabled={importForm.processing || !importForm.data.file}>
                            Import
                        </PrimaryButton>
                        <SecondaryButton type="button" onClick={() => setImportModalOpen(false)}>
                            Cancel
                        </SecondaryButton>
                    </div>
                </form>
            </Modal>

            <ConfirmDialog {...dialogProps} />
        </AuthenticatedLayout>
    );
}
