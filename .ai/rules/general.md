---
paths:
  - '.env*'
---

# General

## Local Postgres runs on port 5433, not 5432
This machine has a standalone Windows service "postgresql-x64-18" already bound to port 5432 with unknown credentials. Local dev uses Laragon's bundled Postgres 18 instead, started manually on port 5433 via:
`pg_ctl.exe start -D "/c/laragon/data/postgresql" -o "-p 5433" -l "/c/laragon/data/postgresql/laragon_pg.log" -w` (from `/c/laragon/bin/postgresql/postgresql/bin`).
DB role/db: `eventflow` / `eventflow_dev_pw`, database `eventflow_app`. If migrations fail with a connection error, check whether this Postgres instance is still running (`pg_ctl status`) and restart it with the command above — do not change DB_PORT back to 5432 or guess credentials for the other service.
