import InputError from '@/Components/InputError';

/**
 * The bordered, label-inside input used across every auth page.
 */
export default function FloatingField({ id, label, error, className = '', ...inputProps }) {
    return (
        <div>
            <div
                className={`rounded-xl border px-4 py-2.5 transition focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-500/20 ${
                    error ? 'border-rose-400' : 'border-slate-200'
                }`}
            >
                <label htmlFor={id} className="block text-xs font-medium text-slate-400">
                    {label}
                </label>
                <input
                    id={id}
                    name={id}
                    {...inputProps}
                    className={`block w-full border-0 p-0 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0 ${className}`}
                />
            </div>
            <InputError message={error} className="mt-1.5" />
        </div>
    );
}
