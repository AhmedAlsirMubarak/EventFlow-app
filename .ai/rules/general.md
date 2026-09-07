---
paths:
  - '.env*'
---

# General

## Local Postgres runs on port 5433, not 5432
This machine has a standalone Windows service "postgresql-x64-18" already bound to port 5432 with unknown credentials. Local dev uses Laragon's bundled Postgres 18 instead, started manually on port 5433 via:
`pg_ctl.exe start -D "/c/laragon/data/postgresql" -o "-p 5433" -l "/c/laragon/data/postgresql/laragon_pg.log" -w` (from `/c/laragon/bin/postgresql/postgresql/bin`).
DB role/db: `eventflow` / `eventflow_dev_pw`, database `eventflow_app`. If migrations fail with a connection error, check whether this Postgres instance is still running (`pg_ctl status`) and restart it with the command above — do not change DB_PORT back to 5432 or guess credentials for the other service.

## Mail encryption is set via MAIL_SCHEME, not MAIL_ENCRYPTION
This Laravel 13 skeleton's config/mail.php smtp mailer has no `encryption` key — it only reads `MAIL_SCHEME`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`. Setting `MAIL_ENCRYPTION=tls` does nothing. For Microsoft 365/Outlook SMTP (smtp.office365.com:587, STARTTLS), set `MAIL_MAILER=smtp` and `MAIL_SCHEME=smtp` (leave MAIL_SCHEME unset/null only for a plain non-TLS relay like the local Mailpit/dev catcher on port 2525). The mailbox also needs SMTP AUTH enabled by a tenant admin, and an app password if MFA is on — plain account passwords are rejected otherwise.
