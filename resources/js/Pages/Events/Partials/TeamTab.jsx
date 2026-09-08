import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import DataTable from '@/Components/UI/DataTable';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SelectInput from '@/Components/SelectInput';
import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function TeamTab({ event, team, availableUsers, roles }) {
    const [modalOpen, setModalOpen] = useState(false);
    const form = useForm({ user_id: '', role_in_event: '' });

    const teamUserIds = team.map((member) => member.id);
    const addableUsers = availableUsers.filter((u) => !teamUserIds.includes(u.id));

    function openModal() {
        form.reset();
        form.setData('user_id', addableUsers[0]?.id ?? '');
        setModalOpen(true);
    }

    function submit(e) {
        e.preventDefault();
        form.post(route('events.team.store', event.id), {
            onSuccess: () => setModalOpen(false),
        });
    }

    function removeMember(user) {
        if (confirm(`Remove ${user.name} from this event's team?`)) {
            router.delete(route('events.team.destroy', [event.id, user.id]));
        }
    }

    const columns = [
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

    return (
        <>
            <Card
                title="Internal Team"
                actions={
                    <button
                        type="button"
                        onClick={openModal}
                        className="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500"
                    >
                        Add Team Member
                    </button>
                }
                className="p-0"
            >
                <DataTable
                    columns={columns}
                    rows={team}
                    emptyMessage="No team members assigned yet."
                />
            </Card>

            <Modal show={modalOpen} onClose={() => setModalOpen(false)} maxWidth="sm">
                {addableUsers.length === 0 ? (
                    <div className="space-y-4 p-6">
                        <h3 className="text-lg font-semibold text-slate-900">
                            Add Team Member
                        </h3>
                        <p className="text-sm text-slate-500">
                            Every internal user is already on this event&apos;s team. Add more
                            internal users from Admin &gt; Users, then they&apos;ll show up here.
                        </p>
                        <SecondaryButton
                            type="button"
                            onClick={() => setModalOpen(false)}
                            className="!text-sm !normal-case !tracking-normal"
                        >
                            Close
                        </SecondaryButton>
                    </div>
                ) : (
                    <form onSubmit={submit} className="space-y-4 p-6">
                        <h3 className="text-lg font-semibold text-slate-900">
                            Add Team Member
                        </h3>

                        <div>
                            <InputLabel htmlFor="user_id" value="User" />
                            <SelectInput
                                id="user_id"
                                className="mt-1 block w-full"
                                value={form.data.user_id}
                                onChange={(e) =>
                                    form.setData('user_id', e.target.value)
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
                                value={form.data.role_in_event}
                                onChange={(e) =>
                                    form.setData('role_in_event', e.target.value)
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
                                disabled={form.processing}
                                className="!bg-indigo-600 !text-sm !normal-case !tracking-normal hover:!bg-indigo-500 focus:!bg-indigo-500 active:!bg-indigo-700"
                            >
                                Add
                            </PrimaryButton>
                            <SecondaryButton
                                type="button"
                                onClick={() => setModalOpen(false)}
                                className="!text-sm !normal-case !tracking-normal"
                            >
                                Cancel
                            </SecondaryButton>
                        </div>
                    </form>
                )}
            </Modal>
        </>
    );
}
