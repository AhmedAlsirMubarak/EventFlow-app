import { IconInbox } from '@/Components/Icons';

function EmptyState({ message, action }) {
    return (
        <div className="flex flex-col items-center gap-2 px-4 py-12 text-center">
            <span className="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                <IconInbox className="h-5 w-5" />
            </span>
            <p className="text-sm text-slate-400">{message}</p>
            {action && (
                <button
                    type="button"
                    onClick={action.onClick}
                    className="mt-1 inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-3 py-1.5 text-sm font-semibold text-indigo-600 transition hover:bg-indigo-100"
                >
                    <span aria-hidden="true">+</span>
                    {action.label}
                </button>
            )}
        </div>
    );
}

function cell(column, row) {
    return column.render ? column.render(row) : row[column.key];
}

/**
 * A table on tablets and up; a list of cards on phones, where a wide table
 * would otherwise hide its right-hand columns — including the row actions —
 * behind a horizontal scroll nobody notices.
 *
 * On a card: leading unlabelled columns (a logo, an avatar) and the first
 * labelled column form the heading, the other labelled columns become
 * label/value pairs, and the `actions` column sits at the bottom.
 *
 * `emptyAction` ({ label, onClick }) turns the empty state into a next step
 * ("Create the first task") instead of a dead end.
 */
export default function DataTable({ columns, rows, emptyMessage = 'No records found.', emptyAction }) {
    const actions = columns.find((column) => column.key === 'actions');
    const titleIndex = columns.findIndex((column) => column.key !== 'actions' && column.header);
    const leading = columns.slice(0, Math.max(titleIndex, 0)).filter((column) => column.key !== 'actions');
    const title = columns[titleIndex];
    const fields = columns.filter((column, index) => index > titleIndex && column.key !== 'actions');

    return (
        <>
            {/* Phones: cards */}
            <div className="rounded-2xl border border-slate-200 bg-white shadow-sm sm:hidden">
                {rows.length === 0 ? (
                    <EmptyState message={emptyMessage} action={emptyAction} />
                ) : (
                    <ul className="divide-y divide-slate-100">
                        {rows.map((row, rowIndex) => (
                            <li key={row.id ?? rowIndex} className="p-4">
                                <div className="flex items-center gap-3">
                                    {leading.map((column) => (
                                        <div key={column.key} className="shrink-0">
                                            {cell(column, row)}
                                        </div>
                                    ))}
                                    {title && (
                                        <div className="min-w-0 flex-1 text-sm font-semibold text-slate-900">
                                            {cell(title, row)}
                                        </div>
                                    )}
                                </div>

                                {fields.length > 0 && (
                                    <dl className="mt-3 grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                                        {fields.map((column) => (
                                            <div key={column.key} className="min-w-0">
                                                <dt className="text-xs text-slate-400">{column.header}</dt>
                                                <dd className="mt-0.5 wrap-break-word text-slate-700">{cell(column, row) ?? '—'}</dd>
                                            </div>
                                        ))}
                                    </dl>
                                )}

                                {actions && (
                                    <div className="mt-3 flex justify-end border-t border-slate-100 pt-3">
                                        {cell(actions, row)}
                                    </div>
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            {/* Tablets and up: the table. `relative` matters: without it,
                absolutely-positioned content (e.g. an `sr-only` header label)
                isn't clipped by overflow-x-auto and stretches the page sideways. */}
            <div className="relative hidden overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm sm:block">
                <table className="min-w-full divide-y divide-slate-200 text-sm">
                    <thead className="bg-slate-50">
                        <tr>
                            {columns.map((column) => (
                                <th
                                    key={column.key}
                                    className="whitespace-nowrap px-4 py-3 text-left font-semibold text-slate-600"
                                >
                                    {column.header}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {rows.length === 0 && (
                            <tr>
                                <td colSpan={columns.length}>
                                    <EmptyState message={emptyMessage} action={emptyAction} />
                                </td>
                            </tr>
                        )}
                        {rows.map((row, rowIndex) => (
                            <tr key={row.id ?? rowIndex} className="hover:bg-slate-50">
                                {columns.map((column) => (
                                    <td key={column.key} className="px-4 py-3 text-slate-700">
                                        {cell(column, row)}
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </>
    );
}
