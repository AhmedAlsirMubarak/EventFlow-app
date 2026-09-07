---
paths:
  - 'app/Http/Controllers/Document*Controller.php'
  - app/Http/Controllers/EventController.php
---

# Controllers

## Document notifications only reach contacts with portal access
DocumentSharedNotification and DocumentVersionUploadedNotification are only sent to Contact recipients where `hasPortalAccess()` is true (i.e. `user_id` is set). A contact with an email but no linked User account is deliberately skipped — emailing them a "view this document" link they can't log in to would be worse than not emailing at all. The natural workflow is: grant portal access first (ContactController::grantPortalAccess, which itself sends PortalAccessGrantedNotification), then share documents.

## Document approval workflow gates sharing, not viewing
Documents can be flagged `approval_required`. When set, `Document::requestApproval()` opens a Pending `DocumentApproval` for the current version — called from `DocumentController::store()` (initial upload), `DocumentController::update()` (toggled on with no pending approval yet), and `DocumentVersionController::store()` (every new version reopens approval, since content changed). Only Super Administrator/Management may decide (`DocumentPolicy::review()`, `DocumentApprovalController`). `Document::isApprovedForSharing()` is enforced only in `DocumentShareController::store()` (403 until approved) — internal viewing/editing/version history is unaffected, so a Pending or Rejected document still behaves normally everywhere except external sharing.

## Eager-loaded relation names that snake_case to a FK column overwrite it in JSON
Eloquent's array/JSON serialization snake_cases relation keys (`$snakeAttributes`), so a relation like `decidedBy()` or `closedBy()` serializes under the key `decided_by`/`closed_by` — the SAME key as the raw foreign-key column — and overwrites it. When that relation is eager-loaded, the frontend prop is the related object (`{id, name}` or null), not the scalar id; when it isn't loaded, it's the scalar. `DecisionsTab.jsx`'s edit-prefill (`decided_by: decision.decided_by ?? ''`) has this latent bug — it assigns the loaded object into a `<select>` value instead of the id, so editing a decision doesn't pre-select "Decided By". Not fixed here (out of scope); the existing convention that avoids it is picking a relation name whose snake_case form differs from the FK (`assignee` not `assignedTo` for `assigned_to`, `creator` not `createdBy` for `created_by`, `requester`/`reviewer` for `requested_by`/`reviewed_by`) — follow that pattern for new relations unless, like `closedBy`, only the display object is ever needed and the raw id is never read back into a form.
