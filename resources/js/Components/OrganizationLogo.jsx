const sizeClasses = {
    '2xs': 'h-4 w-4 text-[9px]',
    xs: 'h-6 w-6 text-xs',
    sm: 'h-9 w-9 text-sm',
    md: 'h-12 w-12 text-base',
    lg: 'h-16 w-16 text-lg',
    full: 'h-full w-full text-lg',
};

export default function OrganizationLogo({ organization, size = 'md', className = '' }) {
    const dimension = sizeClasses[size] ?? sizeClasses.md;

    if (organization?.logo_url) {
        return (
            <div
                className={`flex ${dimension} shrink-0 items-center justify-center overflow-hidden rounded-md bg-white p-0.5 ${className}`}
            >
                <img
                    src={organization.logo_url}
                    alt=""
                    className="h-full w-full object-contain"
                />
            </div>
        );
    }

    return (
        <span
            className={`flex ${dimension} shrink-0 items-center justify-center rounded-md bg-sky-100 font-semibold text-sky-700 ${className}`}
        >
            {organization?.name?.charAt(0).toUpperCase() ?? '?'}
        </span>
    );
}
