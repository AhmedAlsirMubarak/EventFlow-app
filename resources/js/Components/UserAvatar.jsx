const sizeClasses = {
    xs: 'h-6 w-6 text-xs',
    sm: 'h-8 w-8 text-sm',
    md: 'h-12 w-12 text-base',
    lg: 'h-16 w-16 text-lg',
};

export default function UserAvatar({ user, size = 'sm', className = '' }) {
    const dimension = sizeClasses[size] ?? sizeClasses.sm;

    if (user.avatar_url) {
        return (
            <img
                src={user.avatar_url}
                alt=""
                className={`${dimension} shrink-0 rounded-full object-cover ${className}`}
            />
        );
    }

    return (
        <span
            className={`flex ${dimension} shrink-0 items-center justify-center rounded-full bg-indigo-100 font-semibold text-indigo-700 ${className}`}
        >
            {user.name.charAt(0).toUpperCase()}
        </span>
    );
}
