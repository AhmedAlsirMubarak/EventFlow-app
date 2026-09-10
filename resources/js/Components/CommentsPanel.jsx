import Badge from '@/Components/UI/Badge';
import Checkbox from '@/Components/Checkbox';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';
import TextareaInput from '@/Components/TextareaInput';
import UserAvatar from '@/Components/UserAvatar';
import { useConfirm } from '@/utils/useConfirm';
import { router, useForm, usePage } from '@inertiajs/react';

export default function CommentsPanel({ show, onClose, title, comments, storeUrl, allowVisibilityToggle = false }) {
    const { auth } = usePage().props;
    const form = useForm({ body: '', visibility: 'Internal' });
    const { confirm, dialogProps } = useConfirm();

    function submit(e) {
        e.preventDefault();
        form.post(storeUrl, {
            preserveScroll: true,
            onSuccess: () => form.reset('body'),
        });
    }

    function destroy(comment) {
        confirm({ title: 'Remove comment', message: 'Remove this comment?', confirmLabel: 'Remove', danger: true })
            .then((confirmed) => {
                if (confirmed) {
                    router.delete(route('comments.destroy', comment.id), {
                        preserveScroll: true,
                    });
                }
            });
    }

    function isOwnComment(comment) {
        if (comment.user) {
            return comment.user.id === auth.user.id;
        }

        return comment.contact?.id === auth.contactId;
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
                                <div className="flex items-center gap-2">
                                    {comment.user && <UserAvatar user={comment.user} size="xs" />}
                                    <span className="font-medium text-slate-900">
                                        {comment.user?.name ?? comment.contact?.full_name ?? 'Unknown'}
                                    </span>
                                    {comment.visibility === 'External' && (
                                        <Badge color="indigo">Visible to stakeholder</Badge>
                                    )}
                                </div>
                                {isOwnComment(comment) && (
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
                    {allowVisibilityToggle && (
                        <label className="flex items-center gap-2 text-sm text-slate-600">
                            <Checkbox
                                checked={form.data.visibility === 'External'}
                                onChange={(e) =>
                                    form.setData('visibility', e.target.checked ? 'External' : 'Internal')
                                }
                            />
                            Visible to the stakeholder
                        </label>
                    )}
                    <PrimaryButton disabled={form.processing || !form.data.body}>
                        Add Comment
                    </PrimaryButton>
                </form>
            </div>

            <ConfirmDialog {...dialogProps} />
        </Modal>
    );
}
