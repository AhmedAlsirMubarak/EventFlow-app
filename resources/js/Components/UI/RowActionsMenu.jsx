import { Menu, MenuButton, MenuItem, MenuItems, MenuSeparator } from '@headlessui/react';
import { Link } from '@inertiajs/react';

const itemClasses = 'flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm transition';
const normalClasses = 'text-slate-700 data-focus:bg-slate-100';
const dangerClasses = 'text-rose-600 data-focus:bg-rose-50';

/**
 * The "⋯" menu at the end of a table row. Rendered with `anchor`, so it floats
 * above the page instead of being clipped by the table's scroll container.
 *
 * items: [{ label, onClick? | href?, danger?, separatorBefore? }] — falsy entries are skipped.
 */
export default function RowActionsMenu({ label = 'Actions', items }) {
    return (
        <Menu>
            <MenuButton
                aria-label={label}
                className="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 data-open:bg-slate-100 data-open:text-slate-700"
            >
                <svg viewBox="0 0 20 20" fill="currentColor" className="h-5 w-5" aria-hidden="true">
                    <path d="M3 10a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zM8.5 10a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zM15.5 8.5a1.5 1.5 0 100 3 1.5 1.5 0 000-3z" />
                </svg>
            </MenuButton>

            <MenuItems
                anchor="bottom end"
                className="z-50 w-44 rounded-lg border border-slate-200 bg-white p-1 shadow-lg [--anchor-gap:4px] focus:outline-none"
            >
                {items.filter(Boolean).map((item) => [
                    item.separatorBefore && <MenuSeparator key={`${item.label}-sep`} className="my-1 h-px bg-slate-100" />,
                    <MenuItem key={item.label}>
                        {item.href ? (
                            <Link href={item.href} className={`${itemClasses} ${normalClasses}`}>
                                {item.label}
                            </Link>
                        ) : (
                            <button
                                type="button"
                                onClick={item.onClick}
                                className={`${itemClasses} ${item.danger ? dangerClasses : normalClasses}`}
                            >
                                {item.label}
                            </button>
                        )}
                    </MenuItem>,
                ])}
            </MenuItems>
        </Menu>
    );
}
