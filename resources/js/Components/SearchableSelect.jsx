import { Combobox, ComboboxButton, ComboboxInput, ComboboxOption, ComboboxOptions } from '@headlessui/react';
import { useMemo, useState } from 'react';

/**
 * A type-to-filter replacement for a plain <select>, for pickers that can grow
 * to dozens or hundreds of entries (people, organizations). Same value
 * contract as the <select> it replaces: `value` is an option's value or '',
 * and `onChange` receives the new value ('' when cleared to `emptyLabel`).
 */
export default function SearchableSelect({
    id,
    value,
    onChange,
    options,
    emptyLabel,
    placeholder = 'Search…',
    className = '',
}) {
    const [query, setQuery] = useState('');

    const selected = options.find((option) => String(option.value) === String(value)) ?? null;

    const filtered = useMemo(() => {
        const term = query.trim().toLowerCase();

        return term ? options.filter((option) => option.label.toLowerCase().includes(term)) : options;
    }, [options, query]);

    return (
        <Combobox
            value={selected}
            onChange={(option) => onChange(option?.value ?? '')}
            onClose={() => setQuery('')}
            by={(a, b) => String(a?.value) === String(b?.value)}
        >
            <div className={`relative ${className}`}>
                <ComboboxInput
                    id={id}
                    autoComplete="off"
                    displayValue={(option) => option?.label ?? ''}
                    onChange={(e) => setQuery(e.target.value)}
                    placeholder={selected ? undefined : (emptyLabel ?? placeholder)}
                    className="block w-full rounded-lg border-slate-300 py-2 pl-3 pr-9 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                />
                <ComboboxButton className="absolute inset-y-0 right-0 flex items-center px-2.5 text-slate-400">
                    <svg viewBox="0 0 20 20" fill="currentColor" className="h-4 w-4" aria-hidden="true">
                        <path
                            fillRule="evenodd"
                            d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z"
                            clipRule="evenodd"
                        />
                    </svg>
                </ComboboxButton>
            </div>

            <ComboboxOptions
                anchor="bottom start"
                className="z-[70] max-h-60 w-(--input-width) overflow-y-auto rounded-lg border border-slate-200 bg-white p-1 text-sm shadow-lg [--anchor-gap:4px] empty:invisible"
            >
                {emptyLabel && !query && (
                    <ComboboxOption
                        value={{ value: '', label: '' }}
                        className="cursor-pointer rounded-md px-3 py-2 italic text-slate-500 data-focus:bg-indigo-50"
                    >
                        {emptyLabel}
                    </ComboboxOption>
                )}
                {filtered.map((option) => (
                    <ComboboxOption
                        key={option.value}
                        value={option}
                        className="cursor-pointer rounded-md px-3 py-2 text-slate-700 data-focus:bg-indigo-50 data-focus:text-indigo-700 data-selected:font-semibold"
                    >
                        {option.label}
                        {option.hint && <span className="ml-1.5 text-xs text-slate-400">{option.hint}</span>}
                    </ComboboxOption>
                ))}
                {filtered.length === 0 && (
                    <div className="px-3 py-2 text-slate-400">No matches for &ldquo;{query}&rdquo;</div>
                )}
            </ComboboxOptions>
        </Combobox>
    );
}
