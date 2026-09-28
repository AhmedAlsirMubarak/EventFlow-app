import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import CheckboxSearchList from '@/Components/UI/CheckboxSearchList';
import DataTable from '@/Components/UI/DataTable';
import { IconCalendar, IconVideoCamera } from '@/Components/Icons';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SearchableSelect from '@/Components/SearchableSelect';
import SelectInput from '@/Components/SelectInput';
import TextareaInput from '@/Components/TextareaInput';
import TextInput from '@/Components/TextInput';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';
import { useConfirm } from '@/utils/useConfirm';
import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';

const priorityColors = { Low: 'slate', Medium: 'indigo', High: 'amber', Urgent: 'rose' };
const providerColors = { 'Microsoft Teams': 'indigo', Zoom: 'indigo', 'Google Meet': 'emerald' };

const emptyMeeting = {
    title: '',
    held_at: '',
    location: '',
    meeting_link: '',
    organization_id: '',
    agenda: '',
    discussion_notes: '',
    participant_ids: [],
    external_participant_ids: [],
};

function toggleId(list, id) {
    return list.includes(id) ? list.filter((v) => v !== id) : [...list, id];
}

// datetime-local inputs show/accept naive local wall-clock time with no timezone
// info, but the backend stores/returns UTC — convert explicitly at both edges so a
// value round-trips to the same wall-clock time the user actually picked.
function toDatetimeLocal(isoString) {
    if (!isoString) {
        return '';
    }

    const date = new Date(isoString);
    const localTime = new Date(date.getTime() - date.getTimezoneOffset() * 60000);

    return localTime.toISOString().slice(0, 16);
}

function fromDatetimeLocal(localString) {
    return localString ? new Date(localString).toISOString() : null;
}

