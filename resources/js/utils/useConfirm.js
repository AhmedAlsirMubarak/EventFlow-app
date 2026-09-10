import { useCallback, useRef, useState } from 'react';

/**
 * Promise-based replacement for `window.confirm()`, backed by the styled
 * <ConfirmDialog> instead of the browser's native dialog. Usage:
 *
 *   const { confirm, dialogProps } = useConfirm();
 *   ...
 *   confirm({ title: 'Remove task', message: `Remove "${task.title}"?`, danger: true })
 *       .then((confirmed) => confirmed && router.delete(route('tasks.destroy', task.id)));
 *
 *   return (
 *       <>
 *           ...
 *           <ConfirmDialog {...dialogProps} />
 *       </>
 *   );
 */
export function useConfirm() {
    const [state, setState] = useState({
        show: false,
        title: 'Are you sure?',
        message: '',
        confirmLabel: 'Confirm',
        cancelLabel: 'Cancel',
        danger: false,
    });
    const resolver = useRef(null);

    const confirm = useCallback((options) => {
        setState({
            title: 'Are you sure?',
            confirmLabel: 'Confirm',
            cancelLabel: 'Cancel',
            danger: false,
            ...options,
            show: true,
        });

        return new Promise((resolve) => {
            resolver.current = resolve;
        });
    }, []);

    const close = useCallback((result) => {
        setState((prev) => ({ ...prev, show: false }));
        resolver.current?.(result);
        resolver.current = null;
    }, []);

    return {
        confirm,
        dialogProps: {
            ...state,
            onConfirm: () => close(true),
            onCancel: () => close(false),
        },
    };
}
