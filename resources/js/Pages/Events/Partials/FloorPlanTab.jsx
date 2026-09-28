import Card from '@/Components/UI/Card';
import FileDropzone from '@/Components/FileDropzone';
import { IconPencil } from '@/Components/Icons';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SelectInput from '@/Components/SelectInput';
import TextareaInput from '@/Components/TextareaInput';
import TextInput from '@/Components/TextInput';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';
import { useConfirm } from '@/utils/useConfirm';
import { validateFile } from '@/utils/validateFile';
import { router, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { Rnd } from 'react-rnd';

const IMAGE_EXTENSIONS = ['.jpg', '.jpeg', '.png', '.webp'];
const IMAGE_MAX_BYTES = 5 * 1024 * 1024;
const DEFAULT_BOOTH = { x: 40, y: 40, width: 12, height: 10 };

// A visible, finger-sized corner grip (the default handles are invisible 10px
// strips, fine for a mouse but impossible to hit on a tablet). Hidden until
// hover on desktop; always shown on touch devices, which have no hover. Booths
// also get `touch-none` so the page doesn't scroll under a finger dragging one.
const RESIZE_GRIP_STYLE = { width: 22, height: 22, right: -6, bottom: -6 };

function ResizeGrip() {
    return (
        <span className="flex h-full w-full items-center justify-center opacity-0 transition group-hover:opacity-100 [@media(hover:none)]:opacity-100">
            <span className="h-3 w-3 rounded-sm border-2 border-indigo-500 bg-white shadow" />
        </span>
    );
}

function initials(name) {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0].toUpperCase())
        .join('');
}

/**
 * One booth on the floor plan. The logo scales to fill the booth (rather than
 * sitting at a fixed tiny size) so wide wordmarks stay legible, and details
 * drop away progressively as the booth gets too small to hold them.
 */
function BoothCard({ booth, widthPx, heightPx, onEdit }) {
    const assigned = Boolean(booth.organization);
    const showName = heightPx >= 64 && widthPx >= 56;
    const showLogo = heightPx >= 36 && widthPx >= 36;

    return (
        <div
            className={`relative flex h-full w-full flex-col overflow-hidden rounded-lg border-2 transition group-hover:shadow-lg group-hover:ring-2 group-hover:ring-indigo-400/60 ${
                assigned
                    ? 'border-emerald-500 bg-white shadow-md'
                    : 'border-dashed border-slate-400 bg-white/75 backdrop-blur-[1px]'
            }`}
            title={assigned ? `${booth.booth_number} · ${booth.organization.name}` : `${booth.booth_number} · Available`}
        >
            <span
                className={`absolute left-1 top-1 z-10 rounded px-1.5 py-0.5 text-[10px] font-bold leading-none ${
                    assigned ? 'bg-emerald-600 text-white' : 'bg-slate-700/80 text-white'
                }`}
            >
                {booth.booth_number}
            </span>

            <button
                type="button"
                onMouseDown={(e) => e.stopPropagation()}
                onTouchStart={(e) => e.stopPropagation()}
                onClick={onEdit}
                title="Edit booth"
                aria-label={`Edit booth ${booth.booth_number}`}
                className="absolute right-1 top-1 z-10 rounded bg-white/90 p-0.5 text-slate-500 opacity-0 shadow-sm transition hover:text-indigo-600 focus:opacity-100 group-hover:opacity-100 [@media(hover:none)]:p-1.5 [@media(hover:none)]:opacity-100"
            >
                <IconPencil className="h-3 w-3" />
            </button>

            {assigned ? (
                <>
                    <div className="flex min-h-0 flex-1 items-center justify-center px-2 pb-1 pt-5">
                        {showLogo &&
                            (booth.organization.logo_url ? (
                                <img
                                    src={booth.organization.logo_url}
                                    alt={booth.organization.name}
                                    draggable={false}
                                    className="max-h-full max-w-full select-none object-contain"
                                />
                            ) : (
                                <span className="flex aspect-square h-full max-h-12 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-700">
                                    {initials(booth.organization.name)}
                                </span>
                            ))}
                    </div>
                    {showName && (
                        <div className="truncate border-t border-emerald-100 bg-emerald-50 px-1.5 py-1 text-center text-[11px] font-semibold text-emerald-900">
                            {booth.organization.name}
                        </div>
                    )}
                </>
            ) : (
                <div className="flex flex-1 items-center justify-center pt-4 text-[11px] font-medium uppercase tracking-wide text-slate-400">
                    Available
                </div>
            )}
        </div>
    );
}

