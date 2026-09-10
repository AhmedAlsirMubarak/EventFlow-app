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
import { router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

const categoryColors = {
    'What Went Well': 'emerald',
    Challenge: 'rose',
    'Recommendation for Next Time': 'indigo',
};

const emptyLesson = {
    category: 'What Went Well',
    title: '',
    description: '',
    department: '',
};

export default function ClosureTab({ event, lessonsLearned, lessonCategories }) {
    const { auth } = usePage().props;
    const canClose = auth.roles.includes('Super Administrator') || auth.roles.includes('Management');

    const [lessonModalOpen, setLessonModalOpen] = useState(false);
    const [editingLesson, setEditingLesson] = useState(null);
    const [closeModalOpen, setCloseModalOpen] = useState(false);
    const lessonForm = useForm(emptyLesson);
    const closeForm = useForm({ closure_summary: '' });
    const { confirm, dialogProps } = useConfirm();

    function openCreateLesson() {
        setEditingLesson(null);
        lessonForm.reset();
        lessonForm.clearErrors();
        setLessonModalOpen(true);
    }

    function openEditLesson(lesson) {
        setEditingLesson(lesson);
        lessonForm.setData({
            category: lesson.category,
            title: lesson.title,
            description: lesson.description ?? '',
            department: lesson.department ?? '',
        });
        lessonForm.clearErrors();
        setLessonModalOpen(true);
    }

    function submitLesson(e) {
        e.preventDefault();
        const options = { onSuccess: () => setLessonModalOpen(false) };

        if (editingLesson) {
            lessonForm.put(route('lessons.update', editingLesson.id), options);
        } else {
            lessonForm.post(route('lessons.store', event.id), options);
        }
    }

    function destroyLesson(lesson) {
        confirm({
            title: 'Remove lesson',
            message: `Remove the lesson "${lesson.title}"?`,
            confirmLabel: 'Remove',
            danger: true,
        }).then((confirmed) => {
            if (confirmed) {
                router.delete(route('lessons.destroy', lesson.id));
            }
        });
    }

    function submitClose(e) {
        e.preventDefault();
        closeForm.post(route('events.close', event.id), {
            onSuccess: () => setCloseModalOpen(false),
        });
    }

    function reopenEvent() {
        confirm({
            title: 'Reopen event',
            message: 'Reopen this event? Its status will change back to Active and the closure record will be cleared.',
            confirmLabel: 'Reopen',
        }).then((confirmed) => {
            if (confirmed) {
                router.post(route('events.reopen', event.id));
            }
        });
    }

    const columns = [
        {
            key: 'category',
            header: 'Category',
            render: (lesson) => (
                <Badge color={categoryColors[lesson.category] ?? 'slate'}>{lesson.category}</Badge>
            ),
        },
        { key: 'title', header: 'Title' },
        { key: 'department', header: 'Department', render: (lesson) => lesson.department ?? '—' },
        {
            key: 'actions',
            header: '',
            render: (lesson) => (
                <div className="flex justify-end gap-3 text-sm">
                    <button
                        type="button"
                        onClick={() => openEditLesson(lesson)}
                        className="font-medium text-indigo-600 hover:text-indigo-500"
                    >
                        Edit
                    </button>
                    <button
                        type="button"
                        onClick={() => destroyLesson(lesson)}
                        className="font-medium text-rose-600 hover:text-rose-500"
                    >
                        Remove
                    </button>
                </div>
            ),
        },
    ];

    return (
        <div className="space-y-6">
            <Card title="Event Closure">
                {event.closed_at ? (
                    <div className="space-y-3">
                        <p className="text-sm text-slate-700">
                            Closed by <strong>{event.closed_by?.name ?? 'Unknown'}</strong> on{' '}
                            {new Date(event.closed_at).toLocaleString(undefined, {
                                dateStyle: 'medium',
                                timeStyle: 'short',
                            })}
                        </p>
                        {event.closure_summary && (
                            <p className="whitespace-pre-line rounded-lg bg-slate-50 p-3 text-sm text-slate-700">
                                {event.closure_summary}
                            </p>
                        )}
                        {canClose && (
                            <SecondaryButton type="button" onClick={reopenEvent}>
                                Reopen Event
                            </SecondaryButton>
                        )}
                    </div>
                ) : (
                    <div className="space-y-3">
                        <p className="text-sm text-slate-600">
                            Closing this event marks it Completed and records a closure summary. It can be reopened later if needed.
                        </p>
                        {canClose ? (
                            <PrimaryButton type="button" onClick={() => setCloseModalOpen(true)}>
                                Close Event
                            </PrimaryButton>
                        ) : (
                            <p className="text-xs text-slate-400">
                                Only a Management or Super Administrator user can close this event.
                            </p>
                        )}
                    </div>
                )}
            </Card>

            <Card
                title="Lessons Learned"
                actions={
                    <button
                        type="button"
                        onClick={openCreateLesson}
                        className="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500"
                    >
                        Add Lesson
                    </button>
                }
                className="p-0"
            >
                <DataTable
                    columns={columns}
                    rows={lessonsLearned}
                    emptyMessage="No lessons recorded yet."
                />
            </Card>

            <Modal show={lessonModalOpen} onClose={() => setLessonModalOpen(false)} maxWidth="lg">
                <form onSubmit={submitLesson} className="max-h-[80vh] space-y-4 overflow-y-auto p-6">
                    <h3 className="text-lg font-semibold text-slate-900">
                        {editingLesson ? 'Edit Lesson' : 'Add Lesson'}
                    </h3>

                    <div>
                        <InputLabel htmlFor="category" value="Category" />
                        <SelectInput
                            id="category"
                            className="mt-1 block w-full"
                            value={lessonForm.data.category}
                            onChange={(e) => lessonForm.setData('category', e.target.value)}
                        >
                            {lessonCategories.map((category) => (
                                <option key={category} value={category}>
                                    {category}
                                </option>
                            ))}
                        </SelectInput>
                    </div>

                    <div>
                        <InputLabel htmlFor="title" value="Title" />
                        <TextInput
                            id="title"
                            className="mt-1 block w-full"
                            value={lessonForm.data.title}
                            onChange={(e) => lessonForm.setData('title', e.target.value)}
                            required
                            autoFocus
                        />
                        <InputError className="mt-2" message={lessonForm.errors.title} />
                    </div>

                    <div>
                        <InputLabel htmlFor="department" value="Department" />
                        <TextInput
                            id="department"
                            className="mt-1 block w-full"
                            value={lessonForm.data.department}
                            onChange={(e) => lessonForm.setData('department', e.target.value)}
                        />
                    </div>

                    <div>
                        <InputLabel htmlFor="description" value="Description" />
                        <TextareaInput
                            id="description"
                            rows={3}
                            className="mt-1 block w-full"
                            value={lessonForm.data.description}
                            onChange={(e) => lessonForm.setData('description', e.target.value)}
                        />
                    </div>

                    <div className="flex items-center gap-3 pt-2">
                        <PrimaryButton disabled={lessonForm.processing}>
                            {editingLesson ? 'Save Changes' : 'Add Lesson'}
                        </PrimaryButton>
                        <SecondaryButton type="button" onClick={() => setLessonModalOpen(false)}>
                            Cancel
                        </SecondaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={closeModalOpen} onClose={() => setCloseModalOpen(false)} maxWidth="md">
                <form onSubmit={submitClose} className="space-y-4 p-6">
                    <h3 className="text-lg font-semibold text-slate-900">Close Event</h3>
                    <div>
                        <InputLabel htmlFor="closure_summary" value="Closure Summary" />
                        <TextareaInput
                            id="closure_summary"
                            rows={4}
                            className="mt-1 block w-full"
                            placeholder="Outcome summary, outstanding items, final notes..."
                            value={closeForm.data.closure_summary}
                            onChange={(e) => closeForm.setData('closure_summary', e.target.value)}
                        />
                        <InputError className="mt-2" message={closeForm.errors.closure_summary} />
                    </div>
                    <div className="flex items-center gap-3 pt-2">
                        <PrimaryButton disabled={closeForm.processing}>Confirm Closure</PrimaryButton>
                        <SecondaryButton type="button" onClick={() => setCloseModalOpen(false)}>
                            Cancel
                        </SecondaryButton>
                    </div>
                </form>
            </Modal>

            <ConfirmDialog {...dialogProps} />
        </div>
    );
}
