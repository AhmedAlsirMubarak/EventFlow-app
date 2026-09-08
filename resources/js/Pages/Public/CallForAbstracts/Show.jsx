import ApplicationLogo from '@/Components/ApplicationLogo';
import FileDropzone from '@/Components/FileDropzone';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SelectInput from '@/Components/SelectInput';
import TextareaInput from '@/Components/TextareaInput';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';

const MAX_ATTACHMENT_BYTES = 5 * 1024 * 1024;

export default function Show({ call, isOpen }) {
    const { data, setData, post, processing, errors, setError, clearErrors } = useForm({
        title: '',
        abstract_track_id: '',
        abstract_body: '',
        keywords: '',
        speaker_name: '',
        speaker_email: '',
        speaker_phone: '',
        speaker_bio: '',
        speaker_organization: '',
        file: null,
    });

    function handleFileChange(file) {
        if (!file) {
            clearErrors('file');
            setData('file', null);
            return;
        }

        if (file.type !== 'application/pdf') {
            setError('file', 'The attachment must be a PDF file.');
            return;
        }

        if (file.size > MAX_ATTACHMENT_BYTES) {
            setError('file', 'The attachment must not be larger than 5 MB.');
            return;
        }

        clearErrors('file');
        setData('file', file);
    }

    function submit(e) {
        e.preventDefault();
        post(route('public.abstracts.store', call.public_token));
    }

    return (
        <div className="min-h-screen bg-slate-50 py-10">
            <Head title={`Call for Abstracts — ${call.event.name}`} />

            <div className="mx-auto max-w-2xl px-4">
                <div className="mb-6 flex items-center gap-3">
                    <Link href="/">
                        <ApplicationLogo className="h-10 w-10 fill-current text-indigo-600" />
                    </Link>
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">
                            Call for Abstracts — {call.event.name}
                        </h1>
                    </div>
                </div>

                {call.instructions && (
                    <div className="mb-6 whitespace-pre-line rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-700">
                        {call.instructions}
                    </div>
                )}

                {!isOpen && (
                    <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        This call for abstracts is not currently accepting submissions.
                    </div>
                )}

                {isOpen && (
                    <form
                        onSubmit={submit}
                        encType="multipart/form-data"
                        className="space-y-4 rounded-lg border border-slate-200 bg-white p-6"
                    >
                        <div>
                            <InputLabel htmlFor="title" value="Talk Title" />
                            <TextInput
                                id="title"
                                className="mt-1 block w-full"
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value)}
                                required
                                autoFocus
                            />
                            <InputError className="mt-2" message={errors.title} />
                        </div>

                        {call.tracks.length > 0 && (
                            <div>
                                <InputLabel htmlFor="abstract_track_id" value="Track" />
                                <SelectInput
                                    id="abstract_track_id"
                                    className="mt-1 block w-full"
                                    value={data.abstract_track_id}
                                    onChange={(e) => setData('abstract_track_id', e.target.value)}
                                >
                                    <option value="">Select a track</option>
                                    {call.tracks.map((track) => (
                                        <option key={track.id} value={track.id}>
                                            {track.name}
                                        </option>
                                    ))}
                                </SelectInput>
                                <InputError className="mt-2" message={errors.abstract_track_id} />
                            </div>
                        )}

                        <div>
                            <InputLabel htmlFor="abstract_body" value="Abstract" />
                            <TextareaInput
                                id="abstract_body"
                                rows={6}
                                className="mt-1 block w-full"
                                value={data.abstract_body}
                                onChange={(e) => setData('abstract_body', e.target.value)}
                                required
                            />
                            <InputError className="mt-2" message={errors.abstract_body} />
                        </div>

                        <div>
                            <InputLabel htmlFor="keywords" value="Keywords" />
                            <TextInput
                                id="keywords"
                                className="mt-1 block w-full"
                                placeholder="Comma-separated"
                                value={data.keywords}
                                onChange={(e) => setData('keywords', e.target.value)}
                            />
                        </div>

                        <div className="border-t border-slate-100 pt-4">
                            <h2 className="mb-3 text-sm font-semibold text-slate-900">Speaker Details</h2>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel htmlFor="speaker_name" value="Full Name" />
                                    <TextInput
                                        id="speaker_name"
                                        className="mt-1 block w-full"
                                        value={data.speaker_name}
                                        onChange={(e) => setData('speaker_name', e.target.value)}
                                        required
                                    />
                                    <InputError className="mt-2" message={errors.speaker_name} />
                                </div>
                                <div>
                                    <InputLabel htmlFor="speaker_email" value="Email" />
                                    <TextInput
                                        id="speaker_email"
                                        type="email"
                                        className="mt-1 block w-full"
                                        value={data.speaker_email}
                                        onChange={(e) => setData('speaker_email', e.target.value)}
                                        required
                                    />
                                    <InputError className="mt-2" message={errors.speaker_email} />
                                </div>
                            </div>

                            <div className="mt-4 grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel htmlFor="speaker_phone" value="Phone" />
                                    <TextInput
                                        id="speaker_phone"
                                        className="mt-1 block w-full"
                                        value={data.speaker_phone}
                                        onChange={(e) => setData('speaker_phone', e.target.value)}
                                    />
                                </div>
                                <div>
                                    <InputLabel htmlFor="speaker_organization" value="Organization" />
                                    <TextInput
                                        id="speaker_organization"
                                        className="mt-1 block w-full"
                                        value={data.speaker_organization}
                                        onChange={(e) => setData('speaker_organization', e.target.value)}
                                    />
                                </div>
                            </div>

                            <div className="mt-4">
                                <InputLabel htmlFor="speaker_bio" value="Speaker Bio" />
                                <TextareaInput
                                    id="speaker_bio"
                                    rows={3}
                                    className="mt-1 block w-full"
                                    value={data.speaker_bio}
                                    onChange={(e) => setData('speaker_bio', e.target.value)}
                                />
                            </div>
                        </div>

                        <div>
                            <InputLabel htmlFor="file" value="Attachment (optional — CV, slides, etc.)" />
                            <div className="mt-1">
                                <FileDropzone
                                    id="file"
                                    accept="application/pdf,.pdf"
                                    value={data.file}
                                    onChange={handleFileChange}
                                    hint="PDF only, up to 5 MB"
                                />
                            </div>
                            <InputError className="mt-2" message={errors.file} />
                        </div>

                        <div className="pt-2">
                            <PrimaryButton disabled={processing} className="w-full justify-center">
                                Submit Abstract
                            </PrimaryButton>
                        </div>
                    </form>
                )}
            </div>
        </div>
    );
}
