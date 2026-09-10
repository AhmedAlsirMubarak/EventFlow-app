<?php

namespace App\Http\Controllers;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthenticationController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user();

        $qrCodeSvg = null;
        if ($user->two_factor_secret && ! $user->two_factor_confirmed_at) {
            $qrCodeSvg = $this->qrCodeSvgFor($user);
        }

        return Inertia::render('Settings/TwoFactorAuthentication', [
            'enabled' => $user->hasTwoFactorEnabled(),
            'pending' => (bool) ($user->two_factor_secret && ! $user->two_factor_confirmed_at),
            'qrCodeSvg' => $qrCodeSvg,
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        $request->user()->forceFill([
            'two_factor_secret' => (new Google2FA)->generateSecretKey(),
            'two_factor_confirmed_at' => null,
        ])->save();

        return to_route('two-factor.show');
    }

    /**
     * @throws ValidationException
     */
    public function confirm(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $user = $request->user();

        if (! $user->two_factor_secret || ! (new Google2FA)->verifyKey($user->two_factor_secret, $request->string('code'))) {
            throw ValidationException::withMessages(['code' => 'That code is incorrect.']);
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return to_route('two-factor.show')->with('status', 'Two-factor authentication enabled.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $request->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return to_route('two-factor.show')->with('status', 'Two-factor authentication disabled.');
    }

    private function qrCodeSvgFor(object $user): string
    {
        $google2fa = new Google2FA;

        $otpAuthUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $user->two_factor_secret,
        );

        $renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd);
        $svg = (new Writer($renderer))->writeString($otpAuthUrl);

        return substr($svg, strpos($svg, '<svg'));
    }
}
