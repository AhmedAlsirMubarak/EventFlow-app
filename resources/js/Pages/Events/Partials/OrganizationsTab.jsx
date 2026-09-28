import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import DataTable from '@/Components/UI/DataTable';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import OrganizationLogo from '@/Components/OrganizationLogo';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SelectInput from '@/Components/SelectInput';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';
import { useConfirm } from '@/utils/useConfirm';
import { Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function OrganizationsTab({
    event,
    organizations,
    availableOrganizations,
    relationshipTypes,
}) {
    const [modalOpen, setModalOpen] = useState(false);
    const form = useForm({ organization_id: '', relationship_type: '' });
    const { confirm, dialogProps } = useConfirm();

    const eventOrgIds = organizations.map((org) => org.id);
    const addableOrganizations = availableOrganizations.filter(
        (o) => !eventOrgIds.includes(o.id),
    );

    function openModal() {
        form.reset();
        form.setData('organization_id', addableOrganizations[0]?.id ?? '');
        setModalOpen(true);
    }

    function submit(e) {
        e.preventDefault();
        form.post(route('events.organizations.store', event.id), {
            onSuccess: () => setModalOpen(false),
        });
    }

    function removeOrg(org) {
        confirm({
            title: 'Remove organization',
            message: `Remove ${org.name} from this event?`,
            confirmLabel: 'Remove',
            danger: true,
        }).then((confirmed) => {
            if (confirmed) {
                router.delete(
                    route('events.organizations.destroy', [event.id, org.id]),
                );
            }
        });
    }

    const columns = [
        {
            key: 'name',
            header: 'Organization',
            render: (org) => (
                <Link
                    href={route('organizations.show', org.id)}
                    className="flex items-center gap-2 font-medium text-indigo-600 hover:text-indigo-500"
                >
                    <OrganizationLogo organization={org} size="xs" />
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
        <>
            <Card
                title="Participating Organizations"
                actions={
                    addableOrganizations.length > 0 && (
                        <button
                            type="button"
                            onClick={openModal}
                            className="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500"
                        >
                            Add Organization
                        </button>
                    )
                }
                className="p-0"
            >
                <DataTable
                    columns={columns}
                    rows={organizations}
                    emptyMessage="No organizations linked to this event yet."
                    emptyAction={
                        addableOrganizations.length > 0
                            ? { label: 'Link an organization', onClick: openModal }
                            : undefined
                    }
                />
            </Card>

            <Modal show={modalOpen} onClose={() => setModalOpen(false)} maxWidth="sm">
                <form onSubmit={submit} className="space-y-4 p-6">
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
                            value={form.data.organization_id}
                            onChange={(e) =>
                                form.setData(
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
                            value={form.data.relationship_type}
                            onChange={(e) =>
                                form.setData(
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
                        <PrimaryButton disabled={form.processing}>Add</PrimaryButton>
                        <SecondaryButton type="button" onClick={() => setModalOpen(false)}>
                            Cancel
                        </SecondaryButton>
                    </div>
                </form>
            </Modal>

            <ConfirmDialog {...dialogProps} />
        </>
    );
}
