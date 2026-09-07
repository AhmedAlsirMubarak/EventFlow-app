import Card from '@/Components/UI/Card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

const sections = [
    {
        key: 'events',
        title: 'Events',
        render: (event) => (
            <Link
                href={route('events.show', event.id)}
                className="font-medium text-indigo-600 hover:text-indigo-500"
            >
                {event.name} <span className="text-slate-400">({event.code})</span>
            </Link>
        ),
    },
    {
        key: 'documents',
        title: 'Documents',
        render: (document) => (
            <Link
                href={route('events.show', { event: document.event_id, tab: 'documents' })}
                className="font-medium text-indigo-600 hover:text-indigo-500"
            >
                {document.title} <span className="text-slate-400">— {document.event.name}</span>
            </Link>
        ),
    },
    {
        key: 'tasks',
        title: 'Tasks',
        render: (task) => (
            <Link
                href={route('events.show', { event: task.event_id, tab: 'tasks' })}
                className="font-medium text-indigo-600 hover:text-indigo-500"
            >
                {task.title} <span className="text-slate-400">— {task.event.name}</span>
            </Link>
        ),
    },
    {
        key: 'requests',
        title: 'Stakeholder Requests',
        render: (request) => (
            <Link
                href={route('events.show', { event: request.event_id, tab: 'requests' })}
                className="font-medium text-indigo-600 hover:text-indigo-500"
            >
                {request.title} <span className="text-slate-400">— {request.event.name}</span>
            </Link>
        ),
    },
    {
        key: 'organizations',
        title: 'Organizations',
        render: (organization) => (
            <Link
                href={route('organizations.show', organization.id)}
                className="font-medium text-indigo-600 hover:text-indigo-500"
            >
                {organization.name}
            </Link>
        ),
    },
    {
        key: 'contacts',
        title: 'Contacts',
        render: (contact) => (
            <Link
                href={route('organizations.show', contact.organization_id)}
                className="font-medium text-indigo-600 hover:text-indigo-500"
            >
                {contact.full_name}{' '}
                {contact.organization && (
                    <span className="text-slate-400">— {contact.organization.name}</span>
                )}
            </Link>
        ),
    },
];

export default function Index({ query, results }) {
    const hasAnyResults = sections.some((section) => results[section.key].length > 0);

    return (
        <AuthenticatedLayout header="Search">
            <Head title="Search" />

            {query.length > 0 && query.length < 2 && (
                <p className="mb-4 text-sm text-slate-500">Keep typing — enter at least 2 characters.</p>
            )}

            {query.length >= 2 && !hasAnyResults && (
                <Card>
                    <p className="text-sm text-slate-500">
                        No results for &ldquo;{query}&rdquo;.
                    </p>
                </Card>
            )}

            <div className="space-y-6">
                {sections.map(
                    (section) =>
                        results[section.key].length > 0 && (
                            <Card key={section.key} title={section.title}>
                                <ul className="space-y-2 text-sm">
                                    {results[section.key].map((item) => (
                                        <li key={item.id}>{section.render(item)}</li>
                                    ))}
                                </ul>
                            </Card>
                        ),
                )}
            </div>
        </AuthenticatedLayout>
    );
}
