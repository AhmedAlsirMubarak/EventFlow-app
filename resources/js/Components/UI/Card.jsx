export default function Card({ title, actions, className = '', children }) {
    if (!title && !actions) {
        return (
            <div
                className={`rounded-2xl border border-slate-200 bg-white p-6 shadow-sm ${className}`}
            >
                {children}
            </div>
        );
    }

    return (
        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-4 sm:px-6">
                {title && (
                    <h3 className="text-base font-semibold text-slate-900">
                        {title}
                    </h3>
                )}
                {actions}
            </div>
            <div className={className || 'p-4 sm:p-6'}>{children}</div>
        </div>
    );
}
