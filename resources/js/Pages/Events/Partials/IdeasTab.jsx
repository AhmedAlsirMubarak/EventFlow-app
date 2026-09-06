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
import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';

const statusColors = {
    New: 'slate',
    'Under Discussion': 'amber',
    Approved: 'indigo',
    Planned: 'indigo',
    Implemented: 'emerald',
    Rejected: 'rose',
};

const emptyIdea = { title: '', description: '', category: '', status: 'New' };

export default function IdeasTab({ event, ideas, statuses }) {
    const [modalOpen, setModalOpen] = useState(false);
    const [editingIdea, setEditingIdea] = useState(null);
    const form = useForm(emptyIdea);

    function openCreateModal() {
        setEditingIdea(null);
        form.reset();
        form.clearErrors();
        setModalOpen(true);
    }

    function openEditModal(idea) {
        setEditingIdea(idea);
        form.setData({
            title: idea.title,
            description: idea.description ?? '',
            category: idea.category ?? '',
            status: idea.status,
        });
        form.clearErrors();
        setModalOpen(true);
    }

    function submit(e) {
        e.preventDefault();
        const options = { onSuccess: () => setModalOpen(false) };

        if (editingIdea) {
            form.put(route('ideas.update', editingIdea.id), options);
        } else {
            form.post(route('ideas.store', event.id), options);
        }
    }

    function destroyIdea(idea) {
        if (confirm(`Remove the idea "${idea.title}"?`)) {
            router.delete(route('ideas.destroy', idea.id));
        }
    }

    const columns = [
        { key: 'title', header: 'Title' },
        { key: 'category', header: 'Category', render: (idea) => idea.category ?? '—' },
        {
            key: 'status',
            header: 'Status',
            render: (idea) => (
                <Badge color={statusColors[idea.status] ?? 'slate'}>
                    {idea.status}
                </Badge>
            ),
        },
        {
            key: 'creator',
            header: 'Submitted By',
            render: (idea) => idea.creator?.name ?? '—',
        },
        {
            key: 'actions',
            header: '',
            render: (idea) => (
                <div className="flex justify-end gap-3 text-sm">
                    <button
                        type="button"
                        onClick={() => openEditModal(idea)}
                        className="font-medium text-indigo-600 hover:text-indigo-500"
                    >
                        Edit
                    </button>
                    <button
                        type="button"
                        onClick={() => destroyIdea(idea)}
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
                title="Ideas & Brainstorming"
                actions={
                    <button
                        type="button"
                        onClick={openCreateModal}
                        className="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500"
                    >
                        New Idea
                    </button>
                }
                className="p-0"
            >
                <DataTable
                    columns={columns}
                    rows={ideas}
                    emptyMessage="No ideas captured yet."
                />
            </Card>

            <Modal show={modalOpen} onClose={() => setModalOpen(false)} maxWidth="md">
                <form onSubmit={submit} className="space-y-4 p-6">
                    <h3 className="text-lg font-semibold text-slate-900">
                        {editingIdea ? 'Edit Idea' : 'New Idea'}
                    </h3>

                    <div>
                        <InputLabel htmlFor="title" value="Title" />
                        <TextInput
                            id="title"
                            className="mt-1 block w-full"
                            value={form.data.title}
                            onChange={(e) =>
                                form.setData('title', e.target.value)
                            }
                            required
                            autoFocus
                        />
                        <InputError className="mt-2" message={form.errors.title} />
                    </div>

                    <div>
                        <InputLabel htmlFor="category" value="Category" />
                        <TextInput
                            id="category"
                            className="mt-1 block w-full"
                            value={form.data.category}
                            onChange={(e) =>
                                form.setData('category', e.target.value)
                            }
                        />
                    </div>

                    <div>
                        <InputLabel htmlFor="description" value="Description" />
                        <TextareaInput
                            id="description"
                            rows={3}
                            className="mt-1 block w-full"
                            value={form.data.description}
                            onChange={(e) =>
                                form.setData('description', e.target.value)
                            }
                        />
                    </div>

                    <div>
                        <InputLabel htmlFor="status" value="Status" />
                        <SelectInput
                            id="status"
                            className="mt-1 block w-full"
                            value={form.data.status}
                            onChange={(e) =>
                                form.setData('status', e.target.value)
                            }
                        >
                            {statuses.map((s) => (
                                <option key={s} value={s}>
                                    {s}
                                </option>
                            ))}
                        </SelectInput>
                    </div>

                    <div className="flex items-center gap-3 pt-2">
                        <PrimaryButton
                            disabled={form.processing}
                            className="!bg-indigo-600 !text-sm !normal-case !tracking-normal hover:!bg-indigo-500 focus:!bg-indigo-500 active:!bg-indigo-700"
                        >
                            {editingIdea ? 'Save Changes' : 'Add Idea'}
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
