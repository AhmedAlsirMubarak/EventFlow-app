import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import DataTable from '@/Components/UI/DataTable';
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
import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';

const statusColors = { Pending: 'amber', Completed: 'emerald', Dismissed: 'slate' };

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

const emptyReminder = {
    title: '',
    notes: '',
    remind_at: '',
    organization_id: '',
    contact_id: '',
    assigned_to: '',
    status: 'Pending',
};

function isOverdue(reminder) {
    return reminder.status === 'Pending' && new Date(reminder.remind_at) < new Date();
}

export default function RemindersTab({
    event,
    reminders,
    availableUsers,
    availableOrganizations,
    availableContacts,
    reminderStatuses,
}) {
    const [modalOpen, setModalOpen] = useState(false);
    const [editingReminder, setEditingReminder] = useState(null);
    const form = useForm(emptyReminder);
    const { confirm, dialogProps } = useConfirm();

    function openCreateModal() {
        setEditingReminder(null);
        form.reset();
        form.clearErrors();
        setModalOpen(true);
    }

    function openEditModal(reminder) {
        setEditingReminder(reminder);
        form.setData({
            title: reminder.title,
            notes: reminder.notes ?? '',
            remind_at: toDatetimeLocal(reminder.remind_at),
            organization_id: reminder.organization_id ?? '',
            contact_id: reminder.contact_id ?? '',
            assigned_to: reminder.assigned_to ?? '',
            status: reminder.status,
        });
        form.clearErrors();
        setModalOpen(true);
    }

    function submit(e) {
        e.preventDefault();
        const options = { onSuccess: () => setModalOpen(false) };

        form.transform((data) => ({ ...data, remind_at: fromDatetimeLocal(data.remind_at) }));

        if (editingReminder) {
            form.put(route('reminders.update', editingReminder.id), options);
        } else {
            form.post(route('reminders.store', event.id), options);
        }
    }

    function destroyReminder(reminder) {
        confirm({
            title: 'Remove reminder',
            message: `Remove the reminder "${reminder.title}"?`,
            confirmLabel: 'Remove',
            danger: true,
        }).then((confirmed) => {
            if (confirmed) {
                router.delete(route('reminders.destroy', reminder.id));
            }
        });
    }

    function setStatus(reminder, status) {
        router.put(
            route('reminders.update', reminder.id),
            {
                title: reminder.title,
                notes: reminder.notes ?? '',
                remind_at: reminder.remind_at,
                organization_id: reminder.organization_id ?? '',
                contact_id: reminder.contact_id ?? '',
                assigned_to: reminder.assigned_to ?? '',
                status,
            },
            { preserveScroll: true },
        );
    }

    const columns = [
        {
            key: 'title',
            header: 'Title',
            render: (reminder) => (
                <div>
                    <span className="font-medium text-slate-900">{reminder.title}</span>
                    {isOverdue(reminder) && (
                        <span className="ml-2">
                            <Badge color="rose">Overdue</Badge>
                        </span>
                    )}
                </div>
            ),
        },
        {
            key: 'context',
            header: 'Context',
            render: (reminder) => reminder.contact?.full_name ?? reminder.organization?.name ?? '—',
        },
        {
            key: 'assignee',
            header: 'Assigned To',
            render: (reminder) => reminder.assignee?.name ?? 'Unassigned',
        },
        {
            key: 'remind_at',
            header: 'Due',
            render: (reminder) =>
                new Date(reminder.remind_at).toLocaleString(undefined, {
                    dateStyle: 'medium',
                    timeStyle: 'short',
                }),
        },
        {
            key: 'status',
            header: 'Status',
            render: (reminder) => (
                <Badge color={statusColors[reminder.status] ?? 'slate'}>{reminder.status}</Badge>
            ),
        },
        {
            key: 'actions',
            header: '',
            render: (reminder) => (
                <div className="flex justify-end gap-3 text-sm">
                    {reminder.status === 'Pending' && (
                        <>
                            <button
                                type="button"
                                onClick={() => setStatus(reminder, 'Completed')}
                                className="font-medium text-emerald-600 hover:text-emerald-500"
                            >
                                Complete
                            </button>
                            <button
                                type="button"
                                onClick={() => setStatus(reminder, 'Dismissed')}
                                className="font-medium text-slate-500 hover:text-slate-700"
                            >
                                Dismiss
                            </button>
                        </>
                    )}
                    <button
                        type="button"
                        onClick={() => openEditModal(reminder)}
                        className="font-medium text-indigo-600 hover:text-indigo-500"
                    >
                        Edit
                    </button>
                    <button
                        type="button"
                        onClick={() => destroyReminder(reminder)}
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
                title="Reminders & Follow-Ups"
                actions={
                    <button
                        type="button"
                        onClick={openCreateModal}
                        className="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500"
                    >
                        New Reminder
                    </button>
                }
                className="p-0"
            >
                <DataTable
                    columns={columns}
                    rows={reminders}
                    emptyMessage="No reminders set yet."
                />
            </Card>

            <Modal show={modalOpen} onClose={() => setModalOpen(false)} maxWidth="lg">
                <form onSubmit={submit} className="max-h-[80vh] space-y-4 overflow-y-auto p-6">
                    <h3 className="text-lg font-semibold text-slate-900">
                        {editingReminder ? 'Edit Reminder' : 'New Reminder'}
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
                        <InputLabel htmlFor="notes" value="Notes" />
                        <TextareaInput
                            id="notes"
                            rows={3}
                            className="mt-1 block w-full"
                            value={form.data.notes}
                            onChange={(e) => form.setData('notes', e.target.value)}
                        />
                    </div>

                    <div>
                        <InputLabel htmlFor="remind_at" value="Remind At" />
                        <TextInput
                            id="remind_at"
                            type="datetime-local"
                            className="mt-1 block w-full"
                            value={form.data.remind_at}
                            onChange={(e) => form.setData('remind_at', e.target.value)}
                            required
                        />
                        <InputError className="mt-2" message={form.errors.remind_at} />
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="organization_id" value="Organization" />
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
                            <InputLabel htmlFor="contact_id" value="Contact" />
                            <SelectInput
                                id="contact_id"
                                className="mt-1 block w-full"
                                value={form.data.contact_id}
                                onChange={(e) => form.setData('contact_id', e.target.value)}
                            >
                                <option value="">None</option>
                                {availableContacts.map((contact) => (
                                    <option key={contact.id} value={contact.id}>
                                        {contact.full_name}
                                    </option>
                                ))}
                            </SelectInput>
                        </div>
                    </div>

                    <div>
                        <InputLabel htmlFor="assigned_to" value="Assigned To" />
                        <SelectInput
                            id="assigned_to"
                            className="mt-1 block w-full"
                            value={form.data.assigned_to}
                            onChange={(e) => form.setData('assigned_to', e.target.value)}
                        >
                            <option value="">Unassigned</option>
                            {availableUsers.map((user) => (
                                <option key={user.id} value={user.id}>
                                    {user.name}
                                </option>
                            ))}
                        </SelectInput>
                    </div>

                    {editingReminder && (
                        <div>
                            <InputLabel htmlFor="status" value="Status" />
                            <SelectInput
                                id="status"
                                className="mt-1 block w-full"
                                value={form.data.status}
                                onChange={(e) => form.setData('status', e.target.value)}
                            >
                                {reminderStatuses.map((status) => (
                                    <option key={status} value={status}>
                                        {status}
                                    </option>
                                ))}
                            </SelectInput>
                        </div>
                    )}

                    <div className="flex items-center gap-3 pt-2">
                        <PrimaryButton disabled={form.processing}>
                            {editingReminder ? 'Save Changes' : 'Create Reminder'}
                        </PrimaryButton>
                        <SecondaryButton type="button" onClick={() => setModalOpen(false)}>
                            Cancel
                        </SecondaryButton>
                    </div>
                </form>
            </Modal>

            <ConfirmDialog {...dialogProps} />
        </>
    );
}
