---
paths:
  - 'app/Models/*.php'
---

# Models

## Every column set via update()/create() must be in #[Fillable]
This has caused two silent bugs so far: `Document::current_version_id` and `Reminder::completed_at`/`notified_at` were omitted from `#[Fillable]`, so `$model->update([...])` silently dropped those keys (mass-assignment protection, no error thrown) — the column just never changed. When adding a new model attribute that gets set via `create()`/`update()` (not just user input — internal/system-set fields like timestamps and computed status fields count too), add it to `#[Fillable]` in the same edit, and write a test that asserts the persisted value, not just a 200/redirect response.

## Set $table explicitly for compound-word model names
Eloquent's naive pluralization on a compound class name doesn't always match the natural English plural used in the migration — `LessonLearned` guesses table `lesson_learneds`, not `lessons_learned` (hit this once: `SQLSTATE... no such table: lesson_learneds`). Whenever a model's class name is a multi-word compound, set `protected $table = '...'` explicitly to match the migration's `Schema::create()` name rather than trusting the guess, and verify with a real query (a feature test that persists and re-reads a row) — a passing `php artisan migrate` alone doesn't catch this.