export default function MeetingsTab({
    event,
    meetings,
    availableUsers,
    availableContacts,
    availableOrganizations,
    documents,
    taskPriorities,
}) {
    const [modalOpen, setModalOpen] = useState(false);
    const [editingMeeting, setEditingMeeting] = useState(null);
    const [detailsMeetingId, setDetailsMeetingId] = useState(null);

    const form = useForm(emptyMeeting);
    const actionItemForm = useForm({ title: '', assigned_to: '', priority: 'Medium', due_date: '' });
    const documentForm = useForm({ document_id: '' });
    const { confirm, dialogProps } = useConfirm();

    const detailsMeeting = meetings.find((m) => m.id === detailsMeetingId) ?? null;
    const attachableDocuments = detailsMeeting
        ? documents.filter((d) => !detailsMeeting.documents.some((md) => md.id === d.id))
        : [];

    function openCreateModal() {
        setEditingMeeting(null);
        form.reset();
        form.clearErrors();
        setModalOpen(true);
    }

    function openEditModal(meeting) {
        setEditingMeeting(meeting);
        form.setData({
            title: meeting.title,
            held_at: toDatetimeLocal(meeting.held_at),
            location: meeting.location ?? '',
            meeting_link: meeting.meeting_link ?? '',
            organization_id: meeting.organization_id ?? '',
            agenda: meeting.agenda ?? '',
            discussion_notes: meeting.discussion_notes ?? '',
            participant_ids: meeting.participants.map((p) => p.id),
            external_participant_ids: meeting.external_participants.map((p) => p.id),
        });
        form.clearErrors();
        setModalOpen(true);
    }

    function submit(e) {
        e.preventDefault();
        const options = { onSuccess: () => setModalOpen(false) };

        form.transform((data) => ({ ...data, held_at: fromDatetimeLocal(data.held_at) }));

        if (editingMeeting) {
            form.put(route('meetings.update', editingMeeting.id), options);
        } else {
            form.post(route('meetings.store', event.id), options);
        }
    }

    function destroyMeeting(meeting) {
        confirm({
            title: 'Remove meeting',
            message: `Remove the meeting "${meeting.title}"?`,
            confirmLabel: 'Remove',
            danger: true,
        }).then((confirmed) => {
            if (confirmed) {
                router.delete(route('meetings.destroy', meeting.id));
            }
        });
    }

    function openDetails(meeting) {
        setDetailsMeetingId(meeting.id);
        actionItemForm.reset();
        documentForm.reset();
    }

    function submitActionItem(e) {
        e.preventDefault();
        actionItemForm.post(route('meetings.action-items.store', detailsMeetingId), {
            preserveScroll: true,
            onSuccess: () => actionItemForm.reset(),
        });
    }

    function submitAttachDocument(e) {
        e.preventDefault();
        if (!documentForm.data.document_id) {
            return;
        }
        documentForm.post(route('meetings.documents.store', detailsMeetingId), {
            preserveScroll: true,
            onSuccess: () => documentForm.reset(),
        });
    }

    function detachDocument(doc) {
        router.delete(route('meetings.documents.destroy', [detailsMeetingId, doc.id]), {
            preserveScroll: true,
        });
    }

    const columns = [
        {
            key: 'title',
            header: 'Title',
            render: (meeting) => (
                <div className="flex items-center gap-2">
                    <span>{meeting.title}</span>
                    {meeting.provider && (
                        <Badge color={providerColors[meeting.provider] ?? 'slate'}>{meeting.provider}</Badge>
                    )}
                </div>
            ),
        },
        {
            key: 'held_at',
            header: 'Date & Time',
            render: (meeting) => new Date(meeting.held_at).toLocaleString(undefined, {
                dateStyle: 'medium',
                timeStyle: 'short',
            }),
        },
        { key: 'location', header: 'Location', render: (meeting) => meeting.location ?? '—' },
        {
            key: 'organization',
            header: 'Organization',
            render: (meeting) => meeting.organization?.name ?? '—',
        },
        {
            key: 'action_items',
            header: 'Action Items',
            render: (meeting) => (
                <button
                    type="button"
                    onClick={() => openDetails(meeting)}
                    className="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                >
                    {meeting.action_items.length}
                </button>
            ),
        },
        {
            key: 'actions',
            header: '',
            render: (meeting) => (
                <div className="flex justify-end gap-3 text-sm">
                    {meeting.meeting_link && (
                        <a
                            href={meeting.meeting_link}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center gap-1 font-medium text-indigo-600 hover:text-indigo-500"
                        >
                            <IconVideoCamera className="h-4 w-4" />
                            Join
                        </a>
                    )}
                    <button
                        type="button"
                        onClick={() => openEditModal(meeting)}
                        className="font-medium text-indigo-600 hover:text-indigo-500"
                    >
                        Edit
                    </button>
                    <button
                        type="button"
                        onClick={() => destroyMeeting(meeting)}
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
                title="Meetings"
                actions={
                    <button
                        type="button"
                        onClick={openCreateModal}
                        className="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500"
                    >
                        Record Meeting
                    </button>
                }
                className="p-0"
            >
                <DataTable
                    columns={columns}
                    rows={meetings}
                    emptyMessage="No meetings recorded yet."
                    emptyAction={{ label: 'Record the first meeting', onClick: openCreateModal }}
                />
            </Card>

            <Modal show={modalOpen} onClose={() => setModalOpen(false)} maxWidth="lg">
                <form onSubmit={submit} className="max-h-[80vh] space-y-4 overflow-y-auto p-6">
                    <h3 className="text-lg font-semibold text-slate-900">
                        {editingMeeting ? 'Edit Meeting' : 'Record Meeting'}
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

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="held_at" value="Date & Time" />
                            <TextInput
                                id="held_at"
                                type="datetime-local"
                                className="mt-1 block w-full"
                                value={form.data.held_at}
                                onChange={(e) => form.setData('held_at', e.target.value)}
                                required
                            />
                            <InputError className="mt-2" message={form.errors.held_at} />
                        </div>
                        <div>
                            <InputLabel htmlFor="location" value="Location" />
                            <TextInput
                                id="location"
                                className="mt-1 block w-full"
                                placeholder="Online / Room / Venue"
                                value={form.data.location}
                                onChange={(e) => form.setData('location', e.target.value)}
                            />
                        </div>
                    </div>

                    <div>
                        <InputLabel htmlFor="meeting_link" value="Meeting Link (optional)" />
                        <TextInput
                            id="meeting_link"
                            type="url"
                            className="mt-1 block w-full"
                            placeholder="https://teams.microsoft.com/l/meetup-join/..."
                            value={form.data.meeting_link}
                            onChange={(e) => form.setData('meeting_link', e.target.value)}
                        />
                        <p className="mt-1 text-xs text-slate-400">
                            Paste your Microsoft Teams, Zoom, or Google Meet link.
                        </p>
                        <InputError className="mt-2" message={form.errors.meeting_link} />
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

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel value="Internal Participants" />
                            <CheckboxSearchList
                                items={availableUsers.map((user) => ({ id: user.id, label: user.name }))}
                                selectedIds={form.data.participant_ids}
                                onToggle={(id) =>
                                    form.setData('participant_ids', toggleId(form.data.participant_ids, id))
                                }
                                emptyMessage="No internal users yet."
                                searchPlaceholder="Search people…"
                            />
                        </div>
                        <div>
                            <InputLabel value="External Participants" />
                            <CheckboxSearchList
                                items={availableContacts.map((contact) => ({
                                    id: contact.id,
                                    label: contact.full_name,
                                }))}
                                selectedIds={form.data.external_participant_ids}
                                onToggle={(id) =>
                                    form.setData(
                                        'external_participant_ids',
                                        toggleId(form.data.external_participant_ids, id),
                                    )
                                }
                                emptyMessage="No contacts yet."
                                searchPlaceholder="Search contacts…"
                            />
                        </div>
                    </div>

                    <div>
                        <InputLabel htmlFor="agenda" value="Agenda" />
                        <TextareaInput
                            id="agenda"
                            rows={2}
                            className="mt-1 block w-full"
                            value={form.data.agenda}
                            onChange={(e) => form.setData('agenda', e.target.value)}
                        />
                    </div>

                    <div>
                        <InputLabel htmlFor="discussion_notes" value="Discussion Notes" />
                        <TextareaInput
                            id="discussion_notes"
                            rows={3}
                            className="mt-1 block w-full"
                            value={form.data.discussion_notes}
                            onChange={(e) => form.setData('discussion_notes', e.target.value)}
                        />
                    </div>

                    <div className="flex items-center gap-3 pt-2">
                        <PrimaryButton disabled={form.processing}>
                            {editingMeeting ? 'Save Changes' : 'Record Meeting'}
                        </PrimaryButton>
                        <SecondaryButton type="button" onClick={() => setModalOpen(false)}>
                            Cancel
                        </SecondaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={detailsMeeting !== null} onClose={() => setDetailsMeetingId(null)} maxWidth="lg">
                {detailsMeeting && (
                    <div className="max-h-[80vh] space-y-6 overflow-y-auto p-6">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h3 className="text-lg font-semibold text-slate-900">
                                    {detailsMeeting.title}
                                </h3>
                                <p className="mt-1 text-sm text-slate-500">
                                    {new Date(detailsMeeting.held_at).toLocaleString(undefined, {
                                        dateStyle: 'medium',
                                        timeStyle: 'short',
                                    })}
                                    {detailsMeeting.location && ` · ${detailsMeeting.location}`}
                                </p>
                            </div>
                            <a
                                href={route('meetings.calendar', detailsMeeting.id)}
                                className="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-700"
                            >
                                <IconCalendar className="h-4 w-4" />
                                Add to Calendar
                            </a>
                        </div>

                        {detailsMeeting.meeting_link && (
                            <a href={detailsMeeting.meeting_link} target="_blank" rel="noopener noreferrer">
                                <PrimaryButton type="button" className="w-full justify-center">
                                    <IconVideoCamera className="h-4 w-4" />
                                    Join {detailsMeeting.provider} Meeting
                                </PrimaryButton>
                            </a>
                        )}

                        {detailsMeeting.discussion_notes && (
                            <div>
                                <h4 className="text-sm font-medium text-slate-500">Discussion Notes</h4>
                                <p className="mt-1 whitespace-pre-line text-sm text-slate-700">
                                    {detailsMeeting.discussion_notes}
                                </p>
                            </div>
                        )}

                        <div>
                            <h4 className="text-sm font-medium text-slate-500">Action Items</h4>
                            <ul className="mt-2 space-y-2">
                                {detailsMeeting.action_items.length === 0 && (
                                    <li className="text-sm text-slate-400">No action items yet.</li>
                                )}
                                {detailsMeeting.action_items.map((item) => (
                                    <li key={item.id} className="flex items-center justify-between rounded-lg bg-slate-50 p-2 text-sm">
                                        <span>{item.title}</span>
                                        <div className="flex items-center gap-2">
                                            <Badge color={priorityColors[item.priority] ?? 'slate'}>
                                                {item.priority}
                                            </Badge>
                                            <span className="text-slate-500">
                                                {item.assignee?.name ?? 'Unassigned'}
                                            </span>
                                        </div>
                                    </li>
                                ))}
                            </ul>

                            <form onSubmit={submitActionItem} className="mt-3 space-y-2 border-t border-slate-100 pt-3">
                                <TextInput
                                    className="block w-full"
                                    placeholder="New action item title"
                                    value={actionItemForm.data.title}
                                    onChange={(e) => actionItemForm.setData('title', e.target.value)}
                                />
                                <div className="grid grid-cols-1 gap-2 sm:grid-cols-3">
                                    <SearchableSelect
                                        value={actionItemForm.data.assigned_to}
                                        onChange={(value) => actionItemForm.setData('assigned_to', value)}
                                        options={availableUsers.map((user) => ({ value: user.id, label: user.name }))}
                                        emptyLabel="Unassigned"
                                    />
                                    <SelectInput
                                        value={actionItemForm.data.priority}
                                        onChange={(e) => actionItemForm.setData('priority', e.target.value)}
                                    >
                                        {taskPriorities.map((p) => (
                                            <option key={p} value={p}>
                                                {p}
                                            </option>
                                        ))}
                                    </SelectInput>
                                    <TextInput
                                        type="date"
                                        value={actionItemForm.data.due_date}
                                        onChange={(e) => actionItemForm.setData('due_date', e.target.value)}
                                    />
                                </div>
                                <PrimaryButton
                                    disabled={actionItemForm.processing || !actionItemForm.data.title}
                                >
                                    Add Action Item
                                </PrimaryButton>
                            </form>
                        </div>

                        <div>
                            <h4 className="text-sm font-medium text-slate-500">Attachments</h4>
                            <ul className="mt-2 space-y-1">
                                {detailsMeeting.documents.length === 0 && (
                                    <li className="text-sm text-slate-400">No documents attached.</li>
                                )}
                                {detailsMeeting.documents.map((doc) => (
                                    <li key={doc.id} className="flex items-center justify-between text-sm">
                                        <span>{doc.title}</span>
                                        <button
                                            type="button"
                                            onClick={() => detachDocument(doc)}
                                            className="text-xs font-medium text-rose-600 hover:text-rose-500"
                                        >
                                            Remove
                                        </button>
                                    </li>
                                ))}
                            </ul>

                            {attachableDocuments.length > 0 ? (
                                <form onSubmit={submitAttachDocument} className="mt-3 flex gap-2 border-t border-slate-100 pt-3">
                                    <SelectInput
                                        className="block w-full"
                                        value={documentForm.data.document_id}
                                        onChange={(e) => documentForm.setData('document_id', e.target.value)}
                                    >
                                        <option value="">Select a document</option>
                                        {attachableDocuments.map((doc) => (
                                            <option key={doc.id} value={doc.id}>
                                                {doc.title}
                                            </option>
                                        ))}
                                    </SelectInput>
                                    <PrimaryButton disabled={documentForm.processing}>
                                        Attach
                                    </PrimaryButton>
                                </form>
                            ) : (
                                <p className="mt-3 border-t border-slate-100 pt-3 text-xs text-slate-400">
                                    {documents.length === 0
                                        ? 'Upload a document in the Documents tab, then attach it here.'
                                        : 'Every document in this event is already attached.'}
                                </p>
                            )}
                        </div>
                    </div>
                )}
            </Modal>

            <ConfirmDialog {...dialogProps} />
        </>
    );
}
