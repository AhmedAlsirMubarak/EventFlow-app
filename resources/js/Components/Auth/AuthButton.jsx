export default function AuthButton({ disabled, children, ...props }) {
    return (
        <button
            type="submit"
            disabled={disabled}
            {...props}
            className="w-full rounded-full bg-indigo-600 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
        >
            {children}
        </button>
    );
}
