---
paths:
  - 'app/Http/Controllers/Document*Controller.php'
---

# Controllers

## Document notifications only reach contacts with portal access
DocumentSharedNotification and DocumentVersionUploadedNotification are only sent to Contact recipients where `hasPortalAccess()` is true (i.e. `user_id` is set). A contact with an email but no linked User account is deliberately skipped — emailing them a "view this document" link they can't log in to would be worse than not emailing at all. The natural workflow is: grant portal access first (ContactController::grantPortalAccess, which itself sends PortalAccessGrantedNotification), then share documents.

## Document approval workflow gates sharing, not viewing
Documents can be flagged `approval_required`. When set, `Document::requestApproval()` opens a Pending `DocumentApproval` for the current version — called from `DocumentController::store()` (initial upload), `DocumentController::update()` (toggled on with no pending approval yet), and `DocumentVersionController::store()` (every new version reopens approval, since content changed). Only Super Administrator/Management may decide (`DocumentPolicy::review()`, `DocumentApprovalController`). `Document::isApprovedForSharing()` is enforced only in `DocumentShareController::store()` (403 until approved) — internal viewing/editing/version history is unaffected, so a Pending or Rejected document still behaves normally everywhere except external sharing.
