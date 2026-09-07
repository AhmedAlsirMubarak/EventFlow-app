<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\DocumentCategory;
use App\Enums\DocumentVisibility;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['event_id', 'organization_id', 'title', 'description', 'category', 'visibility', 'acknowledgment_required', 'approval_required', 'created_by', 'current_version_id'])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<DocumentVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->orderByDesc('version_number');
    }

    /**
     * @return BelongsTo<DocumentVersion, $this>
     */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'current_version_id');
    }

    /**
     * @return HasMany<DocumentShare, $this>
     */
    public function shares(): HasMany
    {
        return $this->hasMany(DocumentShare::class);
    }

    /**
     * @return HasMany<DocumentActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(DocumentActivity::class);
    }

    /**
     * @return HasMany<DocumentApproval, $this>
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(DocumentApproval::class)->latest();
    }

    /**
     * The approval record for the current version, if one has been requested.
     */
    public function currentApproval(): ?DocumentApproval
    {
        if (! $this->current_version_id) {
            return null;
        }

        return $this->approvals()->where('document_version_id', $this->current_version_id)->first();
    }

    /**
     * Whether this document may be shared externally: either approval isn't required,
     * or the current version has been approved.
     */
    public function isApprovedForSharing(): bool
    {
        if (! $this->approval_required) {
            return true;
        }

        return $this->currentApproval()?->status === ApprovalStatus::Approved;
    }

    /**
     * Open a pending approval request for the given version.
     */
    public function requestApproval(DocumentVersion $version, ?int $requestedBy): DocumentApproval
    {
        return $this->approvals()->create([
            'document_version_id' => $version->id,
            'requested_by' => $requestedBy,
            'status' => ApprovalStatus::Pending,
        ]);
    }

    /**
     * The share record granting the given contact access to this document, if any
     * (either shared directly with them or with their organization).
     */
    public function shareFor(Contact $contact): ?DocumentShare
    {
        return $this->shares()
            ->where(fn ($query) => $query
                ->where('contact_id', $contact->id)
                ->orWhere('organization_id', $contact->organization_id))
            ->first();
    }

    /**
     * Whether the given contact has acknowledged the current version via the given share.
     */
    public function isAcknowledgedBy(Contact $contact, DocumentShare $share): bool
    {
        return DocumentAcknowledgment::query()
            ->where('document_share_id', $share->id)
            ->where('contact_id', $contact->id)
            ->where('document_version_id', $this->current_version_id)
            ->exists();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => DocumentCategory::class,
            'visibility' => DocumentVisibility::class,
            'acknowledgment_required' => 'boolean',
            'approval_required' => 'boolean',
        ];
    }
}
