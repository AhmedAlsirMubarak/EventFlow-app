import Checkbox from '@/Components/Checkbox';
import { IconSearch } from '@/Components/Icons';
import { useMemo, useState } from 'react';

/**
 * A checkbox list that stays usable once there are dozens/hundreds of items —
 * a filter box appears above the list, and a "N selected" count stays visible
 * even once the selection scrolls out of view.
 */
export default function CheckboxSearchList({
    items,
    selectedIds,
    onToggle,
    emptyMessage = 'Nothing to show.',
    searchPlaceholder = 'Search…',
    searchThreshold = 6,
}) {
    const [search, setSearch] = useState('');

    const filtered = useMemo(() => {
        const term = search.trim().toLowerCase();

        return term ? items.filter((item) => item.label.toLowerCase().includes(term)) : items;
    }, [items, search]);

    return (
        <div className="mt-2 rounded-md border border-slate-200">
            {items.length > searchThreshold && (
                <div className="relative border-b border-slate-100 p-1.5">
                    <IconSearch className="pointer-events-none absolute left-3.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                    <input
                        type="search"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder={searchPlaceholder}
                        className="w-full rounded border-0 py-1 pl-7 pr-2 text-xs text-slate-700 focus:outline-none focus:ring-0"
                    />
                </div>
            )}

            <div className="max-h-32 space-y-1 overflow-y-auto p-2">
                {items.length === 0 && <p className="text-sm text-slate-400">{emptyMessage}</p>}
                {items.length > 0 && filtered.length === 0 && (
                    <p className="text-sm text-slate-400">No matches.</p>
                )}
                {filtered.map((item) => (
                    <label key={item.id} className="flex items-center gap-2 text-sm text-slate-700">
                        <Checkbox checked={selectedIds.includes(item.id)} onChange={() => onToggle(item.id)} />
                        {item.label}
                    </label>
                ))}
            </div>

            {selectedIds.length > 0 && (
                <p className="border-t border-slate-100 px-2 py-1 text-xs text-slate-400">
                    {selectedIds.length} selected
                </p>
            )}
        </div>
    );
}
