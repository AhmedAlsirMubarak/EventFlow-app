import Card from '@/Components/UI/Card';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';

export default function TwoFactorAuthentication({ enabled, pending, qrCodeSvg, status }) {
    const enableForm = useForm({});
    const confirmForm = useForm({ code: '' });
    const disableForm = useForm({ password: '' });

    function enable(e) {
        e.preventDefault();
        enableForm.post(route('two-factor.enable'));
    }

    function confirm(e) {
        e.preventDefault();
        confirmForm.post(route('two-factor.confirm'), {
            onSuccess: () => confirmForm.reset(),
        });
    }

    function disable(e) {
        e.preventDefault();
        disableForm.delete(route('two-factor.destroy'), {
            onSuccess: () => disableForm.reset(),
        });
    }

    return (
        <AuthenticatedLayout header="Two-Factor Authentication">
            <Head title="Two-Factor Authentication" />

            <div className="mx-auto max-w-xl">
                <Card>
                    {status && (
                        <div className="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                            {status}
                        </div>
                    )}

                    {enabled && (
                        <div className="space-y-4">
                            <p className="text-sm text-slate-600">
                                Two-factor authentication is currently{' '}
                                <span className="font-semibold text-emerald-600">enabled</span>. You'll
                                be asked for a code from your authenticator app every time you sign in.
                            </p>

                            <form onSubmit={disable} className="space-y-3 border-t border-slate-100 pt-4">
                                <InputLabel htmlFor="password" value="Confirm your password to disable" />
                                <TextInput
                                    id="password"
                                    type="password"
                                    className="block w-full"
                                    value={disableForm.data.password}
                                    onChange={(e) => disableForm.setData('password', e.target.value)}
                                />
                                <InputError message={disableForm.errors.password} />
                                <SecondaryButton
                                    disabled={disableForm.processing}
                                    className="!text-sm !normal-case !tracking-normal !text-rose-600"
                                >
                                    Disable Two-Factor Authentication
                                </SecondaryButton>
                            </form>
                        </div>
                    )}

                    {!enabled && !pending && (
                        <div className="space-y-4">
                            <p className="text-sm text-slate-600">
                                Two-factor authentication is currently disabled. Enable it to require a
                                code from an authenticator app (like Google Authenticator or Authy) every
                                time you sign in.
                            </p>
                            <form onSubmit={enable}>
                                <PrimaryButton
                                    disabled={enableForm.processing}
                                    className="!bg-indigo-600 !text-sm !normal-case !tracking-normal hover:!bg-indigo-500 focus:!bg-indigo-500 active:!bg-indigo-700"
                                >
                                    Enable Two-Factor Authentication
                                </PrimaryButton>
                            </form>
                        </div>
                    )}

                    {!enabled && pending && (
                        <div className="space-y-4">
                            <p className="text-sm text-slate-600">
                                Scan this QR code with your authenticator app, then enter the 6-digit
                                code it shows to finish enabling two-factor authentication.
                            </p>

                            {qrCodeSvg && (
                                <div
                                    className="w-fit rounded-lg border border-slate-200 p-3"
                                    dangerouslySetInnerHTML={{ __html: qrCodeSvg }}
                                />
                            )}

                            <form onSubmit={confirm} className="space-y-3">
                                <InputLabel htmlFor="code" value="6-Digit Code" />
                                <TextInput
                                    id="code"
                                    inputMode="numeric"
                                    maxLength={6}
                                    className="block w-full tracking-widest"
                                    value={confirmForm.data.code}
                                    onChange={(e) => confirmForm.setData('code', e.target.value)}
                                    autoFocus
                                />
                                <InputError message={confirmForm.errors.code} />
                                <PrimaryButton
                                    disabled={confirmForm.processing}
                                    className="!bg-indigo-600 !text-sm !normal-case !tracking-normal hover:!bg-indigo-500 focus:!bg-indigo-500 active:!bg-indigo-700"
                                >
                                    Confirm & Enable
                                </PrimaryButton>
                            </form>
                        </div>
                    )}
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
