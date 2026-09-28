import CommentsPanel from '@/Components/CommentsPanel';
import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import Checkbox from '@/Components/Checkbox';
import DataTable from '@/Components/UI/DataTable';
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
const statusColors = {
    Backlog: 'slate',
    Planned: 'indigo',
    'In Progress': 'amber',
    'Waiting / Blocked': 'rose',
    Completed: 'emerald',
};

// 'YYYY-MM-DD' → "Sep 10" (or "Sep 10, 2027" outside the current year). Parsed as a
// local calendar date so it can't shift a day with the browser's timezone.
function formatShortDate(value) {
    const [year, month, day] = value.split('-').map(Number);
    const date = new Date(year, month - 1, day);

    return date.toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        ...(year !== new Date().getFullYear() ? { year: 'numeric' } : {}),
    });
}

const emptyTask = {
    title: '',
    description: '',
    department: '',
    assigned_to: '',
    organization_id: '',
    priority: 'Medium',
    start_date: '',
    due_date: '',
    status: 'Backlog',
};

export default function TasksTab({
    event,
    tasks,
    availableUsers,
    availableOrganizations,
    taskStatuses,
    taskPriorities,
}) {
    const [taskModalOpen, setTaskModalOpen] = useState(false);
    const [editingTask, setEditingTask] = useState(null);
    const [checklistTaskId, setChecklistTaskId] = useState(null);
    const [commentsTaskId, setCommentsTaskId] = useState(null);

    const taskForm = useForm(emptyTask);
    const checklistForm = useForm({ label: '' });
    const { confirm, dialogProps } = useConfirm();

    const checklistTask = tasks.find((t) => t.id === checklistTaskId) ?? null;
    const commentsTask = tasks.find((t) => t.id === commentsTaskId) ?? null;

    function openCreateModal() {
        setEditingTask(null);
        taskForm.reset();
        taskForm.clearErrors();
        setTaskModalOpen(true);
    }

    function openEditModal(task) {
        setEditingTask(task);
        taskForm.setData({
            title: task.title,
            description: task.description ?? '',
            department: task.department ?? '',
            assigned_to: task.assigned_to ?? '',
            organization_id: task.organization_id ?? '',
            priority: task.priority,
            start_date: task.start_date ?? '',
            due_date: task.due_date ?? '',
            status: task.status,
        });
        taskForm.clearErrors();
        setTaskModalOpen(true);
    }

    function submitTask(e) {
        e.preventDefault();
        const options = { onSuccess: () => setTaskModalOpen(false) };

        if (editingTask) {
            taskForm.put(route('tasks.update', editingTask.id), options);
        } else {
            taskForm.post(route('tasks.store', event.id), options);
        }
    }

    function destroyTask(task) {
        confirm({
            title: 'Remove task',
            message: `Remove the task "${task.title}"?`,
            confirmLabel: 'Remove',
            danger: true,
        }).then((confirmed) => {
            if (confirmed) {
                router.delete(route('tasks.destroy', task.id));
            }
        });
    }

    function openChecklist(task) {
        setChecklistTaskId(task.id);
        checklistForm.reset();
    }

    function addChecklistItem(e) {
        e.preventDefault();
        checklistForm.post(route('checklist-items.store', checklistTask.id), {
            preserveScroll: true,
            onSuccess: () => checklistForm.reset(),
        });
    }

    function toggleChecklistItem(item) {
        router.patch(
            route('checklist-items.update', item.id),
            { is_done: !item.is_done },
            { preserveScroll: true },
        );
    }

    function removeChecklistItem(item) {
        router.delete(route('checklist-items.destroy', item.id), {
            preserveScroll: true,
        });
    }

    const columns = [
        { key: 'title', header: 'Title' },
        {
            key: 'assignee',
            header: 'Assigned To',
            render: (task) => task.assignee?.name ?? '—',
        },
        {
            key: 'priority',
            header: 'Priority',
            render: (task) => (
                <Badge color={priorityColors[task.priority] ?? 'slate'}>
                    {task.priority}
                </Badge>
            ),
        },
        {
            key: 'status',
            header: 'Status',
            render: (task) => (
                <Badge color={statusColors[task.status] ?? 'slate'}>
                    {task.status}
                </Badge>
            ),
        },
        {
            key: 'dates',
            header: 'Dates',
            render: (task) => {
                if (task.start_date && task.due_date) {
                    return (
                        <span className="whitespace-nowrap">
                            {formatShortDate(task.start_date)} → {formatShortDate(task.due_date)}
                        </span>
                    );
                }

                if (task.start_date) {
                    return <span className="whitespace-nowrap">From {formatShortDate(task.start_date)}</span>;
                }

                return task.due_date ? (
                    <span className="whitespace-nowrap">Due {formatShortDate(task.due_date)}</span>
                ) : (
                    '—'
                );
            },
        },
        {
            key: 'checklist',
            header: 'Checklist',
            render: (task) => {
                const done = task.checklist_items.filter((i) => i.is_done).length;
                return (
                    <button
                        type="button"
                        onClick={() => openChecklist(task)}
                        className="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                    >
                        {done}/{task.checklist_items.length}
                    </button>
                );
            },
        },
        {
            key: 'comments',
            header: 'Comments',
            render: (task) => (
                <button
                    type="button"
                    onClick={() => setCommentsTaskId(task.id)}
                    className="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                >
                    {task.comments.length}
                </button>
            ),
        },
        {
            key: 'actions',
            header: '',
            render: (task) => (
                <div className="flex justify-end gap-3 text-sm">
                    <button
                        type="button"
                        onClick={() => openEditModal(task)}
                        className="font-medium text-indigo-600 hover:text-indigo-500"
                    >
                        Edit
                    </button>
                    <button
                        type="button"
                        onClick={() => destroyTask(task)}
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
                title="Tasks & Planning"
                actions={
                    <button
                        type="button"
                        onClick={openCreateModal}
                        className="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500"
                    >
                        New Task
                    </button>
                }
                className="p-0"
            >
                <DataTable
                    columns={columns}
                    rows={tasks}
                    emptyMessage="No tasks created yet."
                    emptyAction={{ label: 'Create the first task', onClick: openCreateModal }}
                />
            </Card>

            <Modal show={taskModalOpen} onClose={() => setTaskModalOpen(false)} maxWidth="lg">
                <form onSubmit={submitTask} className="space-y-4 p-6">
                    <h3 className="text-lg font-semibold text-slate-900">
                        {editingTask ? 'Edit Task' : 'New Task'}
                    </h3>

                    <div>
                        <InputLabel htmlFor="title" value="Title" />
                        <TextInput
                            id="title"
                            className="mt-1 block w-full"
                            value={taskForm.data.title}
                            onChange={(e) =>
                                taskForm.setData('title', e.target.value)
                            }
                            required
                            autoFocus
                        />
                        <InputError className="mt-2" message={taskForm.errors.title} />
                    </div>

                    <div>
                        <InputLabel htmlFor="description" value="Description" />
                        <TextareaInput
                            id="description"
                            rows={3}
                            className="mt-1 block w-full"
                            value={taskForm.data.description}
                            onChange={(e) =>
                                taskForm.setData('description', e.target.value)
                            }
                        />
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="department" value="Department" />
                            <TextInput
                                id="department"
                                className="mt-1 block w-full"
                                value={taskForm.data.department}
                                onChange={(e) =>
                                    taskForm.setData('department', e.target.value)
                                }
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="assigned_to" value="Assigned To" />
                            <SearchableSelect
                                id="assigned_to"
                                className="mt-1"
                                value={taskForm.data.assigned_to}
                                onChange={(value) => taskForm.setData('assigned_to', value)}
                                options={availableUsers.map((user) => ({ value: user.id, label: user.name }))}
                                emptyLabel="Unassigned"
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="start_date" value="Start Date (optional)" />
                            <TextInput
                                id="start_date"
                                type="date"
                                className="mt-1 block w-full"
                                value={taskForm.data.start_date}
                                max={taskForm.data.due_date || undefined}
                                onChange={(e) => taskForm.setData('start_date', e.target.value)}
                            />
                            <InputError className="mt-2" message={taskForm.errors.start_date} />
                        </div>
                        <div>
                            <InputLabel htmlFor="due_date" value="Due Date" />
                            <TextInput
                                id="due_date"
                                type="date"
                                className="mt-1 block w-full"
                                value={taskForm.data.due_date}
                                min={taskForm.data.start_date || undefined}
                                onChange={(e) =>
                                    taskForm.setData('due_date', e.target.value)
                                }
                            />
                            <InputError className="mt-2" message={taskForm.errors.due_date} />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="priority" value="Priority" />
                            <SelectInput
                                id="priority"
                                className="mt-1 block w-full"
                                value={taskForm.data.priority}
                                onChange={(e) =>
                                    taskForm.setData('priority', e.target.value)
                                }
                            >
                                {taskPriorities.map((p) => (
                                    <option key={p} value={p}>
                                        {p}
                                    </option>
                                ))}
                            </SelectInput>
                        </div>
                        <div>
                            <InputLabel htmlFor="status" value="Status" />
                            <SelectInput
                                id="status"
                                className="mt-1 block w-full"
                                value={taskForm.data.status}
                                onChange={(e) =>
                                    taskForm.setData('status', e.target.value)
                                }
                            >
                                {taskStatuses.map((s) => (
                                    <option key={s} value={s}>
                                        {s}
                                    </option>
                                ))}
                            </SelectInput>
                        </div>
                    </div>

                    <div>
                        <div>
                            <InputLabel
                                htmlFor="organization_id"
                                value="Related Organization"
                            />
                            <SelectInput
                                id="organization_id"
                                className="mt-1 block w-full"
                                value={taskForm.data.organization_id}
                                onChange={(e) =>
                                    taskForm.setData(
                                        'organization_id',
                                        e.target.value,
                                    )
                                }
                            >
                                <option value="">None</option>
                                {availableOrganizations.map((org) => (
                                    <option key={org.id} value={org.id}>
                                        {org.name}
                                    </option>
                                ))}
                            </SelectInput>
                        </div>
                    </div>

                    <div className="flex items-center gap-3 pt-2">
                        <PrimaryButton disabled={taskForm.processing}>
                            {editingTask ? 'Save Changes' : 'Add Task'}
                        </PrimaryButton>
                        <SecondaryButton type="button" onClick={() => setTaskModalOpen(false)}>
                            Cancel
                        </SecondaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={checklistTask !== null} onClose={() => setChecklistTaskId(null)} maxWidth="sm">
                {checklistTask && (
                    <div className="space-y-4 p-6">
                        <h3 className="text-lg font-semibold text-slate-900">
                            Checklist — {checklistTask.title}
                        </h3>

                        <ul className="space-y-2">
                            {checklistTask.checklist_items.length === 0 && (
                                <li className="text-sm text-slate-400">
                                    No checklist items yet.
                                </li>
                            )}
                            {checklistTask.checklist_items.map((item) => (
                                <li
                                    key={item.id}
                                    className="flex items-center justify-between gap-2"
                                >
                                    <label className="flex items-center gap-2 text-sm text-slate-700">
                                        <Checkbox
                                            checked={item.is_done}
                                            onChange={() =>
                                                toggleChecklistItem(item)
                                            }
                                        />
                                        <span
                                            className={
                                                item.is_done
                                                    ? 'line-through text-slate-400'
                                                    : ''
                                            }
                                        >
                                            {item.label}
                                        </span>
                                    </label>
                                    <button
                                        type="button"
                                        onClick={() => removeChecklistItem(item)}
                                        className="text-xs font-medium text-rose-600 hover:text-rose-500"
                                    >
                                        Remove
                                    </button>
                                </li>
                            ))}
                        </ul>

                        <form onSubmit={addChecklistItem} className="flex gap-2 pt-2">
                            <TextInput
                                className="block w-full"
                                placeholder="Add checklist item"
                                value={checklistForm.data.label}
                                onChange={(e) =>
                                    checklistForm.setData('label', e.target.value)
                                }
                            />
                            <PrimaryButton disabled={checklistForm.processing}>Add</PrimaryButton>
                        </form>
                    </div>
                )}
            </Modal>

            {commentsTask && (
                <CommentsPanel
                    show
                    onClose={() => setCommentsTaskId(null)}
                    title={commentsTask.title}
                    comments={commentsTask.comments}
                    storeUrl={route('tasks.comments.store', commentsTask.id)}
                />
            )}

            <ConfirmDialog {...dialogProps} />
        </>
    );
}
