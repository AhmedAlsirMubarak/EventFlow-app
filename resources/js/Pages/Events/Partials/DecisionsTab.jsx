import Card from '@/Components/UI/Card';
import Checkbox from '@/Components/Checkbox';
import DataTable from '@/Components/UI/DataTable';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SelectInput from '@/Components/SelectInput';
import TextareaInput from '@/Components/TextareaInput';
import TextInput from '@/Components/TextInput';
import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';

const emptyDecision = {
    title: '',
    description: '',
    decision_date: '',
    decided_by: '',
    organization_id: '',
    meeting_id: '',
    document_id: '',
    reason: '',
    supporting_document_ids: [],
};

export default function DecisionsTab({
    event,
    decisions,
    availableUsers,
    availableOrganizations,
    meetings,
    documents,
}) {
    const [modalOpen, setModalOpen] = useState(false);
    const [editingDecision, setEditingDecision] = useState(null);
    const [expandedId, setExpandedId] = useState(null);
    const form = useForm(emptyDecision);

    function openCreateModal() {
        setEditingDecision(null);
        form.reset();
        form.clearErrors();
        setModalOpen(true);
    }

    function openEditModal(decision) {
        setEditingDecision(decision);
        form.setData({
            title: decision.title,
            description: decision.description ?? '',
            decision_date: decision.decision_date,
            decided_by: decision.decided_by ?? '',
            organization_id: decision.organization_id ?? '',
            meeting_id: decision.meeting_id ?? '',
            document_id: decision.document_id ?? '',
            reason: decision.reason ?? '',
            supporting_document_ids: decision.supporting_documents.map((d) => d.id),
        });
        form.clearErrors();
        setModalOpen(true);
    }

    function submit(e) {
        e.preventDefault();
        const options = { onSuccess: () => setModalOpen(false) };

        if (editingDecision) {
            form.put(route('decisions.update', editingDecision.id), options);
        } else {
            form.post(route('decisions.store', event.id), options);
        }
    }

    function destroyDecision(decision) {
        if (confirm(`Remove the decision "${decision.title}"?`)) {
            router.delete(route('decisions.destroy', decision.id));
        }
    }

    function toggleSupportingDocument(id) {
        form.setData(
            'supporting_document_ids',
            form.data.supporting_document_ids.includes(id)
                ? form.data.supporting_document_ids.filter((v) => v !== id)
                : [...form.data.supporting_document_ids, id],
        );
    }

    const columns = [
        { key: 'reference', header: 'Reference' },
        { key: 'title', header: 'Title' },
        { key: 'decision_date', header: 'Date' },
        {
            key: 'decided_by',
            header: 'Decided By',
            render: (decision) => decision.decided_by?.name ?? '—',
        },
        {
            key: 'meeting',
            header: 'Meeting',
            render: (decision) => decision.meeting?.title ?? '—',
        },
        {
            key: 'expand',
            header: '',
            render: (decision) => (
                <button
                    type="button"
                    onClick={() =>
                        setExpandedId(expandedId === decision.id ? null : decision.id)
                    }
                    className="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                >
                    {expandedId === decision.id ? 'Hide' : 'Details'}
                </button>
            ),
        },
        {
            key: 'actions',
            header: '',
            render: (decision) => (
                <div className="flex justify-end gap-3 text-sm">
                    <button
                        type="button"
                        onClick={() => openEditModal(decision)}
                        className="font-medium text-indigo-600 hover:text-indigo-500"
                    >
                        Edit
                    </button>
                    <button
                        type="button"
                        onClick={() => destroyDecision(decision)}
                        className="font-medium text-rose-600 hover:text-rose-500"
                    >
                        Remove
                    </button>
                </div>
            ),
        },
    ];

    const expandedDecision = decisions.find((d) => d.id === expandedId) ?? null;

    return (
        <>
            <Card
                title="Decision Log"
                actions={
                    <button
                        type="button"
                        onClick={openCreateModal}
                        className="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500"
                    >
                        Record Decision
                    </button>
                }
                className="p-0"
            >
                <DataTable
                    columns={columns}
                    rows={decisions}
                    emptyMessage="No decisions recorded yet."
                />

                {expandedDecision && (
                    <div className="border-t border-slate-100 bg-slate-50 p-5 text-sm">
                        {expandedDecision.description && (
                            <div className="mb-3">
                                <h4 className="font-medium text-slate-500">Description</h4>
                                <p className="mt-1 whitespace-pre-line text-slate-700">
                                    {expandedDecision.description}
                                </p>
                            </div>
                        )}
                        {expandedDecision.reason && (
                            <div className="mb-3">
                                <h4 className="font-medium text-slate-500">Reason / Justification</h4>
                                <p className="mt-1 whitespace-pre-line text-slate-700">
                                    {expandedDecision.reason}
                                </p>
                            </div>
                        )}
                        <div className="mb-3">
                            <h4 className="font-medium text-slate-500">Related Organization</h4>
                            <p className="mt-1 text-slate-700">
                                {expandedDecision.organization?.name ?? '—'}
                            </p>
                        </div>
                        <div className="mb-3">
                            <h4 className="font-medium text-slate-500">Related Document</h4>
                            <p className="mt-1 text-slate-700">
                                {expandedDecision.related_document?.title ?? '—'}
                            </p>
                        </div>
                        <div>
                            <h4 className="font-medium text-slate-500">Supporting Attachments</h4>
                            {expandedDecision.supporting_documents.length === 0 ? (
                                <p className="mt-1 text-slate-400">None</p>
                            ) : (
                                <ul className="mt-1 list-inside list-disc text-slate-700">
                                    {expandedDecision.supporting_documents.map((doc) => (
                                        <li key={doc.id}>{doc.title}</li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    </div>
                )}
            </Card>

            <Modal show={modalOpen} onClose={() => setModalOpen(false)} maxWidth="lg">
                <form onSubmit={submit} className="max-h-[80vh] space-y-4 overflow-y-auto p-6">
                    <h3 className="text-lg font-semibold text-slate-900">
                        {editingDecision ? 'Edit Decision' : 'Record Decision'}
                    </h3>

                    <div>
                        <InputLabel htmlFor="title" value="Decision Title" />
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

                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel htmlFor="decision_date" value="Decision Date" />
                            <TextInput
                                id="decision_date"
                                type="date"
                                className="mt-1 block w-full"
                                value={form.data.decision_date}
                                onChange={(e) => form.setData('decision_date', e.target.value)}
                                required
                            />
                            <InputError className="mt-2" message={form.errors.decision_date} />
                        </div>
                        <div>
                            <InputLabel htmlFor="decided_by" value="Decided By" />
                            <SelectInput
                                id="decided_by"
                                className="mt-1 block w-full"
                                value={form.data.decided_by}
                                onChange={(e) => form.setData('decided_by', e.target.value)}
                            >
                                <option value="">Not specified</option>
                                {availableUsers.map((user) => (
                                    <option key={user.id} value={user.id}>
                                        {user.name}
                                    </option>
                                ))}
                            </SelectInput>
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
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
                        <div>
                            <InputLabel htmlFor="meeting_id" value="Related Meeting" />
                            <SelectInput
                                id="meeting_id"
                                className="mt-1 block w-full"
                                value={form.data.meeting_id}
                                onChange={(e) => form.setData('meeting_id', e.target.value)}
                            >
                                <option value="">None</option>
                                {meetings.map((meeting) => (
                                    <option key={meeting.id} value={meeting.id}>
                                        {meeting.title}
                                    </option>
                                ))}
                            </SelectInput>
                        </div>
                    </div>

                    <div>
                        <InputLabel htmlFor="document_id" value="Related Document" />
                        <SelectInput
                            id="document_id"
                            className="mt-1 block w-full"
                            value={form.data.document_id}
                            onChange={(e) => form.setData('document_id', e.target.value)}
                        >
                            <option value="">None</option>
                            {documents.map((doc) => (
                                <option key={doc.id} value={doc.id}>
                                    {doc.title}
                                </option>
                            ))}
                        </SelectInput>
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

                    <div>
                        <InputLabel htmlFor="reason" value="Reason / Justification" />
                        <TextareaInput
                            id="reason"
                            rows={2}
                            className="mt-1 block w-full"
                            value={form.data.reason}
                            onChange={(e) => form.setData('reason', e.target.value)}
                        />
                    </div>

                    <div>
                        <InputLabel value="Supporting Attachments" />
                        <div className="mt-2 max-h-32 space-y-1 overflow-y-auto rounded-md border border-gray-200 p-2">
                            {documents.length === 0 && (
                                <p className="text-sm text-slate-400">No documents available yet.</p>
                            )}
                            {documents.map((doc) => (
                                <label key={doc.id} className="flex items-center gap-2 text-sm text-slate-700">
                                    <Checkbox
                                        checked={form.data.supporting_document_ids.includes(doc.id)}
                                        onChange={() => toggleSupportingDocument(doc.id)}
                                    />
                                    {doc.title}
                                </label>
                            ))}
                        </div>
                    </div>

                    <div className="flex items-center gap-3 pt-2">
                        <PrimaryButton
                            disabled={form.processing}
                            className="!bg-indigo-600 !text-sm !normal-case !tracking-normal hover:!bg-indigo-500 focus:!bg-indigo-500 active:!bg-indigo-700"
                        >
                            {editingDecision ? 'Save Changes' : 'Record Decision'}
                        </PrimaryButton>
                        <SecondaryButton
                            type="button"
                            onClick={() => setModalOpen(false)}
                            className="!text-sm !normal-case !tracking-normal"
                        >
                            Cancel
                        </SecondaryButton>
                    </div>
                </form>
            </Modal>
        </>
    );
}
