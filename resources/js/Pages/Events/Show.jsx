import EventLogo from '@/Components/EventLogo';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { POLL_INTERVAL } from '@/pollInterval';
import { Head, router, usePage, usePoll } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import AbstractsTab from './Partials/AbstractsTab';
import ActivityTab from './Partials/ActivityTab';
import ClosureTab from './Partials/ClosureTab';
import DeadlinesTab from './Partials/DeadlinesTab';
import DecisionsTab from './Partials/DecisionsTab';
import DocumentsTab from './Partials/DocumentsTab';
import FloorPlanTab from './Partials/FloorPlanTab';
import IdeasTab from './Partials/IdeasTab';
import MeetingsTab from './Partials/MeetingsTab';
import OrganizationsTab from './Partials/OrganizationsTab';
import OverviewTab from './Partials/OverviewTab';
import RecentlyDeletedTab from './Partials/RecentlyDeletedTab';
import RemindersTab from './Partials/RemindersTab';
import RequestsTab from './Partials/RequestsTab';
import TasksTab from './Partials/TasksTab';
import TeamTab from './Partials/TeamTab';

// Fifteen flat tabs were too many to scan, so they're grouped into sections;
// a section's sub-tabs only appear once you're in it.
const tabGroups = [
    {
        key: 'overview',
        label: 'Overview',
        tabs: [
            { key: 'overview', label: 'Summary' },
            { key: 'deadlines', label: 'Deadlines' },
        ],
    },
    {
        key: 'planning',
        label: 'Planning',
        tabs: [
            { key: 'tasks', label: 'Tasks' },
            { key: 'ideas', label: 'Ideas' },
            { key: 'meetings', label: 'Meetings' },
            { key: 'decisions', label: 'Decisions' },
        ],
    },
    {
        key: 'people',
        label: 'People',
        tabs: [
            { key: 'team', label: 'Team' },
            { key: 'organizations', label: 'Organizations' },
            { key: 'requests', label: 'Requests' },
            { key: 'reminders', label: 'Reminders' },
        ],
    },
    {
        key: 'content',
        label: 'Content',
        tabs: [
            { key: 'documents', label: 'Documents' },
            { key: 'abstracts', label: 'Abstracts' },
        ],
    },
    { key: 'venue', label: 'Floor Plan', tabs: [{ key: 'floor-plan', label: 'Floor Plan' }] },
    {
        key: 'wrap-up',
        label: 'Wrap-up',
        tabs: [
            { key: 'activity', label: 'Activity' },
            { key: 'closure', label: 'Closure' },
            { key: 'deleted', label: 'Recently deleted' },
        ],
    },
];

const allTabKeys = tabGroups.flatMap((group) => group.tabs.map((tab) => tab.key));

// The active tab lives in the URL (?tab=), not component state, so the back
// button, a page refresh, and a shared link all land on the same tab.
function tabFromUrl(url) {
    const requested = new URL(url, 'http://localhost').searchParams.get('tab');

    return allTabKeys.includes(requested) ? requested : 'overview';
}

