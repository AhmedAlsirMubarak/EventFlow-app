import { IconDocumentText, IconUpload } from '@/Components/Icons';
import { useRef, useState } from 'react';

function formatBytes(bytes) {
    if (!bytes) {
        return '';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    const exponent = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);

    return `${(bytes / 1024 ** exponent).toFixed(exponent === 0 ? 0 : 1)} ${units[exponent]}`;
}

export default function FileDropzone({ id, accept, value, onChange, hint }) {
    const inputRef = useRef(null);
    const [isDragging, setIsDragging] = useState(false);

    function selectFile(files) {
        if (files && files[0]) {
            onChange(files[0]);
        }
    }

    function handleDrop(e) {
        e.preventDefault();
        setIsDragging(false);
        selectFile(e.dataTransfer.files);
    }

    return (
        <div>
            <input
                ref={inputRef}
                id={id}
                type="file"
                accept={accept}
                className="hidden"
                onChange={(e) => selectFile(e.target.files)}
            />

            {value ? (
                <div className="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                    <div className="flex min-w-0 items-center gap-2 text-sm text-slate-700">
                        <IconDocumentText className="h-5 w-5 shrink-0 text-slate-400" />
                        <span className="truncate font-medium">{value.name}</span>
                        <span className="shrink-0 text-slate-400">{formatBytes(value.size)}</span>
                    </div>
                    <button
                        type="button"
                        onClick={() => {
                            onChange(null);
                            if (inputRef.current) {
                                inputRef.current.value = '';
                            }
                        }}
                        className="shrink-0 text-xs font-medium text-rose-600 hover:text-rose-500"
                    >
                        Remove
                    </button>
                </div>
            ) : (
                <div
                    role="button"
                    tabIndex={0}
                    onClick={() => inputRef.current?.click()}
                    onKeyDown={(e) => (e.key === 'Enter' || e.key === ' ') && inputRef.current?.click()}
                    onDragOver={(e) => {
                        e.preventDefault();
                        setIsDragging(true);
                    }}
                    onDragLeave={() => setIsDragging(false)}
                    onDrop={handleDrop}
                    className={`flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed px-4 py-6 text-center transition ${
                        isDragging
                            ? 'border-indigo-400 bg-indigo-50'
                            : 'border-slate-300 bg-slate-50 hover:border-slate-400 hover:bg-slate-100'
                    }`}
                >
                    <IconUpload className="h-6 w-6 text-slate-400" />
                    <p className="mt-2 text-sm text-slate-600">
                        <span className="font-medium text-indigo-600">Click to upload</span> or drag and drop
                    </p>
                    {hint && <p className="mt-1 text-xs text-slate-400">{hint}</p>}
                </div>
            )}
        </div>
    );
}
