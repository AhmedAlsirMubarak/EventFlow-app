import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import Checkbox from '@/Components/Checkbox';
import DataTable from '@/Components/UI/DataTable';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SelectInput from '@/Components/SelectInput';
import TextareaInput from '@/Components/TextareaInput';
import TextInput from '@/Components/TextInput';
import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';

const roleColors = { Chair: 'indigo', Reviewer: 'slate' };

function toDatetimeLocal(value) {
    return value ? value.slice(0, 16) : '';
}

export default function AbstractsTab({ event, abstractCall, committeeMembers, availableUsers, committeeRoles }) {
    const [callModalOpen, setCallModalOpen] = useState(false);
    const [trackModalOpen, setTrackModalOpen] = useState(false);
    const [editingTrack, setEditingTrack] = useState(null);
    const [committeeModalOpen, setCommitteeModalOpen] = useState(false);
    const [linkCopied, setLinkCopied] = useState(false);

    const callForm = useForm({
        instructions: abstractCall?.instructions ?? '',
        opens_at: toDatetimeLocal(abstractCall?.opens_at),
        closes_at: toDatetimeLocal(abstractCall?.closes_at),
        is_open: abstractCall?.is_open ?? true,
    });
    const trackForm = useForm({ name: '', description: '' });
    const committeeForm = useForm({ user_id: '', committee_role: 'Reviewer' });

    const committeeUserIds = committeeMembers.map((member) => member.user.id);
    const addableUsers = availableUsers.filter((u) => !committeeUserIds.includes(u.id));

    function openCallModal() {
        callForm.setData({
            instructions: abstractCall?.instructions ?? '',
            opens_at: toDatetimeLocal(abstractCall?.opens_at),
            closes_at: toDatetimeLocal(abstractCall?.closes_at),
            is_open: abstractCall?.is_open ?? true,
        });
        callForm.clearErrors();
        setCallModalOpen(true);
    }

    function submitCall(e) {
        e.preventDefault();
        const options = { onSuccess: () => setCallModalOpen(false) };

        if (abstractCall) {
            callForm.put(route('abstract-calls.update', abstractCall.id), options);
        } else {
            callForm.post(route('abstract-calls.store', event.id), options);
        }
    }

    function openCreateTrack() {
        setEditingTrack(null);
        trackForm.reset();
        trackForm.clearErrors();
        setTrackModalOpen(true);
    }

    function openEditTrack(track) {
        setEditingTrack(track);
        trackForm.setData({ name: track.name, description: track.description ?? '' });
        trackForm.clearErrors();
        setTrackModalOpen(true);
    }

    function submitTrack(e) {
        e.preventDefault();
        const options = { onSuccess: () => setTrackModalOpen(false) };

        if (editingTrack) {
            trackForm.put(route('abstract-tracks.update', editingTrack.id), options);
        } else {
            trackForm.post(route('abstract-tracks.store', abstractCall.id), options);
        }
    }

    function destroyTrack(track) {
        if (confirm(`Remove the track "${track.name}"?`)) {
            router.delete(route('abstract-tracks.destroy', track.id));
        }
    }

    function openCommitteeModal() {
        committeeForm.reset();
        committeeForm.setData('user_id', addableUsers[0]?.id ?? '');
        setCommitteeModalOpen(true);
    }

    function submitCommittee(e) {
        e.preventDefault();
        committeeForm.post(route('committee.store', event.id), {
            onSuccess: () => setCommitteeModalOpen(false),
        });
    }

    function removeCommitteeMember(member) {
        if (confirm(`Remove ${member.user.name} from the committee?`)) {
            router.delete(route('committee.destroy', member.id));
        }
    }

    function copyLink() {
        const url = `${window.location.origin}/call/${abstractCall.public_token}`;
        navigator.clipboard.writeText(url).then(() => {
            setLinkCopied(true);
            setTimeout(() => setLinkCopied(false), 2000);
        });
    }

    const committeeColumns = [
        { key: 'name', header: 'Name', render: (member) => member.user.name },
        {
            key: 'committee_role',
            header: 'Role',
            render: (member) => (
                <Badge color={roleColors[member.committee_role] ?? 'slate'}>{member.committee_role}</Badge>
            ),
        },
        {
            key: 'actions',
            header: '',
            render: (member) => (
                <button
                    type="button"
                    onClick={() => removeCommitteeMember(member)}
                    className="text-sm font-medium text-rose-600 hover:text-rose-500"
                >
                    Remove
                </button>
            ),
        },
    ];

    const trackColumns = [
        { key: 'name', header: 'Track' },
        { key: 'description', header: 'Description', render: (track) => track.description ?? '—' },
        {
            key: 'actions',
            header: '',
            render: (track) => (
                <div className="flex justify-end gap-3 text-sm">
                    <button
                        type="button"
                        onClick={() => openEditTrack(track)}
                        className="font-medium text-indigo-600 hover:text-indigo-500"
                    >
                        Edit
                    </button>
                    <button
                        type="button"
                        onClick={() => destroyTrack(track)}
                        className="font-medium text-rose-600 hover:text-rose-500"
                    >
                        Remove
                    </button>
                </div>
            ),
        },
    ];

    return (
        <div className="space-y-6">
            <Card
                title="Call for Abstracts"
                actions={
                    <button
                        type="button"
                        onClick={openCallModal}
                        className="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500"
                    >
                        {abstractCall ? 'Edit Call' : 'Create Call'}
                    </button>
                }
            >
                {!abstractCall && (
                    <p className="text-sm text-slate-500">
                        No call for abstracts yet. Create one to get a public submission link.
                    </p>
                )}

                {abstractCall && (
                    <div className="space-y-4">
                        <div className="flex items-center gap-2">
                            <Badge color={abstractCall.is_open ? 'emerald' : 'slate'}>
                                {abstractCall.is_open ? 'Open' : 'Closed'}
                            </Badge>
                            {abstractCall.opens_at && (
                                <span className="text-sm text-slate-500">
                                    Opens {new Date(abstractCall.opens_at).toLocaleString()}
                                </span>
                            )}
                            {abstractCall.closes_at && (
                                <span className="text-sm text-slate-500">
                                    Closes {new Date(abstractCall.closes_at).toLocaleString()}
                                </span>
                            )}
                        </div>

                        {abstractCall.instructions && (
                            <p className="whitespace-pre-line text-sm text-slate-700">
                                {abstractCall.instructions}
                            </p>
                        )}

                        <div className="flex items-center gap-2">
                            <TextInput
                                readOnly
                                className="w-full max-w-md text-xs"
                                value={`${window.location.origin}/call/${abstractCall.public_token}`}
                            />
                            <SecondaryButton
                                type="button"
                                onClick={copyLink}
                                className="!text-sm !normal-case !tracking-normal"
                            >
                                {linkCopied ? 'Copied!' : 'Copy Link'}
                            </SecondaryButton>
                        </div>
                    </div>
                )}
            </Card>

            {abstractCall && (
                <Card
                    title="Tracks"
                    actions={
                        <button
                            type="button"
                            onClick={openCreateTrack}
                            className="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500"
                        >
                            Add Track
                        </button>
                    }
                    className="p-0"
                >
                    <DataTable
                        columns={trackColumns}
                        rows={abstractCall.tracks}
                        emptyMessage="No tracks yet — submitters will see a single general category."
                    />
                </Card>
            )}

            <Card
                title="Review Committee"
                actions={
                    addableUsers.length > 0 && (
                        <button
                            type="button"
                            onClick={openCommitteeModal}
                            className="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500"
                        >
                            Add Committee Member
                        </button>
                    )
                }
                className="p-0"
            >
                <DataTable
                    columns={committeeColumns}
                    rows={committeeMembers}
                    emptyMessage="No committee members assigned yet."
                />
            </Card>

            <Modal show={callModalOpen} onClose={() => setCallModalOpen(false)} maxWidth="lg">
                <form onSubmit={submitCall} className="space-y-4 p-6">
                    <h3 className="text-lg font-semibold text-slate-900">
                        {abstractCall ? 'Edit Call for Abstracts' : 'Create Call for Abstracts'}
                    </h3>

                    <div>
                        <InputLabel htmlFor="instructions" value="Instructions for Submitters" />
                        <TextareaInput
                            id="instructions"
                            rows={4}
                            className="mt-1 block w-full"
                            value={callForm.data.instructions}
                            onChange={(e) => callForm.setData('instructions', e.target.value)}
                        />
                        <InputError className="mt-2" message={callForm.errors.instructions} />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel htmlFor="opens_at" value="Opens At" />
                            <TextInput
                                id="opens_at"
                                type="datetime-local"
                                className="mt-1 block w-full"
                                value={callForm.data.opens_at}
                                onChange={(e) => callForm.setData('opens_at', e.target.value)}
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="closes_at" value="Closes At" />
                            <TextInput
                                id="closes_at"
                                type="datetime-local"
                                className="mt-1 block w-full"
                                value={callForm.data.closes_at}
                                onChange={(e) => callForm.setData('closes_at', e.target.value)}
                            />
                            <InputError className="mt-2" message={callForm.errors.closes_at} />
                        </div>
                    </div>

                    {abstractCall && (
                        <label className="flex items-center gap-2 text-sm text-slate-700">
                            <Checkbox
                                checked={callForm.data.is_open}
                                onChange={(e) => callForm.setData('is_open', e.target.checked)}
                            />
                            Accepting submissions
                        </label>
                    )}

                    <div className="flex items-center gap-3 pt-2">
                        <PrimaryButton
                            disabled={callForm.processing}
                            className="!bg-indigo-600 !text-sm !normal-case !tracking-normal hover:!bg-indigo-500 focus:!bg-indigo-500 active:!bg-indigo-700"
                        >
                            {abstractCall ? 'Save Changes' : 'Create Call'}
                        </PrimaryButton>
                        <SecondaryButton
                            type="button"
                            onClick={() => setCallModalOpen(false)}
                            className="!text-sm !normal-case !tracking-normal"
                        >
                            Cancel
                        </SecondaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={trackModalOpen} onClose={() => setTrackModalOpen(false)} maxWidth="md">
                <form onSubmit={submitTrack} className="space-y-4 p-6">
                    <h3 className="text-lg font-semibold text-slate-900">
                        {editingTrack ? 'Edit Track' : 'Add Track'}
                    </h3>

                    <div>
                        <InputLabel htmlFor="track_name" value="Name" />
                        <TextInput
                            id="track_name"
                            className="mt-1 block w-full"
                            value={trackForm.data.name}
                            onChange={(e) => trackForm.setData('name', e.target.value)}
                            required
                            autoFocus
                        />
                        <InputError className="mt-2" message={trackForm.errors.name} />
                    </div>

                    <div>
                        <InputLabel htmlFor="track_description" value="Description" />
                        <TextareaInput
                            id="track_description"
                            rows={2}
                            className="mt-1 block w-full"
                            value={trackForm.data.description}
                            onChange={(e) => trackForm.setData('description', e.target.value)}
                        />
                    </div>

                    <div className="flex items-center gap-3 pt-2">
                        <PrimaryButton
                            disabled={trackForm.processing}
                            className="!bg-indigo-600 !text-sm !normal-case !tracking-normal hover:!bg-indigo-500 focus:!bg-indigo-500 active:!bg-indigo-700"
                        >
                            {editingTrack ? 'Save Changes' : 'Add Track'}
                        </PrimaryButton>
                        <SecondaryButton
                            type="button"
                            onClick={() => setTrackModalOpen(false)}
                            className="!text-sm !normal-case !tracking-normal"
                        >
                            Cancel
                        </SecondaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={committeeModalOpen} onClose={() => setCommitteeModalOpen(false)} maxWidth="sm">
                <form onSubmit={submitCommittee} className="space-y-4 p-6">
                    <h3 className="text-lg font-semibold text-slate-900">Add Committee Member</h3>

                    <div>
                        <InputLabel htmlFor="committee_user_id" value="User" />
                        <SelectInput
                            id="committee_user_id"
                            className="mt-1 block w-full"
                            value={committeeForm.data.user_id}
                            onChange={(e) => committeeForm.setData('user_id', e.target.value)}
                        >
                            {addableUsers.map((user) => (
                                <option key={user.id} value={user.id}>
                                    {user.name}
                                </option>
                            ))}
                        </SelectInput>
                    </div>

                    <div>
                        <InputLabel htmlFor="committee_role" value="Committee Role" />
                        <SelectInput
                            id="committee_role"
                            className="mt-1 block w-full"
                            value={committeeForm.data.committee_role}
                            onChange={(e) => committeeForm.setData('committee_role', e.target.value)}
                        >
                            {committeeRoles.map((role) => (
                                <option key={role} value={role}>
                                    {role}
                                </option>
                            ))}
                        </SelectInput>
                    </div>

                    <div className="flex items-center gap-3 pt-2">
                        <PrimaryButton
                            disabled={committeeForm.processing}
                            className="!bg-indigo-600 !text-sm !normal-case !tracking-normal hover:!bg-indigo-500 focus:!bg-indigo-500 active:!bg-indigo-700"
                        >
                            Add
                        </PrimaryButton>
                        <SecondaryButton
                            type="button"
                            onClick={() => setCommitteeModalOpen(false)}
                            className="!text-sm !normal-case !tracking-normal"
                        >
                            Cancel
                        </SecondaryButton>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
