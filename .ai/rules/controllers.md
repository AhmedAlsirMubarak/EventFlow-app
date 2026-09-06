---
paths:
  - 'app/Http/Controllers/Document*Controller.php'
---

# Controllers

## Document notifications only reach contacts with portal access
DocumentSharedNotification and DocumentVersionUploadedNotification are only sent to Contact recipients where `hasPortalAccess()` is true (i.e. `user_id` is set). A contact with an email but no linked User account is deliberately skipped — emailing them a "view this document" link they can't log in to would be worse than not emailing at all. The natural workflow is: grant portal access first (ContactController::grantPortalAccess, which itself sends PortalAccessGrantedNotification), then share documents.
