---
paths:
  - 'app/Models/*.php'
  - app/Models/Event.php
---

# Models

## Every column set via update()/create() must be in #[Fillable]
This has caused two silent bugs so far: `Document::current_version_id` and `Reminder::completed_at`/`notified_at` were omitted from `#[Fillable]`, so `$model->update([...])` silently dropped those keys (mass-assignment protection, no error thrown) — the column just never changed. When adding a new model attribute that gets set via `create()`/`update()` (not just user input — internal/system-set fields like timestamps and computed status fields count too), add it to `#[Fillable]` in the same edit, and write a test that asserts the persisted value, not just a 200/redirect response.

## Set $table explicitly for compound-word model names
Eloquent's naive pluralization on a compound class name doesn't always match the natural English plural used in the migration — `LessonLearned` guesses table `lesson_learneds`, not `lessons_learned` (hit this once: `SQLSTATE... no such table: lesson_learneds`). Whenever a model's class name is a multi-word compound, set `protected $table = '...'` explicitly to match the migration's `Schema::create()` name rather than trusting the guess, and verify with a real query (a feature test that persists and re-reads a row) — a passing `php artisan migrate` alone doesn't catch this.

## Event logos use the public disk, unlike every other upload
Every other file upload in this app (documents, abstract submissions, CSV imports) goes on the private `local` disk behind an authenticated controller route — deliberately, since they're confidential business content. Event logos are the one exception: they're non-sensitive branding shown repeatedly in `<img>` tags (events index thumbnail, create/edit forms), so they're stored on the `public` disk (`Storage::disk('public')`) and served as static files via the `storage:link` symlink (`public/storage` -> `storage/app/public`), exposed through `Event::logoUrl()` (an `Attribute` accessor, auto-included via `protected $appends = ['logo_url']`). If a fresh environment is missing `public/storage`, run `php artisan storage:link` — it won't exist after a plain `git clone` since symlinks aren't part of version control. Follow this same public-disk pattern for any future non-sensitive branding/avatar image, not the private-disk-plus-route pattern used everywhere else.
