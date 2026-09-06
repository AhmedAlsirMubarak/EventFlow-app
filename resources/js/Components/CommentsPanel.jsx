import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import TextareaInput from '@/Components/TextareaInput';
import { router, useForm, usePage } from '@inertiajs/react';

export default function CommentsPanel({ show, onClose, title, comments, storeUrl }) {
    const { auth } = usePage().props;
    const form = useForm({ body: '' });

    function submit(e) {
        e.preventDefault();
        form.post(storeUrl, {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }

    function destroy(comment) {
        if (confirm('Remove this comment?')) {
            router.delete(route('comments.destroy', comment.id), {
                preserveScroll: true,
            });
        }
    }

    return (
        <Modal show={show} onClose={onClose} maxWidth="md">
            <div className="space-y-4 p-6">
                <h3 className="text-lg font-semibold text-slate-900">
                    Comments — {title}
                </h3>

                <ul className="max-h-80 space-y-3 overflow-y-auto">
                    {comments.length === 0 && (
                        <li className="text-sm text-slate-400">
                            No comments yet.
                        </li>
                    )}
                    {comments.map((comment) => (
                        <li
                            key={comment.id}
                            className="rounded-lg bg-slate-50 p-3 text-sm"
                        >
                            <div className="flex items-center justify-between">
                                <span className="font-medium text-slate-900">
                                    {comment.user?.name ?? 'Unknown'}
                                </span>
                                {comment.user?.id === auth.user.id && (
                                    <button
                                        type="button"
                                        onClick={() => destroy(comment)}
                                        className="text-xs font-medium text-rose-600 hover:text-rose-500"
                                    >
                                        Remove
                                    </button>
                                )}
                            </div>
                            <p className="mt-1 whitespace-pre-line text-slate-700">
                                {comment.body}
                            </p>
                        </li>
                    ))}
                </ul>

                <form onSubmit={submit} className="space-y-2 border-t border-slate-100 pt-4">
                    <TextareaInput
                        rows={2}
                        className="block w-full"
                        placeholder="Add a comment"
                        value={form.data.body}
                        onChange={(e) => form.setData('body', e.target.value)}
                    />
                    <PrimaryButton
                        disabled={form.processing || !form.data.body}
                        className="!bg-indigo-600 !text-sm !normal-case !tracking-normal hover:!bg-indigo-500 focus:!bg-indigo-500 active:!bg-indigo-700"
                    >
                        Add Comment
                    </PrimaryButton>
                </form>
            </div>
        </Modal>
    );
}
