---
paths:
  - 'resources/js/**'
---

# Js

## datetime-local inputs need explicit UTC↔local conversion
An `<input type="datetime-local">`'s value is naive local wall-clock time with no timezone offset — but the backend stores/returns UTC (APP_TIMEZONE=UTC) and JS's `new Date(isoString).toISOString().slice(0,16)` does NOT convert UTC to local, it just truncates the UTC string. Pre-filling an edit form that way, and sending the raw datetime-local string back as-is, silently shifts every date/time by the browser's UTC offset — confirmed as a real bug in `AbstractsTab.jsx` (call opens_at/closes_at), and the identical pattern was copy-pasted into `RemindersTab.jsx` (remind_at) and `MeetingsTab.jsx` (held_at) before being fixed in the same pass. The fix, now applied in all three: convert UTC→local with `toDatetimeLocal()` (offset by `date.getTimezoneOffset()`) when populating the input, and local→UTC with `fromDatetimeLocal()` (`new Date(localString).toISOString()`) via `form.transform()` right before `post()`/`put()` (called as two separate statements — `form.transform(cb)` then `form.post(...)`, never chained, since `transform()` returns undefined). Plain `type="date"` inputs (day-only, no time-of-day) are NOT affected — date-only strings are treated as UTC by both JS and PHP, so they round-trip correctly with no conversion needed. Any *new* datetime-local field must use these two helpers, not a raw `.slice(0, 16)`.