// Every tab's data is loaded upfront in one request, but the page polls every
// POLL_INTERVAL for live updates from other users — without this map, that poll
// would re-fetch all ~15 relations on every tick regardless of which tab is
// visible. Only reload the prop(s) the currently active tab actually renders.
const TAB_PROPS = {
    overview: ['event'],
    deadlines: ['meetings', 'tasks', 'reminders', 'stakeholderRequests'],
    team: ['team'],
    organizations: ['organizations'],
    ideas: ['ideas'],
    tasks: ['tasks'],
    meetings: ['meetings', 'documents'],
    decisions: ['decisions', 'meetings', 'documents'],
    requests: ['stakeholderRequests'],
    reminders: ['reminders'],
    documents: ['documents'],
    'floor-plan': ['floorPlans', 'organizations'],
    abstracts: ['abstractCall', 'committeeMembers'],
    activity: ['activityLogs', 'documentActivities'],
    closure: ['event', 'lessonsLearned'],
    deleted: ['recentlyDeleted'],
};

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
    floorPlans,
    abstractCall,
    committeeRoles,
    reviewRecommendations,
    availableUsers,
    availableCommitteeUsers,
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
    recentlyDeleted,
}) {
    const { url } = usePage();
    const activeTab = tabFromUrl(url);
    const activeGroup = tabGroups.find((group) => group.tabs.some((tab) => tab.key === activeTab));
    const tabsRef = useRef(null);

    // On a phone the section and sub-tab rows scroll sideways; keep whichever
    // tab is active (e.g. "Floor Plan", far to the right) in view.
    useEffect(() => {
        tabsRef.current
            ?.querySelectorAll('[aria-current]')
            .forEach((tab) => tab.scrollIntoView({ block: 'nearest', inline: 'nearest' }));
    }, [activeTab]);
    usePoll(POLL_INTERVAL, { preserveScroll: true, only: TAB_PROPS[activeTab] ?? [] });

    function setActiveTab(key) {
        if (key === activeTab) {
            return;
        }

        const next = new URL(url, window.location.origin);
        next.searchParams.set('tab', key);

        router.push({ url: next.pathname + next.search, preserveScroll: true, preserveState: true });
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex min-w-0 items-center gap-3">
                    {/* Logo hidden on phones — the top bar is too narrow for logo + name. */}
                    <span className="hidden shrink-0 sm:block">
                        <EventLogo event={event} size="md" />
                    </span>
                    <span className="truncate">{event.name}</span>
                </div>
            }
            back={{ href: route('events.index'), label: 'Back to Events' }}
        >
            <Head title={event.name} />


            <nav ref={tabsRef} aria-label="Event sections" className="mb-6">
                <div className="flex items-center gap-1 overflow-x-auto border-b border-slate-200 pr-8 mask-[linear-gradient(to_right,black_calc(100%-2rem),transparent)] sm:pr-0 sm:mask-none">
                    {tabGroups.map((group) => (
                        <button
                            key={group.key}
                            type="button"
                            onClick={() => setActiveTab(group.tabs[0].key)}
                            aria-current={activeGroup?.key === group.key ? 'page' : undefined}
                            className={`shrink-0 border-b-2 px-4 py-2.5 text-sm font-semibold whitespace-nowrap transition ${
                                activeGroup?.key === group.key
                                    ? 'border-indigo-600 text-indigo-600'
                                    : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'
                            }`}
                        >
                            {group.label}
                        </button>
                    ))}
                </div>

                {activeGroup && activeGroup.tabs.length > 1 && (
                    <div className="mt-3 flex items-center gap-1.5 overflow-x-auto pr-8 mask-[linear-gradient(to_right,black_calc(100%-2rem),transparent)] sm:pr-0 sm:mask-none">
                        {activeGroup.tabs.map((tab) => (
                            <button
                                key={tab.key}
                                type="button"
                                onClick={() => setActiveTab(tab.key)}
                                aria-current={activeTab === tab.key ? 'true' : undefined}
                                className={`shrink-0 rounded-full px-3.5 py-1.5 text-sm font-medium whitespace-nowrap transition ${
                                    activeTab === tab.key
                                        ? 'bg-indigo-600 text-white shadow-sm'
                                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                                }`}
                            >
                                {tab.label}
                            </button>
                        ))}
                    </div>
                )}
            </nav>

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

            {activeTab === 'floor-plan' && (
                <FloorPlanTab event={event} floorPlans={floorPlans} organizations={organizations} />
            )}

            {activeTab === 'abstracts' && (
                <AbstractsTab
                    event={event}
                    abstractCall={abstractCall}
                    committeeMembers={committeeMembers}
                    availableCommitteeUsers={availableCommitteeUsers}
                    committeeRoles={committeeRoles}
                    reviewRecommendations={reviewRecommendations}
                />
            )}

            {activeTab === 'activity' && (
                <ActivityTab activityLogs={activityLogs} documentActivities={documentActivities} />
            )}

            {activeTab === 'deleted' && <RecentlyDeletedTab items={recentlyDeleted} />}

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
