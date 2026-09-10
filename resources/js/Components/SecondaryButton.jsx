const variants = {
    default: 'border-slate-200 text-slate-700 hover:bg-slate-50',
    danger: 'border-rose-200 text-rose-600 hover:bg-rose-50',
};

export default function SecondaryButton({
    type = 'button',
    className = '',
    variant = 'default',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            type={type}
            className={
                `inline-flex items-center justify-center gap-2 rounded-lg border bg-white px-4 py-2 text-sm font-semibold shadow-sm transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 ${variants[variant]} ${
                    disabled ? 'cursor-not-allowed opacity-50' : ''
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
