import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import Checkbox from '@/Components/Checkbox';
import DataTable from '@/Components/UI/DataTable';
import FileDropzone from '@/Components/FileDropzone';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SelectInput from '@/Components/SelectInput';
import TextareaInput from '@/Components/TextareaInput';
import TextInput from '@/Components/TextInput';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';
import { useConfirm } from '@/utils/useConfirm';
import { validateFile } from '@/utils/validateFile';
import { router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

const approvalColors = { Pending: 'amber', Approved: 'emerald', Rejected: 'rose' };
const DOCUMENT_FILE_EXTENSIONS = ['.pdf', '.doc', '.docx', '.ppt', '.pptx', '.xls', '.xlsx', '.jpg', '.jpeg', '.png'];
const DOCUMENT_FILE_MAX_BYTES = 20 * 1024 * 1024;
const DOCUMENT_FILE_ACCEPT = '.pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png';
const DOCUMENT_FILE_HINT = 'PDF, Word, PowerPoint, Excel, or image — up to 20 MB';

function currentApprovalFor(document) {
    return document.approvals?.find((a) => a.document_version_id === document.current_version_id) ?? null;
}

function formatBytes(bytes) {
    if (!bytes) {
        return '0 B';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    const exponent = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);

    return `${(bytes / 1024 ** exponent).toFixed(exponent === 0 ? 0 : 1)} ${units[exponent]}`;
}

const emptyDocument = {
    title: '',
    description: '',
    category: '',
    visibility: 'Internal',
    acknowledgment_required: false,
    approval_required: false,
    organization_id: '',
    file: null,
};

export default function DocumentsTab({
    event,
    documents,
    availableOrganizations,
    availableContacts,
    documentCategories,
    documentVisibilities,
}) {
    const { auth } = usePage().props;
    const canReview = auth.roles.includes('Super Administrator') || auth.roles.includes('Management');

    const [modalOpen, setModalOpen] = useState(false);
    const [editingDocument, setEditingDocument] = useState(null);
    const [historyDocumentId, setHistoryDocumentId] = useState(null);
    const [shareDocumentId, setShareDocumentId] = useState(null);
    const [shareType, setShareType] = useState('organization');
    const [approvalDocumentId, setApprovalDocumentId] = useState(null);

    const form = useForm(emptyDocument);
    const versionForm = useForm({ file: null });
    const shareForm = useForm({ organization_id: '', contact_id: '' });
    const [approvalComments, setApprovalComments] = useState('');
    const [approvalProcessing, setApprovalProcessing] = useState(false);
    const { confirm, dialogProps } = useConfirm();

    const historyDocument = documents.find((d) => d.id === historyDocumentId) ?? null;
    const shareDocument = documents.find((d) => d.id === shareDocumentId) ?? null;
    const approvalDocument = documents.find((d) => d.id === approvalDocumentId) ?? null;
    const shareBlocked = shareDocument
        ? shareDocument.approval_required && currentApprovalFor(shareDocument)?.status !== 'Approved'
        : false;

    function openApprovalModal(document) {
        setApprovalDocumentId(document.id);
        setApprovalComments('');
    }

    function decideApproval(status) {
        setApprovalProcessing(true);
        router.put(
            route('documents.approval.update', approvalDocumentId),
            { status, comments: approvalComments },
            {
                preserveScroll: true,
                onSuccess: () => setApprovalDocumentId(null),
                onFinish: () => setApprovalProcessing(false),
            },
        );
    }

    function openShareModal(document) {
        setShareDocumentId(document.id);
        setShareType('organization');
        shareForm.reset();
        shareForm.clearErrors();
    }

    function submitShare(e) {
        e.preventDefault();
        const payload =
            shareType === 'organization'
                ? { organization_id: shareForm.data.organization_id, contact_id: '' }
                : { organization_id: '', contact_id: shareForm.data.contact_id };

        shareForm.transform(() => payload);
        shareForm.post(route('documents.shares.store', shareDocumentId), {
            preserveScroll: true,
            onSuccess: () => shareForm.reset(),
        });
    }

    function revokeShare(share) {
        confirm({
            title: 'Revoke share',
            message: 'Revoke this share? The stakeholder will lose access immediately.',
            confirmLabel: 'Revoke',
            danger: true,
        }).then((confirmed) => {
            if (confirmed) {
                router.delete(route('document-shares.destroy', share.id), { preserveScroll: true });
            }
        });
    }

    function openCreateModal() {
        setEditingDocument(null);
        form.reset();
        form.clearErrors();
        setModalOpen(true);
    }

    function openEditModal(document) {
        setEditingDocument(document);
        form.setData({
            title: document.title,
            description: document.description ?? '',
            category: document.category,
            visibility: document.visibility,
            acknowledgment_required: document.acknowledgment_required,
            approval_required: document.approval_required,
            organization_id: document.organization_id ?? '',
            file: null,
        });
        form.clearErrors();
        setModalOpen(true);
    }

    function submit(e) {
        e.preventDefault();
        const options = { onSuccess: () => setModalOpen(false) };

        if (editingDocument) {
            form.put(route('documents.update', editingDocument.id), options);
        } else {
            form.post(route('documents.store', event.id), options);
        }
    }

    function destroyDocument(document) {
        confirm({
            title: 'Remove document',
            message: `Remove the document "${document.title}"? This deletes every version.`,
            confirmLabel: 'Remove',
            danger: true,
        }).then((confirmed) => {
            if (confirmed) {
                router.delete(route('documents.destroy', document.id));
            }
        });
    }

    function submitNewVersion(e) {
        e.preventDefault();
        versionForm.post(route('documents.versions.store', historyDocumentId), {
            preserveScroll: true,
            onSuccess: () => versionForm.reset(),
        });
    }

    function handleDocumentFileChange(file) {
        const error = validateFile(file, { extensions: DOCUMENT_FILE_EXTENSIONS, maxSizeBytes: DOCUMENT_FILE_MAX_BYTES });

        if (error) {
            form.setError('file', error);
            return;
        }

        form.clearErrors('file');
        form.setData('file', file);
    }

    function handleVersionFileChange(file) {
        const error = validateFile(file, { extensions: DOCUMENT_FILE_EXTENSIONS, maxSizeBytes: DOCUMENT_FILE_MAX_BYTES });

        if (error) {
            versionForm.setError('file', error);
            return;
        }

        versionForm.clearErrors('file');
        versionForm.setData('file', file);
    }

    const columns = [
        { key: 'title', header: 'Title' },
        {
            key: 'category',
            header: 'Category',
            render: (document) => <Badge color="indigo">{document.category}</Badge>,
        },
        {
            key: 'current_version',
            header: 'Current File',
            render: (document) =>
                document.current_version ? (
                    <a
                        href={route('document-versions.download', document.current_version.id)}
                        className="font-medium text-indigo-600 hover:text-indigo-500"
                    >
                        {document.current_version.original_filename}
                    </a>
                ) : (
                    '—'
                ),
        },
        {
            key: 'visibility',
            header: 'Visibility',
            render: (document) => (
                <Badge color={document.visibility === 'External' ? 'amber' : 'slate'}>
                    {document.visibility}
                </Badge>
            ),
        },
        {
            key: 'approval',
            header: 'Approval',
            render: (document) => {
                if (!document.approval_required) {
                    return '—';
                }

                const approval = currentApprovalFor(document);

                return (
                    <button
                        type="button"
                        onClick={() => openApprovalModal(document)}
                        className="inline-flex"
                    >
                        <Badge color={approvalColors[approval?.status] ?? 'slate'}>
                            {approval?.status ?? 'Pending'}
                        </Badge>
                    </button>
                );
            },
        },
        {
            key: 'versions_count',
            header: 'Versions',
            render: (document) => (
                <button
                    type="button"
                    onClick={() => setHistoryDocumentId(document.id)}
                    className="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                >
                    {document.versions_count}
                </button>
            ),
        },
        {
            key: 'shares',
            header: 'Shared With',
            render: (document) => (
                <button
                    type="button"
                    onClick={() => openShareModal(document)}
                    className="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                >
                    {document.shares.length} recipient{document.shares.length === 1 ? '' : 's'}
                </button>
            ),
        },
        {
            key: 'actions',
            header: '',
            render: (document) => (
                <div className="flex justify-end gap-3 text-sm">
                    <button
                        type="button"
                        onClick={() => openEditModal(document)}
                        className="font-medium text-indigo-600 hover:text-indigo-500"
                    >
                        Edit
                    </button>
                    <button
                        type="button"
                        onClick={() => destroyDocument(document)}
                        className="font-medium text-rose-600 hover:text-rose-500"
                    >
                        Remove
                    </button>
                </div>
            ),
        },
    ];

    return (
        <>
            <Card
                title="Documents"
                actions={
                    <button
                        type="button"
                        onClick={openCreateModal}
                        className="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500"
                    >
                        Upload Document
                    </button>
                }
                className="p-0"
            >
                <DataTable
                    columns={columns}
                    rows={documents}
                    emptyMessage="No documents uploaded yet."
                    emptyAction={{ label: 'Upload the first document', onClick: openCreateModal }}
                />
            </Card>

            <Modal show={modalOpen} onClose={() => setModalOpen(false)} maxWidth="lg">
                <form onSubmit={submit} className="space-y-4 p-6">
                    <h3 className="text-lg font-semibold text-slate-900">
                        {editingDocument ? 'Edit Document' : 'Upload Document'}
                    </h3>

                    <div>
                        <InputLabel htmlFor="title" value="Title" />
                        <TextInput
                            id="title"
                            className="mt-1 block w-full"
                            value={form.data.title}
                            onChange={(e) => form.setData('title', e.target.value)}
                            required
                            autoFocus
                        />
                        <InputError className="mt-2" message={form.errors.title} />
                    </div>

                    <div>
                        <InputLabel htmlFor="description" value="Description" />
                        <TextareaInput
                            id="description"
                            rows={2}
                            className="mt-1 block w-full"
                            value={form.data.description}
                            onChange={(e) => form.setData('description', e.target.value)}
                        />
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="category" value="Category" />
                            <SelectInput
                                id="category"
                                className="mt-1 block w-full"
                                value={form.data.category}
                                onChange={(e) => form.setData('category', e.target.value)}
                            >
                                <option value="">Select a category</option>
                                {documentCategories.map((c) => (
                                    <option key={c} value={c}>
                                        {c}
                                    </option>
                                ))}
                            </SelectInput>
                            <InputError className="mt-2" message={form.errors.category} />
                        </div>
                        <div>
                            <InputLabel htmlFor="visibility" value="Visibility" />
                            <SelectInput
                                id="visibility"
                                className="mt-1 block w-full"
                                value={form.data.visibility}
                                onChange={(e) => form.setData('visibility', e.target.value)}
                            >
                                {documentVisibilities.map((v) => (
                                    <option key={v} value={v}>
                                        {v}
                                    </option>
                                ))}
                            </SelectInput>
                        </div>
                    </div>

                    <div>
                        <InputLabel htmlFor="organization_id" value="Related Organization" />
                        <SelectInput
                            id="organization_id"
                            className="mt-1 block w-full"
                            value={form.data.organization_id}
                            onChange={(e) => form.setData('organization_id', e.target.value)}
                        >
                            <option value="">None</option>
                            {availableOrganizations.map((org) => (
                                <option key={org.id} value={org.id}>
                                    {org.name}
                                </option>
                            ))}
                        </SelectInput>
                    </div>

                    <label className="flex items-center gap-2 text-sm text-slate-700">
                        <Checkbox
                            checked={form.data.acknowledgment_required}
                            onChange={(e) =>
                                form.setData('acknowledgment_required', e.target.checked)
                            }
                        />
                        Require external acknowledgment when shared
                    </label>

                    <label className="flex items-center gap-2 text-sm text-slate-700">
                        <Checkbox
                            checked={form.data.approval_required}
                            onChange={(e) =>
                                form.setData('approval_required', e.target.checked)
                            }
                        />
                        Require internal approval before it can be shared
                    </label>

                    {!editingDocument && (
                        <div>
                            <InputLabel htmlFor="file" value="File" />
                            <div className="mt-1">
                                <FileDropzone
                                    id="file"
                                    accept={DOCUMENT_FILE_ACCEPT}
                                    value={form.data.file}
                                    onChange={handleDocumentFileChange}
                                    hint={DOCUMENT_FILE_HINT}
                                />
                            </div>
                            <InputError className="mt-2" message={form.errors.file} />
                        </div>
                    )}

                    <div className="flex items-center gap-3 pt-2">
                        <PrimaryButton disabled={form.processing}>
                            {editingDocument ? 'Save Changes' : 'Upload'}
                        </PrimaryButton>
                        <SecondaryButton type="button" onClick={() => setModalOpen(false)}>
                            Cancel
                        </SecondaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={historyDocument !== null} onClose={() => setHistoryDocumentId(null)} maxWidth="md">
                {historyDocument && (
                    <div className="space-y-4 p-6">
                        <h3 className="text-lg font-semibold text-slate-900">
                            Version History — {historyDocument.title}
                        </h3>

                        <ul className="divide-y divide-slate-100">
                            {historyDocument.versions?.map((version) => (
                                <li key={version.id} className="flex items-center justify-between py-2">
                                    <div>
                                        <a
                                            href={route('document-versions.download', version.id)}
                                            className="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                                        >
                                            v{version.version_number} — {version.original_filename}
                                        </a>
                                        <p className="text-xs text-slate-500">
                                            {formatBytes(version.size)} · uploaded by{' '}
                                            {version.uploader?.name ?? 'Unknown'}
                                        </p>
                                    </div>
                                    {version.id === historyDocument.current_version_id && (
                                        <Badge color="emerald">Current</Badge>
                                    )}
                                </li>
                            ))}
                        </ul>

                        <form onSubmit={submitNewVersion} className="space-y-3 border-t border-slate-100 pt-4">
                            <FileDropzone
                                accept={DOCUMENT_FILE_ACCEPT}
                                value={versionForm.data.file}
                                onChange={handleVersionFileChange}
                                hint={DOCUMENT_FILE_HINT}
                            />
                            <InputError message={versionForm.errors.file} />
                            <PrimaryButton disabled={versionForm.processing || !versionForm.data.file}>
                                Upload New Version
                            </PrimaryButton>
                        </form>
                    </div>
                )}
            </Modal>

            <Modal show={shareDocument !== null} onClose={() => setShareDocumentId(null)} maxWidth="md">
                {shareDocument && (
                    <div className="space-y-4 p-6">
                        <h3 className="text-lg font-semibold text-slate-900">
                            Share — {shareDocument.title}
                        </h3>

                        <ul className="divide-y divide-slate-100">
                            {shareDocument.shares.length === 0 && (
                                <li className="py-2 text-sm text-slate-400">
                                    Not shared with anyone yet.
                                </li>
                            )}
                            {shareDocument.shares.map((share) => (
                                <li key={share.id} className="flex items-center justify-between py-2">
                                    <span className="text-sm text-slate-700">
                                        {share.organization
                                            ? `${share.organization.name} (entire organization)`
                                            : share.contact?.full_name}
                                    </span>
                                    <button
                                        type="button"
                                        onClick={() => revokeShare(share)}
                                        className="text-xs font-medium text-rose-600 hover:text-rose-500"
                                    >
                                        Revoke
                                    </button>
                                </li>
                            ))}
                        </ul>

                        {shareBlocked && (
                            <p className="rounded-lg bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700">
                                This document requires internal approval before it can be shared.
                            </p>
                        )}

                        <form onSubmit={submitShare} className="space-y-3 border-t border-slate-100 pt-4">
                            <p className="text-xs text-slate-500">
                                Only contacts with portal access will be
                                emailed. Grant access from the organization
                                page first if needed.
                            </p>
                            <div className="flex gap-4 text-sm text-slate-700">
                                <label className="flex items-center gap-1.5">
                                    <input
                                        type="radio"
                                        checked={shareType === 'organization'}
                                        onChange={() => setShareType('organization')}
                                    />
                                    Entire organization
                                </label>
                                <label className="flex items-center gap-1.5">
                                    <input
                                        type="radio"
                                        checked={shareType === 'contact'}
                                        onChange={() => setShareType('contact')}
                                    />
                                    Specific contact
                                </label>
                            </div>

                            {shareType === 'organization' ? (
                                <SelectInput
                                    className="block w-full"
                                    value={shareForm.data.organization_id}
                                    onChange={(e) =>
                                        shareForm.setData('organization_id', e.target.value)
                                    }
                                >
                                    <option value="">Select an organization</option>
                                    {availableOrganizations.map((org) => (
                                        <option key={org.id} value={org.id}>
                                            {org.name}
                                        </option>
                                    ))}
                                </SelectInput>
                            ) : (
                                <SelectInput
                                    className="block w-full"
                                    value={shareForm.data.contact_id}
                                    onChange={(e) =>
                                        shareForm.setData('contact_id', e.target.value)
                                    }
                                >
                                    <option value="">Select a contact</option>
                                    {availableContacts.map((contact) => (
                                        <option key={contact.id} value={contact.id}>
                                            {contact.full_name}
                                        </option>
                                    ))}
                                </SelectInput>
                            )}
                            <InputError
                                message={shareForm.errors.organization_id || shareForm.errors.contact_id}
                            />

                            <PrimaryButton disabled={shareForm.processing || shareBlocked}>
                                Share
                            </PrimaryButton>
                        </form>
                    </div>
                )}
            </Modal>

            <Modal show={approvalDocument !== null} onClose={() => setApprovalDocumentId(null)} maxWidth="md">
                {approvalDocument && (() => {
                    const approval = currentApprovalFor(approvalDocument);

                    return (
                        <div className="space-y-4 p-6">
                            <h3 className="text-lg font-semibold text-slate-900">
                                Approval — {approvalDocument.title}
                            </h3>

                            <div className="flex items-center gap-2 text-sm">
                                <span className="text-slate-500">Status:</span>
                                <Badge color={approvalColors[approval?.status] ?? 'slate'}>
                                    {approval?.status ?? 'Pending'}
                                </Badge>
                            </div>

                            {approval?.requester && (
                                <p className="text-xs text-slate-500">
                                    Requested by {approval.requester.name}
                                </p>
                            )}

                            {approval?.reviewer && (
                                <p className="text-xs text-slate-500">
                                    Reviewed by {approval.reviewer.name}
                                </p>
                            )}

                            {approval?.comments && (
                                <p className="whitespace-pre-line rounded-lg bg-slate-50 p-3 text-sm text-slate-700">
                                    {approval.comments}
                                </p>
                            )}

                            {approval?.status === 'Pending' && canReview && (
                                <div className="space-y-3 border-t border-slate-100 pt-4">
                                    <TextareaInput
                                        rows={2}
                                        className="block w-full"
                                        placeholder="Comments (optional)"
                                        value={approvalComments}
                                        onChange={(e) => setApprovalComments(e.target.value)}
                                    />
                                    <div className="flex items-center gap-3">
                                        <PrimaryButton
                                            type="button"
                                            variant="success"
                                            disabled={approvalProcessing}
                                            onClick={() => decideApproval('Approved')}
                                        >
                                            Approve
                                        </PrimaryButton>
                                        <SecondaryButton
                                            type="button"
                                            variant="danger"
                                            disabled={approvalProcessing}
                                            onClick={() => decideApproval('Rejected')}
                                        >
                                            Reject
                                        </SecondaryButton>
                                    </div>
                                </div>
                            )}

                            {approval?.status === 'Pending' && !canReview && (
                                <p className="text-xs text-slate-400">
                                    Waiting for a Management or Super Administrator review.
                                </p>
                            )}
                        </div>
                    );
                })()}
            </Modal>

            <ConfirmDialog {...dialogProps} />
        </>
    );
}
