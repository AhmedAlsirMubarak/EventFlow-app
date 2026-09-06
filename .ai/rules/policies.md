---
paths:
  - app/Policies/CommentPolicy.php
---

# Policies

## Comments support dual internal/external actors
Comment authorization branches on `$user->isExternal()`. Internal staff follow the normal `canAccessEvent($user, $commentable->event)` check against every commentable. External Contacts (logged in as an external `User`) may only comment on a `StakeholderRequest` that is their own (`$commentable->contact_id === $user->contact?->id`) — they cannot comment on Ideas/Tasks, which stay internal-only. When storing a comment, `CommentController::storeComment()` forces `visibility = External` and sets `contact_id` (not `user_id`) for external actors; internal actors default to `Internal` unless they explicitly pick `External` (visible to the stakeholder) via the `visibility` field. Any future commentable model open to external actors must be added to this branch explicitly — it is not automatic.
