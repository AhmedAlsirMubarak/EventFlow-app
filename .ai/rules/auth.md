---
paths:
  - 'app/Http/Controllers/Auth/*.php'
---

# Auth

## Internal-account 2FA is a post-login logout+redirect, not a guard change
`AuthenticatedSessionController::store()` still calls `$request->authenticate()` (full `Auth::attempt()`, keeping `LoginRequest`'s existing rate-limiting untouched) — but if the freshly-authenticated user `isInternal() && hasTwoFactorEnabled()`, it immediately `Auth::guard('web')->logout()`s again, stashes `session(['2fa.user_id' => $user->id])`, and redirects to `two-factor.login` instead of completing the session. `TwoFactorChallengeController::store()` is what actually calls `Auth::login()`, after verifying the TOTP code via `PragmaRX\Google2FA\Google2FA::verifyKey()`. External accounts never hit this branch — 2FA is internal-only, gated at the `isInternal()` check, not at the model level (`two_factor_secret` could theoretically be set on an external account but is simply never checked at their login path).
