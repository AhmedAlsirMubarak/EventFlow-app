---
paths:
  - 'app/Enums/**'
---

# Enums

## Canonical role enum is App\Enums\Role, not RoleName
The 12 platform roles (Super Administrator, Management, Event Director, Project Manager, Account Manager, Operations Team, Marketing Team, Registration Team, Sales / Sponsorship Team, Finance Team, General Staff, External Stakeholder) live in `App\Enums\Role` (string-backed, values match spatie/laravel-permission role names exactly). `Database\Seeders\RoleSeeder` creates one `Spatie\Permission\Models\Role` per case via `Role::findOrCreate()`. Do not recreate this as `RoleName` or similar — a duplicate was created and removed once already during a multi-session collision on this repo.
