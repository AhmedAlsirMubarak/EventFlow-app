import EventLogo from '@/Components/EventLogo';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { POLL_INTERVAL } from '@/pollInterval';
import { Head, usePoll } from '@inertiajs/react';
import { useState } from 'react';
import AbstractsTab from './Partials/AbstractsTab';
import ActivityTab from './Partials/ActivityTab';
import ClosureTab from './Partials/ClosureTab';
import DeadlinesTab from './Partials/DeadlinesTab';
import DecisionsTab from './Partials/DecisionsTab';
import DocumentsTab from './Partials/DocumentsTab';
import IdeasTab from './Partials/IdeasTab';
import MeetingsTab from './Partials/MeetingsTab';
import OrganizationsTab from './Partials/OrganizationsTab';
import OverviewTab from './Partials/OverviewTab';
import RemindersTab from './Partials/RemindersTab';
import RequestsTab from './Partials/RequestsTab';
import TasksTab from './Partials/TasksTab';
import TeamTab from './Partials/TeamTab';

const tabs = [
    { key: 'overview', label: 'Overview' },
    { key: 'deadlines', label: 'Deadlines' },
    { key: 'team', label: 'Team' },
    { key: 'organizations', label: 'Organizations' },
    { key: 'ideas', label: 'Ideas' },
    { key: 'tasks', label: 'Tasks' },
    { key: 'meetings', label: 'Meetings' },
    { key: 'decisions', label: 'Decisions' },
    { key: 'requests', label: 'Requests' },
    { key: 'reminders', label: 'Reminders' },
    { key: 'documents', label: 'Documents' },
    { key: 'abstracts', label: 'Abstracts' },
    { key: 'activity', label: 'Activity' },
    { key: 'closure', label: 'Closure' },
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
    meetings,
    decisions,
    stakeholderRequests,
    reminders,
    lessonsLearned,
    activityLogs,
    documentActivities,
    committeeMembers,
    abstractCall,
    committeeRoles,
    reviewRecommendations,
    availableUsers,
    availableOrganizations,
    availableContacts,
    roles,
    relationshipTypes,
    ideaStatuses,
    taskStatuses,
    taskPriorities,
    documentCategories,
    documentVisibilities,
    requestStatuses,
    reminderStatuses,
    lessonCategories,
    status,
}) {
    const [activeTab, setActiveTab] = useState(initialTab);
    usePoll(POLL_INTERVAL, { preserveScroll: true });

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center gap-3">
                    <EventLogo event={event} size="sm" />
                    <span>{event.name}</span>
                </div>
            }
        >
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

            {activeTab === 'overview' && (
                <OverviewTab event={event} availableOrganizations={availableOrganizations} />
            )}

            {activeTab === 'deadlines' && (
                <DeadlinesTab
                    event={event}
                    meetings={meetings}
                    tasks={tasks}
                    reminders={reminders}
                    requests={stakeholderRequests}
                    onNavigateTab={setActiveTab}
                />
            )}

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

            {activeTab === 'meetings' && (
                <MeetingsTab
                    event={event}
                    meetings={meetings}
                    availableUsers={availableUsers}
                    availableContacts={availableContacts}
                    availableOrganizations={availableOrganizations}
                    documents={documents}
                    taskPriorities={taskPriorities}
                />
            )}

            {activeTab === 'decisions' && (
                <DecisionsTab
                    event={event}
                    decisions={decisions}
                    availableUsers={availableUsers}
                    availableOrganizations={availableOrganizations}
                    meetings={meetings}
                    documents={documents}
                />
            )}

            {activeTab === 'requests' && (
                <RequestsTab
                    event={event}
                    requests={stakeholderRequests}
                    availableUsers={availableUsers}
                    availableOrganizations={availableOrganizations}
                    availableContacts={availableContacts}
                    requestStatuses={requestStatuses}
                    taskPriorities={taskPriorities}
                />
            )}

            {activeTab === 'reminders' && (
                <RemindersTab
                    event={event}
                    reminders={reminders}
                    availableUsers={availableUsers}
                    availableOrganizations={availableOrganizations}
                    availableContacts={availableContacts}
                    reminderStatuses={reminderStatuses}
                />
            )}

            {activeTab === 'documents' && (
                <DocumentsTab
                    event={event}
                    documents={documents}
                    availableOrganizations={availableOrganizations}
                    availableContacts={availableContacts}
                    documentCategories={documentCategories}
                    documentVisibilities={documentVisibilities}
                />
            )}

            {activeTab === 'abstracts' && (
                <AbstractsTab
                    event={event}
                    abstractCall={abstractCall}
                    committeeMembers={committeeMembers}
                    availableUsers={availableUsers}
                    committeeRoles={committeeRoles}
                    reviewRecommendations={reviewRecommendations}
                />
            )}

            {activeTab === 'activity' && (
                <ActivityTab activityLogs={activityLogs} documentActivities={documentActivities} />
            )}

            {activeTab === 'closure' && (
                <ClosureTab
                    event={event}
                    lessonsLearned={lessonsLearned}
                    lessonCategories={lessonCategories}
                />
            )}
        </AuthenticatedLayout>
    );
}
