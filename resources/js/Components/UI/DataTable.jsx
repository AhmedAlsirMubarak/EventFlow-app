export default function DataTable({ columns, rows, emptyMessage = 'No records found.' }) {
    return (
        <div className="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table className="min-w-full divide-y divide-slate-200 text-sm">
                <thead className="bg-slate-50">
                    <tr>
                        {columns.map((column) => (
                            <th
                                key={column.key}
                                className="px-4 py-3 text-left font-semibold text-slate-600"
                            >
                                {column.header}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                    {rows.length === 0 && (
                        <tr>
                            <td
                                colSpan={columns.length}
                                className="px-4 py-8 text-center text-slate-400"
                            >
                                {emptyMessage}
                            </td>
                        </tr>
                    )}
                    {rows.map((row, rowIndex) => (
                        <tr key={row.id ?? rowIndex} className="hover:bg-slate-50">
                            {columns.map((column) => (
                                <td
                                    key={column.key}
                                    className="px-4 py-3 text-slate-700"
                                >
                                    {column.render
                                        ? column.render(row)
                                        : row[column.key]}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
