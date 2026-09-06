import Card from '@/Components/UI/Card';

const accents = {
    indigo: 'bg-indigo-50 text-indigo-600',
    emerald: 'bg-emerald-50 text-emerald-600',
    amber: 'bg-amber-50 text-amber-600',
    rose: 'bg-rose-50 text-rose-600',
    sky: 'bg-sky-50 text-sky-600',
};

export default function StatCard({ label, value, icon, accent = 'indigo' }) {
    return (
        <Card className="flex items-center gap-4">
            {icon && (
                <div
                    className={`flex h-12 w-12 shrink-0 items-center justify-center rounded-xl ${accents[accent]}`}
                >
                    {icon}
                </div>
            )}
            <div>
                <p className="text-sm font-medium text-slate-500">{label}</p>
                <p className="mt-1 text-2xl font-semibold text-slate-900">
                    {value}
                </p>
            </div>
        </Card>
    );
}
