<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Microsoft Teams Integration

Meetings support a manually pasted video-call link today (Teams, Zoom, Google Meet, or anything else — EventFlow detects the provider from the URL and shows the right badge/icon). No setup is required for that.

Automatically generating a real Teams meeting (so organizers never have to create one in Teams and paste the link back) is a follow-up that needs an Azure AD app registration in your Microsoft 365 tenant:

1. In the [Entra admin center](https://entra.microsoft.com), register a new app (App registrations → New registration). No redirect URI is needed — this uses the application-only (client credentials) flow, not a sign-in flow.
2. Under **API permissions**, add the Microsoft Graph **application** permission `OnlineMeetings.ReadWrite.All`, then have a tenant admin **grant admin consent** for it.
3. Under **Certificates & secrets**, create a new client secret and copy its value immediately (it's only shown once).
4. Note the app's **Application (client) ID** and your **Directory (tenant) ID** from the app's Overview page.
5. Pick an **organizer UPN** — a licensed Microsoft 365 mailbox (e.g. `events@yourcompany.com`) that meetings will be created under, since application permissions require creating the meeting "as" a specific user.
6. Set `MS_TEAMS_TENANT_ID`, `MS_TEAMS_CLIENT_ID`, `MS_TEAMS_CLIENT_SECRET`, and `MS_TEAMS_ORGANIZER_UPN` in `.env` (already scaffolded in `.env.example` and `config('services.microsoft_teams')`, commented out).

Once those four values exist, the code side is a contained addition: a small Graph API client calling `POST /users/{organizer_upn}/onlineMeetings`, dispatched from a queued job on `meetings.store`, writing the returned `joinWebUrl` into the same `meetings.meeting_link` column the manual-entry field already uses — no schema or frontend changes needed at that point.

## Production Checklist

Before deploying EventFlow to a real environment:

- `APP_ENV=production` and `APP_DEBUG=false` — debug mode exposes stack traces and `.env` values.
- `SESSION_SECURE_COOKIE=true` — only send the session cookie over HTTPS.
- Set `SEED_ADMIN_PASSWORD` (or skip seeding the demo Super Administrator entirely) — `DatabaseSeeder` only seeds it in `local`.
- Install `deploy/eventflow-queue-worker.service` as a systemd service (see the comments at the top of that file for the exact steps) — every notification is queued (`ShouldQueue`); a `queue:work` process running manually in a terminal dies the moment that terminal closes, and nothing sends again until someone notices and restarts it by hand.
- Add a cron entry for the scheduler: `* * * * * cd /path/to/eventflow-app && php artisan schedule:run >> /dev/null 2>&1` — this is what actually fires `SendDueReminders` (`routes/console.php`) and the daily backup commands below; without it, nothing scheduled ever runs on its own.

## Backups

- Configured via [`spatie/laravel-backup`](https://spatie.be/docs/laravel-backup) (`config/backup.php`) — backs up **both** the Postgres database and the uploaded files in `storage/app/private` + `storage/app/public` (documents, abstract submissions, avatars, logos, floor plan images). A database-only backup would silently miss every file a user has ever uploaded.
- Scheduled daily via `routes/console.php` (`backup:run` + `backup:clean`, same mechanism as `SendDueReminders`) — requires the cron entry above to actually run.
- Destination: local disk, outside the project directory (`BACKUP_ARCHIVE_PATH` in `.env`, defaulting to a sibling `../eventflow-backups` folder) — deliberately not inside `storage/`, so losing the project directory doesn't also destroy its backups.
- Retention: the last 7 daily backups are kept (`config/backup.php`'s `cleanup` strategy); older ones are pruned automatically by `backup:clean`.
- **An untested backup is not a backup.** Periodically verify you can actually restore one: `php artisan backup:run`, unzip the newest archive, restore the SQL dump into a scratch database (`pg_restore`/`psql`), and confirm the `storage/` folders extracted alongside it are intact.
- Moving to off-machine storage later (e.g. S3, Backblaze B2) is a `config/backup.php` disk change, not a rewrite — `spatie/laravel-backup` supports it natively.

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
