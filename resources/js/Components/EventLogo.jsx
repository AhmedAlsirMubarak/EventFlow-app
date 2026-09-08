const sizeClasses = {
    sm: 'h-9 w-9',
    md: 'h-12 w-12',
    lg: 'h-16 w-16',
};

export default function EventLogo({ event, size = 'md', className = '' }) {
    const dimension = sizeClasses[size] ?? sizeClasses.md;

    if (event.logo_url) {
        return (
            <div
                className={`flex ${dimension} shrink-0 items-center justify-center overflow-hidden rounded-md bg-white p-1 ${className}`}
            >
                <img
                    src={event.logo_url}
                    alt=""
                    className="max-h-full max-w-full object-contain"
                />
            </div>
        );
    }

    return (
        <span
            className={`flex ${dimension} shrink-0 items-center justify-center rounded-md bg-indigo-100 text-sm font-semibold text-indigo-700 ${className}`}
        >
            {event.name.charAt(0).toUpperCase()}
        </span>
    );
}
