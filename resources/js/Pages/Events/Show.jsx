import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import DocumentsTab from './Partials/DocumentsTab';
import IdeasTab from './Partials/IdeasTab';
import OrganizationsTab from './Partials/OrganizationsTab';
import OverviewTab from './Partials/OverviewTab';
import TasksTab from './Partials/TasksTab';
import TeamTab from './Partials/TeamTab';

const tabs = [
    { key: 'overview', label: 'Overview' },
    { key: 'team', label: 'Team' },
    { key: 'organizations', label: 'Organizations' },
    { key: 'ideas', label: 'Ideas' },
    { key: 'tasks', label: 'Tasks' },
    { key: 'documents', label: 'Documents' },
];

function initialTab() {
    if (typeof window === 'undefined') {
        return 'overview';
    }

    const requested = new URLSearchParams(window.location.search).get('tab');

    return tabs.some((tab) => tab.key === requested) ? requested : 'overview';
}

export default function Show({
    event,
    team,
    organizations,
    ideas,
    tasks,
    documents,
    availableUsers,
    availableOrganizations,
    roles,
    relationshipTypes,
    ideaStatuses,
    taskStatuses,
    taskPriorities,
    documentCategories,
    documentVisibilities,
    status,
}) {
    const [activeTab, setActiveTab] = useState(initialTab);

    return (
        <AuthenticatedLayout header={event.name}>
            <Head title={event.name} />

            {status && (
                <div className="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    {status}
                </div>
            )}

            <div className="mb-6 flex items-center gap-2 border-b border-slate-200">
                {tabs.map((tab) => (
                    <button
                        key={tab.key}
                        type="button"
                        onClick={() => setActiveTab(tab.key)}
                        className={`border-b-2 px-3 py-2 text-sm font-medium transition ${
                            activeTab === tab.key
                                ? 'border-indigo-600 text-indigo-600'
                                : 'border-transparent text-slate-500 hover:text-slate-700'
                        }`}
                    >
                        {tab.label}
                    </button>
                ))}
            </div>

            {activeTab === 'overview' && <OverviewTab event={event} />}

            {activeTab === 'team' && (
                <TeamTab
                    event={event}
                    team={team}
                    availableUsers={availableUsers}
                    roles={roles}
                />
            )}

            {activeTab === 'organizations' && (
                <OrganizationsTab
                    event={event}
                    organizations={organizations}
                    availableOrganizations={availableOrganizations}
                    relationshipTypes={relationshipTypes}
                />
            )}

            {activeTab === 'ideas' && (
                <IdeasTab event={event} ideas={ideas} statuses={ideaStatuses} />
            )}

            {activeTab === 'tasks' && (
                <TasksTab
                    event={event}
                    tasks={tasks}
                    availableUsers={availableUsers}
                    availableOrganizations={availableOrganizations}
                    taskStatuses={taskStatuses}
                    taskPriorities={taskPriorities}
                />
            )}

            {activeTab === 'documents' && (
                <DocumentsTab
                    event={event}
                    documents={documents}
                    availableOrganizations={availableOrganizations}
                    documentCategories={documentCategories}
                    documentVisibilities={documentVisibilities}
                />
            )}
        </AuthenticatedLayout>
    );
}
