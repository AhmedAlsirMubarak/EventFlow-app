import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

/**
 * A styled stand-in for `window.confirm()`. Pair with the `useConfirm` hook
 * rather than managing show/onConfirm/onCancel state by hand.
 */
export default function ConfirmDialog({
    show,
    title = 'Are you sure?',
    message,
    confirmLabel = 'Confirm',
    cancelLabel = 'Cancel',
    danger = false,
    onConfirm,
    onCancel,
}) {
    return (
        <Modal show={show} onClose={onCancel} maxWidth="md" title={title}>
            <div className="p-6">
                <h2 className="text-lg font-medium text-slate-900">{title}</h2>
                {message && <p className="mt-2 text-sm text-slate-600">{message}</p>}

                <div className="mt-6 flex justify-end gap-3">
                    <SecondaryButton onClick={onCancel}>{cancelLabel}</SecondaryButton>
                    <PrimaryButton variant={danger ? 'danger' : 'primary'} onClick={onConfirm}>
                        {confirmLabel}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    );
}
