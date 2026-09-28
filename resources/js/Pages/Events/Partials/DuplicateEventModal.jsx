import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';

const OPTIONS = [
    { key: 'team', label: 'Internal team', hint: 'Same people and event roles.' },
    { key: 'organizations', label: 'Participating organizations', hint: 'Sponsors, partners, exhibitors.' },
    {
        key: 'tasks',
        label: 'Tasks & checklists',
        hint: 'Reset to Backlog, due dates shifted to match the new start date.',
    },
    {
        key: 'floor_plans',
        label: 'Floor plans & booth layout',
        hint: 'Same booths and positions — all marked available again.',
    },
    {
        key: 'abstracts',
        label: 'Call for abstracts setup',
        hint: 'Instructions, tracks, and review committee — no submissions.',
    },
];

// "Oman Climate Week 2026" → "Oman Climate Week 2027" — the most common reason
// to duplicate an event is next year's edition.
function nextEdition(value) {
    return value?.replace(/\b(20\d{2})\b/, (year) => String(Number(year) + 1)) ?? '';
}

function nextYearDate(value) {
    if (!value) {
        return '';
    }

    const [year, month, day] = value.split('-');

    return `${Number(year) + 1}-${month}-${day}`;
}

export default function DuplicateEventModal({ event, show, onClose }) {
    const form = useForm({
        name: '',
        code: '',
        start_date: '',
        include: OPTIONS.map((option) => option.key),
    });

    useEffect(() => {
        if (show) {
            const name = nextEdition(event.name);
            const code = nextEdition(event.code);

            form.setData({
                name: name === event.name ? `${event.name} (Copy)` : name,
                code: code === event.code ? `${event.code}-copy` : code,
                start_date: nextYearDate(event.start_date),
                include: OPTIONS.map((option) => option.key),
            });
            form.clearErrors();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [show]);

    function toggle(key) {
        form.setData(
            'include',
            form.data.include.includes(key)
                ? form.data.include.filter((item) => item !== key)
                : [...form.data.include, key],
        );
    }

    function submit(e) {
        e.preventDefault();
        form.post(route('events.duplicate', event.id), { onSuccess: onClose });
    }

    return (
        <Modal show={show} onClose={onClose} maxWidth="lg" title="Duplicate event">
            <form onSubmit={submit} className="max-h-[85vh] space-y-5 overflow-y-auto p-6">
                <div>
                    <h3 className="text-lg font-semibold text-slate-900">Duplicate event</h3>
                    <p className="mt-1 text-sm text-slate-500">
                        Start a new event from <span className="font-medium text-slate-700">{event.name}</span>.
                        Documents, meetings, decisions, requests and submissions stay with the original.
                    </p>
                </div>

                <div>
                    <InputLabel htmlFor="duplicate_name" value="New event name" />
                    <TextInput
                        id="duplicate_name"
                        className="mt-1 block w-full"
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                        required
                    />
                    <InputError className="mt-2" message={form.errors.name} />
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel htmlFor="duplicate_code" value="Event code" />
                        <TextInput
                            id="duplicate_code"
                            className="mt-1 block w-full"
                            value={form.data.code}
                            onChange={(e) => form.setData('code', e.target.value)}
                            required
                        />
                        <InputError className="mt-2" message={form.errors.code} />
                    </div>
                    <div>
                        <InputLabel htmlFor="duplicate_start_date" value="New start date" />
                        <TextInput
                            id="duplicate_start_date"
                            type="date"
                            className="mt-1 block w-full"
                            value={form.data.start_date}
                            onChange={(e) => form.setData('start_date', e.target.value)}
                        />
                        <InputError className="mt-2" message={form.errors.start_date} />
                    </div>
                </div>

                <fieldset>
                    <legend className="text-sm font-medium text-slate-700">Bring along</legend>
                    <div className="mt-2 space-y-2">
                        {OPTIONS.map((option) => (
                            <label
                                key={option.key}
                                className={`flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition ${
                                    form.data.include.includes(option.key)
                                        ? 'border-indigo-200 bg-indigo-50/60'
                                        : 'border-slate-200 hover:bg-slate-50'
                                }`}
                            >
                                <Checkbox
                                    className="mt-0.5"
                                    checked={form.data.include.includes(option.key)}
                                    onChange={() => toggle(option.key)}
                                />
                                <span>
                                    <span className="block text-sm font-medium text-slate-900">{option.label}</span>
                                    <span className="block text-xs text-slate-500">{option.hint}</span>
                                </span>
                            </label>
                        ))}
                    </div>
                    {!event.start_date && form.data.include.includes('tasks') && (
                        <p className="mt-2 text-xs text-amber-600">
                            The original event has no start date, so copied tasks won&apos;t have due dates.
                        </p>
                    )}
                </fieldset>

                <div className="flex items-center gap-3 pt-1">
                    <PrimaryButton disabled={form.processing}>Create event</PrimaryButton>
                    <SecondaryButton type="button" onClick={onClose}>
                        Cancel
                    </SecondaryButton>
                </div>
            </form>
        </Modal>
    );
}
