import { forwardRef } from 'react';

export default forwardRef(function TextareaInput(
    { className = '', ...props },
    ref,
) {
    return (
        <textarea
            {...props}
            className={
                'rounded-lg border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 ' +
                className
            }
            ref={ref}
        />
    );
});
