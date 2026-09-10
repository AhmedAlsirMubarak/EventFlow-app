const variants = {
    primary:
        'bg-indigo-600 hover:bg-indigo-500 focus:bg-indigo-500 focus:ring-indigo-500 active:bg-indigo-700',
    danger: 'bg-rose-600 hover:bg-rose-500 focus:bg-rose-500 focus:ring-rose-500 active:bg-rose-700',
    success:
        'bg-emerald-600 hover:bg-emerald-500 focus:bg-emerald-500 focus:ring-emerald-500 active:bg-emerald-700',
};

export default function PrimaryButton({
    className = '',
    variant = 'primary',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            className={
                `inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 ${variants[variant]} ${
                    disabled ? 'cursor-not-allowed opacity-50' : ''
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
