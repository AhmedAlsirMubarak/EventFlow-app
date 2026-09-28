import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { useEffect, useState } from 'react';

/**
 * A styled stand-in for `window.confirm()`. Pair with the `useConfirm` hook
 * rather than managing show/onConfirm/onCancel state by hand.
 *
 * `requireText`: for irreversible actions, the confirm button stays disabled
 * until the user types this exact text (e.g. the name of what's being deleted).
 */
export default function ConfirmDialog({
    show,
    title = 'Are you sure?',
    message,
    confirmLabel = 'Confirm',
    cancelLabel = 'Cancel',
    danger = false,
    requireText,
    onConfirm,
    onCancel,
}) {
    const [typed, setTyped] = useState('');

    useEffect(() => {
        if (show) {
            setTyped('');
        }
    }, [show]);

    const confirmable = !requireText || typed.trim() === requireText.trim();

    return (
        <Modal show={show} onClose={onCancel} maxWidth="md" title={title}>
            <form
                className="p-6"
                onSubmit={(e) => {
                    e.preventDefault();
                    // React bubbles events through portals; never let this reach a parent form.
                    e.stopPropagation();

                    if (confirmable) {
                        onConfirm();
                    }
                }}
            >
                <h2 className="text-lg font-medium text-slate-900">{title}</h2>
                {message && <p className="mt-2 text-sm text-slate-600">{message}</p>}

                {requireText && (
                    <div className="mt-4">
                        <label htmlFor="confirm-text" className="block text-sm text-slate-600">
                            Type <span className="font-semibold text-slate-900">{requireText}</span> to confirm
                        </label>
                        <TextInput
                            id="confirm-text"
                            className="mt-1.5 block w-full"
                            value={typed}
                            onChange={(e) => setTyped(e.target.value)}
                            autoComplete="off"
                            autoFocus
                        />
                    </div>
                )}

                <div className="mt-6 flex justify-end gap-3">
                    <SecondaryButton onClick={onCancel}>{cancelLabel}</SecondaryButton>
                    <PrimaryButton type="submit" variant={danger ? 'danger' : 'primary'} disabled={!confirmable}>
                        {confirmLabel}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    );
}
