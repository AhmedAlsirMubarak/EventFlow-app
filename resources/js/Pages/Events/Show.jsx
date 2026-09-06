import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import DataTable from '@/Components/UI/DataTable';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SelectInput from '@/Components/SelectInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

const statusColors = {
    Planning: 'amber',
    Active: 'emerald',
    Completed: 'slate',
    Cancelled: 'rose',
};

const tabs = [
    { key: 'overview', label: 'Overview' },
    { key: 'team', label: 'Team' },
    { key: 'organizations', label: 'Organizations' },
];

export default function Show({
    event,
    team,
    organizations,
    availableUsers,
    availableOrganizations,
    roles,
    relationshipTypes,
    status,
}) {
    const [activeTab, setActiveTab] = useState('overview');
    const [memberModalOpen, setMemberModalOpen] = useState(false);
    const [orgModalOpen, setOrgModalOpen] = useState(false);

    const memberForm = useForm({ user_id: '', role_in_event: '' });
    const orgForm = useForm({ organization_id: '', relationship_type: '' });

    const teamUserIds = team.map((member) => member.id);
    const eventOrgIds = organizations.map((org) => org.id);
    const addableUsers = availableUsers.filter((u) => !teamUserIds.includes(u.id));
    const addableOrganizations = availableOrganizations.filter(
        (o) => !eventOrgIds.includes(o.id),
    );

    function openMemberModal() {
        memberForm.reset();
        memberForm.setData('user_id', addableUsers[0]?.id ?? '');
        setMemberModalOpen(true);
    }

    function submitMember(e) {
        e.preventDefault();
        memberForm.post(route('events.team.store', event.id), {
            onSuccess: () => setMemberModalOpen(false),
        });
    }

    function removeMember(user) {
        if (confirm(`Remove ${user.name} from this event's team?`)) {
            router.delete(route('events.team.destroy', [event.id, user.id]));
        }
    }

    function openOrgModal() {
        orgForm.reset();
        orgForm.setData('organization_id', addableOrganizations[0]?.id ?? '');
        setOrgModalOpen(true);
    }

    function submitOrg(e) {
        e.preventDefault();
        orgForm.post(route('events.organizations.store', event.id), {
            onSuccess: () => setOrgModalOpen(false),
        });
    }

    function removeOrg(org) {
        if (confirm(`Remove ${org.name} from this event?`)) {
            router.delete(
                route('events.organizations.destroy', [event.id, org.id]),
            );
        }
    }

    const teamColumns = [
        { key: 'name', header: 'Name' },
        { key: 'email', header: 'Email' },
        {
            key: 'role_in_event',
            header: 'Role',
            render: (member) =>
                member.pivot.role_in_event ? (
                    <Badge color="indigo">{member.pivot.role_in_event}</Badge>
                ) : (
                    '—'
                ),
        },
        {
            key: 'actions',
            header: '',
            render: (member) => (
                <button
                    type="button"
                    onClick={() => removeMember(member)}
                    className="text-sm font-medium text-rose-600 hover:text-rose-500"
                >
                    Remove
                </button>
            ),
        },
    ];

    const orgColumns = [
        {
            key: 'name',
            header: 'Organization',
            render: (org) => (
                <Link
                    href={route('organizations.show', org.id)}
                    className="font-medium text-indigo-600 hover:text-indigo-500"
                >
                    {org.name}
                </Link>
            ),
        },
        { key: 'category', header: 'Category' },
        {
            key: 'relationship_type',
            header: 'Relationship',
            render: (org) =>
                org.pivot.relationship_type ? (
                    <Badge color="indigo">{org.pivot.relationship_type}</Badge>
                ) : (
                    '—'
                ),
        },
        {
            key: 'actions',
            header: '',
            render: (org) => (
                <button
                    type="button"
                    onClick={() => removeOrg(org)}
                    className="text-sm font-medium text-rose-600 hover:text-rose-500"
                >
                    Remove
                </button>
            ),
        },
    ];

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

            {activeTab === 'overview' && (
                <Card
                    title="Event Details"
                    actions={
                        <Link
                            href={route('events.edit', event.id)}
                            className="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                        >
                            Edit
                        </Link>
                    }
                >
                    <dl className="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div>
                            <dt className="text-sm text-slate-500">Status</dt>
                            <dd className="mt-1">
                                <Badge
                                    color={
                                        statusColors[event.status] ?? 'slate'
                                    }
                                >
                                    {event.status}
                                </Badge>
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm text-slate-500">Code</dt>
                            <dd className="mt-1 text-sm text-slate-900">
                                {event.code}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm text-slate-500">
                                Start Date
                            </dt>
                            <dd className="mt-1 text-sm text-slate-900">
                                {event.start_date ?? '—'}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm text-slate-500">
                                End Date
                            </dt>
                            <dd className="mt-1 text-sm text-slate-900">
                                {event.end_date ?? '—'}
                            </dd>
                        </div>
                        {event.description && (
                            <div className="sm:col-span-2">
                                <dt className="text-sm text-slate-500">
                                    Description
                                </dt>
                                <dd className="mt-1 whitespace-pre-line text-sm text-slate-900">
                                    {event.description}
                                </dd>
                            </div>
                        )}
                    </dl>
                </Card>
            )}

            {activeTab === 'team' && (
                <Card
                    title="Internal Team"
                    actions={
                        addableUsers.length > 0 && (
                            <button
                                type="button"
                                onClick={openMemberModal}
                                className="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500"
                            >
                                Add Team Member
                            </button>
                        )
                    }
                    className="p-0"
                >
                    <DataTable
                        columns={teamColumns}
                        rows={team}
                        emptyMessage="No team members assigned yet."
                    />
                </Card>
            )}

            {activeTab === 'organizations' && (
                <Card
                    title="Participating Organizations"
                    actions={
                        addableOrganizations.length > 0 && (
                            <button
                                type="button"
                                onClick={openOrgModal}
                                className="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500"
                            >
                                Add Organization
                            </button>
                        )
                    }
                    className="p-0"
                >
                    <DataTable
                        columns={orgColumns}
                        rows={organizations}
                        emptyMessage="No organizations linked to this event yet."
                    />
                </Card>
            )}

            <Modal show={memberModalOpen} onClose={() => setMemberModalOpen(false)} maxWidth="sm">
                <form onSubmit={submitMember} className="space-y-4 p-6">
                    <h3 className="text-lg font-semibold text-slate-900">
                        Add Team Member
                    </h3>

                    <div>
                        <InputLabel htmlFor="user_id" value="User" />
                        <SelectInput
                            id="user_id"
                            className="mt-1 block w-full"
                            value={memberForm.data.user_id}
                            onChange={(e) =>
                                memberForm.setData('user_id', e.target.value)
                            }
                        >
                            {addableUsers.map((user) => (
                                <option key={user.id} value={user.id}>
                                    {user.name}
                                </option>
                            ))}
                        </SelectInput>
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="role_in_event"
                            value="Role for this Event"
                        />
                        <SelectInput
                            id="role_in_event"
                            className="mt-1 block w-full"
                            value={memberForm.data.role_in_event}
                            onChange={(e) =>
                                memberForm.setData(
                                    'role_in_event',
                                    e.target.value,
                                )
                            }
                        >
                            <option value="">Not specified</option>
                            {roles.map((role) => (
                                <option key={role} value={role}>
                                    {role}
                                </option>
                            ))}
                        </SelectInput>
                    </div>

                    <div className="flex items-center gap-3 pt-2">
                        <PrimaryButton
                            disabled={memberForm.processing}
                            className="bg-indigo-600 text-sm normal-case tracking-normal hover:bg-indigo-500 focus:bg-indigo-500 active:bg-indigo-700"
                        >
                            Add
                        </PrimaryButton>
                        <SecondaryButton
                            type="button"
                            onClick={() => setMemberModalOpen(false)}
                            className="text-sm normal-case tracking-normal"
                        >
                            Cancel
                        </SecondaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={orgModalOpen} onClose={() => setOrgModalOpen(false)} maxWidth="sm">
                <form onSubmit={submitOrg} className="space-y-4 p-6">
                    <h3 className="text-lg font-semibold text-slate-900">
                        Add Organization
                    </h3>

                    <div>
                        <InputLabel
                            htmlFor="organization_id"
                            value="Organization"
                        />
                        <SelectInput
                            id="organization_id"
                            className="mt-1 block w-full"
                            value={orgForm.data.organization_id}
                            onChange={(e) =>
                                orgForm.setData(
                                    'organization_id',
                                    e.target.value,
                                )
                            }
                        >
                            {addableOrganizations.map((org) => (
                                <option key={org.id} value={org.id}>
                                    {org.name}
                                </option>
                            ))}
                        </SelectInput>
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="relationship_type"
                            value="Relationship"
                        />
                        <SelectInput
                            id="relationship_type"
                            className="mt-1 block w-full"
                            value={orgForm.data.relationship_type}
                            onChange={(e) =>
                                orgForm.setData(
                                    'relationship_type',
                                    e.target.value,
                                )
                            }
                        >
                            <option value="">Not specified</option>
                            {relationshipTypes.map((type) => (
                                <option key={type} value={type}>
                                    {type}
                                </option>
                            ))}
                        </SelectInput>
                    </div>

                    <div className="flex items-center gap-3 pt-2">
                        <PrimaryButton
                            disabled={orgForm.processing}
                            className="bg-indigo-600 text-sm normal-case tracking-normal hover:bg-indigo-500 focus:bg-indigo-500 active:bg-indigo-700"
                        >
                            Add
                        </PrimaryButton>
                        <SecondaryButton
                            type="button"
                            onClick={() => setOrgModalOpen(false)}
                            className="text-sm normal-case tracking-normal"
                        >
                            Cancel
                        </SecondaryButton>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
