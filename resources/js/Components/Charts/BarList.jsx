/**
 * Horizontal bars for a single series across a handful of categories (task
 * statuses, funnel stages). One colour for every bar — length carries the
 * value, and every value is labelled, so the list doubles as its own table.
 *
 * items: [{ label, value, hint? }]
 * scaleTo: the value a full-length bar represents (defaults to the largest).
 * shareOf: optional denominator for a "% of …" note on hover/focus.
 */
export default function BarList({ items, scaleTo, shareOf, shareLabel = 'of total' }) {
    const max = scaleTo ?? Math.max(1, ...items.map((item) => item.value));

    return (
        <ul className="space-y-3.5">
            {items.map((item) => {
                const width = max > 0 ? Math.max((item.value / max) * 100, item.value > 0 ? 2 : 0) : 0;
                const share = shareOf ? Math.round((item.value / shareOf) * 100) : null;

                return (
                    <li
                        key={item.label}
                        tabIndex={0}
                        className="group rounded-md outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40"
                    >
                        <div className="mb-1.5 flex items-baseline justify-between gap-3 text-sm">
                            <span className="truncate text-slate-600">{item.label}</span>
                            <span className="flex shrink-0 items-baseline gap-2">
                                {share !== null && shareOf > 0 && (
                                    <span className="text-xs text-slate-400 opacity-0 transition group-hover:opacity-100 group-focus-visible:opacity-100">
                                        {share}% {shareLabel}
                                    </span>
                                )}
                                <span className="font-semibold tabular-nums text-slate-900">{item.value}</span>
                            </span>
                        </div>
                        <div className="h-2.5 w-full rounded-r bg-slate-100">
                            <div
                                className="h-full rounded-r bg-indigo-600 transition-[width] duration-500 group-hover:bg-indigo-500"
                                style={{ width: `${width}%` }}
                            />
                        </div>
                        {item.hint && <p className="mt-1 text-xs text-slate-400">{item.hint}</p>}
                    </li>
                );
            })}
        </ul>
    );
}