export default function FloorPlanTab({ event, floorPlans, organizations }) {
    const [activeFloorPlanId, setActiveFloorPlanId] = useState(floorPlans[0]?.id ?? null);
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [editModalOpen, setEditModalOpen] = useState(false);
    const [removeImage, setRemoveImage] = useState(false);
    const [boothModalOpen, setBoothModalOpen] = useState(false);
    const [editingBoothId, setEditingBoothId] = useState(null);
    const [containerSize, setContainerSize] = useState({ width: 0, height: 0 });
    const containerRef = useRef(null);

    const activeFloorPlan = floorPlans.find((fp) => fp.id === activeFloorPlanId) ?? floorPlans[0] ?? null;

    useEffect(() => {
        if (floorPlans.length > 0 && !floorPlans.some((fp) => fp.id === activeFloorPlanId)) {
            setActiveFloorPlanId(floorPlans[0].id);
        }
    }, [floorPlans, activeFloorPlanId]);

    useEffect(() => {
        const el = containerRef.current;

        if (!el) {
            return;
        }

        const observer = new ResizeObserver((entries) => {
            const entry = entries[0];

            if (entry) {
                setContainerSize({ width: entry.contentRect.width, height: entry.contentRect.height });
            }
        });

        observer.observe(el);

        return () => observer.disconnect();
    }, [activeFloorPlan?.id]);

    const createForm = useForm({ name: '', image: null });
    const editForm = useForm({ name: '', image: null, remove_image: false });
    const boothForm = useForm({
        booth_number: '',
        organization_id: '',
        notes: '',
        ...DEFAULT_BOOTH,
    });
    const { confirm, dialogProps } = useConfirm();

    function openCreateModal() {
        createForm.reset();
        createForm.clearErrors();
        setCreateModalOpen(true);
    }

    function handleCreateImageChange(file) {
        const error = validateFile(file, { extensions: IMAGE_EXTENSIONS, maxSizeBytes: IMAGE_MAX_BYTES });

        if (error) {
            createForm.setError('image', error);
            return;
        }

        createForm.clearErrors('image');
        createForm.setData('image', file);
    }

    function submitCreate(e) {
        e.preventDefault();
        createForm.post(route('floor-plans.store', event.id), {
            onSuccess: (page) => {
                setCreateModalOpen(false);
                const created = page.props.floorPlans.reduce(
                    (mostRecent, fp) => (!mostRecent || fp.id > mostRecent.id ? fp : mostRecent),
                    null,
                );

                if (created) {
                    setActiveFloorPlanId(created.id);
                }
            },
        });
    }

    function openEditModal() {
        editForm.setData({ name: activeFloorPlan.name, image: null, remove_image: false });
        editForm.clearErrors();
        setRemoveImage(false);
        setEditModalOpen(true);
    }

    function handleEditImageChange(file) {
        const error = validateFile(file, { extensions: IMAGE_EXTENSIONS, maxSizeBytes: IMAGE_MAX_BYTES });

        if (error) {
            editForm.setError('image', error);
            return;
        }

        editForm.clearErrors('image');
        setRemoveImage(false);
        editForm.setData((prev) => ({ ...prev, image: file, remove_image: false }));
    }

    function handleRemoveImage() {
        setRemoveImage(true);
        editForm.setData((prev) => ({ ...prev, image: null, remove_image: true }));
    }

    function submitEdit(e) {
        e.preventDefault();
        editForm.put(route('floor-plans.update', activeFloorPlan.id), {
            onSuccess: () => setEditModalOpen(false),
        });
    }

    function destroyFloorPlan() {
        confirm({
            title: 'Delete floor plan',
            message: `Delete the floor plan "${activeFloorPlan.name}"? This also removes its booths.`,
            confirmLabel: 'Delete',
            danger: true,
        }).then((confirmed) => {
            if (confirmed) {
                router.delete(route('floor-plans.destroy', activeFloorPlan.id));
            }
        });
    }

    function openAddBooth() {
        setEditingBoothId(null);
        boothForm.setData({ booth_number: '', organization_id: '', notes: '', ...DEFAULT_BOOTH });
        boothForm.clearErrors();
        setBoothModalOpen(true);
    }

    function openEditBooth(booth) {
        setEditingBoothId(booth.id);
        boothForm.setData({
            booth_number: booth.booth_number,
            organization_id: booth.organization_id ?? '',
            notes: booth.notes ?? '',
            x: booth.x,
            y: booth.y,
            width: booth.width,
            height: booth.height,
        });
        boothForm.clearErrors();
        setBoothModalOpen(true);
    }

    function submitBooth(e) {
        e.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => setBoothModalOpen(false) };

        if (editingBoothId) {
            boothForm.put(route('booths.update', editingBoothId), options);
        } else {
            boothForm.post(route('booths.store', activeFloorPlan.id), options);
        }
    }

    function destroyBooth() {
        confirm({ title: 'Remove booth', message: 'Remove this booth?', confirmLabel: 'Remove', danger: true })
            .then((confirmed) => {
                if (confirmed) {
                    router.delete(route('booths.destroy', editingBoothId), {
                        preserveScroll: true,
                        onSuccess: () => setBoothModalOpen(false),
                    });
                }
            });
    }

    function saveBoothLayout(booth, patch) {
        router.put(
            route('booths.update', booth.id),
            {
                booth_number: booth.booth_number,
                organization_id: booth.organization_id ?? '',
                notes: booth.notes ?? '',
                x: booth.x,
                y: booth.y,
                width: booth.width,
                height: booth.height,
                ...patch,
            },
            { preserveScroll: true, preserveState: true },
        );
    }

    function toPercent(px, total) {
        return total > 0 ? (px / total) * 100 : 0;
    }

    const booths = activeFloorPlan?.booths ?? [];
    const assignedCount = booths.filter((booth) => booth.organization_id).length;

    function loadImage(src) {
        return new Promise((resolve, reject) => {
            const image = new Image();
            image.crossOrigin = 'anonymous';
            image.onload = () => resolve(image);
            image.onerror = reject;
            image.src = src;
        });
    }

    async function downloadFloorPlan() {
        if (!activeFloorPlan?.image_url) {
            return;
        }

        const backgroundImage = await loadImage(activeFloorPlan.image_url);

        const logoUrls = [...new Set(booths.map((booth) => booth.organization?.logo_url).filter(Boolean))];
        const logoImages = {};
        await Promise.all(
            logoUrls.map(async (url) => {
                try {
                    logoImages[url] = await loadImage(url);
                } catch {
                    // Logo failed to load — fall back to text-only for that booth.
                }
            }),
        );

        const canvas = document.createElement('canvas');
        canvas.width = backgroundImage.naturalWidth;
        canvas.height = backgroundImage.naturalHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(backgroundImage, 0, 0);

        booths.forEach((booth) => {
            const x = (booth.x / 100) * canvas.width;
            const y = (booth.y / 100) * canvas.height;
            const w = (booth.width / 100) * canvas.width;
            const h = (booth.height / 100) * canvas.height;
            const assigned = Boolean(booth.organization_id);
            const logoImage = booth.organization?.logo_url ? logoImages[booth.organization.logo_url] : null;

            // Mirrors BoothCard: white card, booth-number pill, logo filling the
            // body, organization name on a tinted strip along the bottom.
            const radius = Math.min(w, h) * 0.08;
            const unit = Math.min(w, h);

            ctx.save();
            ctx.beginPath();
            ctx.roundRect(x, y, w, h, radius);
            ctx.fillStyle = assigned ? '#ffffff' : 'rgba(255, 255, 255, 0.8)';
            ctx.fill();
            ctx.lineWidth = Math.max(2, canvas.width * 0.002);
            ctx.strokeStyle = assigned ? '#10b981' : '#94a3b8';
            ctx.setLineDash(assigned ? [] : [8, 5]);
            ctx.stroke();
            ctx.setLineDash([]);
            ctx.clip();

            const pillFont = Math.max(10, unit * 0.13);
            ctx.font = `bold ${pillFont}px sans-serif`;
            const pillPad = pillFont * 0.35;
            const pillW = ctx.measureText(booth.booth_number).width + pillPad * 2;
            ctx.fillStyle = assigned ? '#059669' : 'rgba(51, 65, 85, 0.85)';
            ctx.beginPath();
            ctx.roundRect(x + pillPad, y + pillPad, pillW, pillFont + pillPad, pillFont * 0.2);
            ctx.fill();
            ctx.fillStyle = '#ffffff';
            ctx.textBaseline = 'top';
            ctx.textAlign = 'left';
            ctx.fillText(booth.booth_number, x + pillPad * 2, y + pillPad * 1.5);

            const bodyTop = y + pillFont + pillPad * 3;

            if (booth.organization) {
                const nameFont = Math.max(9, unit * 0.11);
                const stripH = nameFont + nameFont * 0.8;
                const stripY = y + h - stripH;

                ctx.fillStyle = '#ecfdf5';
                ctx.fillRect(x, stripY, w, stripH);
                ctx.fillStyle = '#064e3b';
                ctx.font = `600 ${nameFont}px sans-serif`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(booth.organization.name, x + w / 2, stripY + stripH / 2, w - nameFont);

                const pad = unit * 0.08;
                const availableW = w - pad * 2;
                const availableH = Math.max(0, stripY - bodyTop - pad);

                if (logoImage && availableH > 0) {
                    const scale = Math.min(availableW / logoImage.naturalWidth, availableH / logoImage.naturalHeight);
                    const drawW = logoImage.naturalWidth * scale;
                    const drawH = logoImage.naturalHeight * scale;
                    ctx.drawImage(logoImage, x + (w - drawW) / 2, bodyTop + (availableH - drawH) / 2, drawW, drawH);
                } else if (availableH > 0) {
                    const r = Math.min(availableW, availableH, unit * 0.5) / 2;
                    ctx.fillStyle = '#d1fae5';
                    ctx.beginPath();
                    ctx.arc(x + w / 2, bodyTop + availableH / 2, r, 0, Math.PI * 2);
                    ctx.fill();
                    ctx.fillStyle = '#047857';
                    ctx.font = `bold ${r * 0.8}px sans-serif`;
                    ctx.fillText(initials(booth.organization.name), x + w / 2, bodyTop + availableH / 2);
                }
            } else {
                ctx.fillStyle = '#94a3b8';
                ctx.font = `600 ${Math.max(9, unit * 0.11)}px sans-serif`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText('AVAILABLE', x + w / 2, (bodyTop + y + h) / 2);
            }

            ctx.restore();
        });

        canvas.toBlob((blob) => {
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `${activeFloorPlan.name.replace(/\s+/g, '-').toLowerCase()}-floor-plan.png`;
            link.click();
            URL.revokeObjectURL(url);
        });
    }

    const createModal = (
        <Modal show={createModalOpen} onClose={() => setCreateModalOpen(false)} maxWidth="md">
            <form onSubmit={submitCreate} className="space-y-4 p-6">
                <h3 className="text-lg font-semibold text-slate-900">Create Floor Plan</h3>

                <div>
                    <InputLabel htmlFor="fp_name" value="Name" />
                    <TextInput
                        id="fp_name"
                        className="mt-1 block w-full"
                        value={createForm.data.name}
                        onChange={(e) => createForm.setData('name', e.target.value)}
                        required
                        autoFocus
                        placeholder="e.g. Hall A"
                    />
                    <InputError className="mt-2" message={createForm.errors.name} />
                </div>

                <div>
                    <InputLabel htmlFor="fp_image" value="Floor Plan Image (optional)" />
                    <div className="mt-1">
                        <FileDropzone
                            id="fp_image"
                            accept="image/*"
                            value={createForm.data.image}
                            onChange={handleCreateImageChange}
                            hint="JPG, PNG, or WEBP, up to 5 MB"
                        />
                    </div>
                    <InputError className="mt-2" message={createForm.errors.image} />
                </div>

                <div className="flex items-center gap-3 pt-2">
                    <PrimaryButton disabled={createForm.processing}>Create</PrimaryButton>
                    <SecondaryButton type="button" onClick={() => setCreateModalOpen(false)}>
                        Cancel
                    </SecondaryButton>
                </div>
            </form>
        </Modal>
    );

    if (floorPlans.length === 0) {
        return (
            <>
                <Card
                    title="Floor Plan"
                    actions={
                        <button
                            type="button"
                            onClick={openCreateModal}
                            className="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500"
                        >
                            Create Floor Plan
                        </button>
                    }
                >
                    <p className="text-sm text-slate-500">
                        No floor plans yet. Create one to upload the venue map and start laying out
                        exhibition booths.
                    </p>
                </Card>

                {createModal}
                <ConfirmDialog {...dialogProps} />
            </>
        );
    }

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-center gap-2 border-b border-slate-200">
                {floorPlans.map((fp) => (
                    <button
                        key={fp.id}
                        type="button"
                        onClick={() => setActiveFloorPlanId(fp.id)}
                        className={`border-b-2 px-3 py-2 text-sm font-medium transition ${
                            activeFloorPlan?.id === fp.id
                                ? 'border-indigo-600 text-indigo-600'
                                : 'border-transparent text-slate-500 hover:text-slate-700'
                        }`}
                    >
                        {fp.name}
                    </button>
                ))}
                <button
                    type="button"
                    onClick={openCreateModal}
                    className="ml-auto rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500"
                >
                    Add Floor Plan
                </button>
            </div>

            {activeFloorPlan && (
                <Card
                    title={activeFloorPlan.name}
                    actions={
                        <div className="flex items-center gap-4 text-sm">
                            <span className="text-slate-500">
                                {assignedCount} of {booths.length} booths assigned
                            </span>
                            <button
                                type="button"
                                onClick={openAddBooth}
                                className="font-medium text-indigo-600 hover:text-indigo-500"
                            >
                                Add Booth
                            </button>
                            {activeFloorPlan.image_url && (
                                <button
                                    type="button"
                                    onClick={downloadFloorPlan}
                                    className="font-medium text-slate-500 hover:text-slate-700"
                                >
                                    Download
                                </button>
                            )}
                            <button
                                type="button"
                                onClick={openEditModal}
                                className="font-medium text-slate-500 hover:text-slate-700"
                            >
                                Edit
                            </button>
                            <button
                                type="button"
                                onClick={destroyFloorPlan}
                                className="font-medium text-rose-600 hover:text-rose-500"
                            >
                                Delete
                            </button>
                        </div>
                    }
                >
                    {activeFloorPlan.image_url ? (
                        <div
                            ref={containerRef}
                            className="relative w-full overflow-hidden rounded-lg border border-slate-200 bg-slate-50"
                        >
                            <img
                                src={activeFloorPlan.image_url}
                                alt={activeFloorPlan.name}
                                className="block w-full select-none"
                                draggable={false}
                            />

                            {containerSize.width > 0 &&
                                booths.map((booth) => (
                                    <Rnd
                                        key={booth.id}
                                        bounds="parent"
                                        position={{
                                            x: (booth.x / 100) * containerSize.width,
                                            y: (booth.y / 100) * containerSize.height,
                                        }}
                                        size={{
                                            width: (booth.width / 100) * containerSize.width,
                                            height: (booth.height / 100) * containerSize.height,
                                        }}
                                        onDragStop={(e, d) =>
                                            saveBoothLayout(booth, {
                                                x: toPercent(d.x, containerSize.width),
                                                y: toPercent(d.y, containerSize.height),
                                            })
                                        }
                                        onResizeStop={(e, direction, ref, delta, position) =>
                                            saveBoothLayout(booth, {
                                                x: toPercent(position.x, containerSize.width),
                                                y: toPercent(position.y, containerSize.height),
                                                width: toPercent(ref.offsetWidth, containerSize.width),
                                                height: toPercent(ref.offsetHeight, containerSize.height),
                                            })
                                        }
                                        className="group cursor-move touch-none"
                                        resizeHandleComponent={{ bottomRight: <ResizeGrip /> }}
                                        resizeHandleStyles={{ bottomRight: RESIZE_GRIP_STYLE }}
                                    >
                                        <BoothCard
                                            booth={booth}
                                            widthPx={(booth.width / 100) * containerSize.width}
                                            heightPx={(booth.height / 100) * containerSize.height}
                                            onEdit={() => openEditBooth(booth)}
                                        />
                                    </Rnd>
                                ))}
                        </div>
                    ) : (
                        <p className="text-sm text-slate-500">
                            This floor plan has no image yet.{' '}
                            <button type="button" onClick={openEditModal} className="font-medium text-indigo-600 hover:text-indigo-500">
                                Upload one
                            </button>{' '}
                            to start placing booths.
                        </p>
                    )}
                </Card>
            )}

            {createModal}

            <Modal show={editModalOpen} onClose={() => setEditModalOpen(false)} maxWidth="md">
                {activeFloorPlan && (
                    <form onSubmit={submitEdit} className="space-y-4 p-6">
                        <h3 className="text-lg font-semibold text-slate-900">Edit Floor Plan</h3>

                        <div>
                            <InputLabel htmlFor="edit_fp_name" value="Name" />
                            <TextInput
                                id="edit_fp_name"
                                className="mt-1 block w-full"
                                value={editForm.data.name}
                                onChange={(e) => editForm.setData('name', e.target.value)}
                                required
                            />
                            <InputError className="mt-2" message={editForm.errors.name} />
                        </div>

                        <div>
                            <InputLabel htmlFor="edit_fp_image" value="Image" />

                            {activeFloorPlan.image_url && !removeImage && !editForm.data.image && (
                                <div className="mt-1 flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                                    <span className="text-sm text-slate-600">Current image uploaded</span>
                                    <button
                                        type="button"
                                        onClick={handleRemoveImage}
                                        className="text-sm font-medium text-rose-600 hover:text-rose-500"
                                    >
                                        Remove Image
                                    </button>
                                </div>
                            )}

                            {(!activeFloorPlan.image_url || removeImage || editForm.data.image) && (
                                <div className="mt-1">
                                    <FileDropzone
                                        id="edit_fp_image"
                                        accept="image/*"
                                        value={editForm.data.image}
                                        onChange={handleEditImageChange}
                                        hint="JPG, PNG, or WEBP, up to 5 MB"
                                    />
                                </div>
                            )}

                            <InputError className="mt-2" message={editForm.errors.image} />
                        </div>

                        <div className="flex items-center gap-3 pt-2">
                            <PrimaryButton disabled={editForm.processing}>Save Changes</PrimaryButton>
                            <SecondaryButton type="button" onClick={() => setEditModalOpen(false)}>
                                Cancel
                            </SecondaryButton>
                        </div>
                    </form>
                )}
            </Modal>

            <Modal show={boothModalOpen} onClose={() => setBoothModalOpen(false)} maxWidth="sm">
                <form onSubmit={submitBooth} className="space-y-4 p-6">
                    <h3 className="text-lg font-semibold text-slate-900">
                        {editingBoothId ? 'Edit Booth' : 'Add Booth'}
                    </h3>

                    <div>
                        <InputLabel htmlFor="booth_number" value="Booth Number" />
                        <TextInput
                            id="booth_number"
                            className="mt-1 block w-full"
                            value={boothForm.data.booth_number}
                            onChange={(e) => boothForm.setData('booth_number', e.target.value)}
                            required
                            autoFocus
                            placeholder="e.g. A1"
                        />
                        <InputError className="mt-2" message={boothForm.errors.booth_number} />
                    </div>

                    <div>
                        <InputLabel htmlFor="booth_organization" value="Assigned Company" />
                        <SelectInput
                            id="booth_organization"
                            className="mt-1 block w-full"
                            value={boothForm.data.organization_id}
                            onChange={(e) => boothForm.setData('organization_id', e.target.value)}
                        >
                            <option value="">Available (unassigned)</option>
                            {organizations.map((organization) => (
                                <option key={organization.id} value={organization.id}>
                                    {organization.name}
                                </option>
                            ))}
                        </SelectInput>
                        <InputError className="mt-2" message={boothForm.errors.organization_id} />
                        {organizations.length === 0 && (
                            <p className="mt-2 text-xs text-slate-400">
                                No participating organizations yet — add some from the Organizations tab
                                first.
                            </p>
                        )}
                    </div>

                    <div>
                        <InputLabel htmlFor="booth_notes" value="Notes (optional)" />
                        <TextareaInput
                            id="booth_notes"
                            rows={2}
                            className="mt-1 block w-full"
                            value={boothForm.data.notes}
                            onChange={(e) => boothForm.setData('notes', e.target.value)}
                            placeholder="Agreed price, contract reference, anything else"
                        />
                        <InputError className="mt-2" message={boothForm.errors.notes} />
                    </div>

                    <p className="text-xs text-slate-400">
                        {editingBoothId
                            ? 'Drag or resize the booth directly on the floor plan to reposition it.'
                            : 'The booth will appear on the floor plan — drag it into place afterward.'}
                    </p>

                    <div className="flex items-center gap-3 pt-2">
                        <PrimaryButton disabled={boothForm.processing}>
                            {editingBoothId ? 'Save Changes' : 'Add Booth'}
                        </PrimaryButton>
                        <SecondaryButton type="button" onClick={() => setBoothModalOpen(false)}>
                            Cancel
                        </SecondaryButton>
                        {editingBoothId && (
                            <button
                                type="button"
                                onClick={destroyBooth}
                                className="ml-auto text-sm font-medium text-rose-600 hover:text-rose-500"
                            >
                                Remove Booth
                            </button>
                        )}
                    </div>
                </form>
            </Modal>

            <ConfirmDialog {...dialogProps} />
        </div>
    );
}
