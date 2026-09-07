---
paths:
  - 'app/Models/*.php'
---

# Models

## Every column set via update()/create() must be in #[Fillable]
This has caused two silent bugs so far: `Document::current_version_id` and `Reminder::completed_at`/`notified_at` were omitted from `#[Fillable]`, so `$model->update([...])` silently dropped those keys (mass-assignment protection, no error thrown) — the column just never changed. When adding a new model attribute that gets set via `create()`/`update()` (not just user input — internal/system-set fields like timestamps and computed status fields count too), add it to `#[Fillable]` in the same edit, and write a test that asserts the persisted value, not just a 200/redirect response.
