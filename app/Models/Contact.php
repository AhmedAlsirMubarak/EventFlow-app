<?php

namespace App\Models;

use App\Enums\PreferredChannel;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

#[Fillable(['organization_id', 'user_id', 'full_name', 'job_title', 'department', 'email', 'mobile', 'preferred_channel'])]
class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use HasFactory, Notifiable;

    /**
     * Route notifications for the mail channel.
     */
    public function routeNotificationForMail(): ?string
    {
        return $this->email;
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Whether this contact can log in to the stakeholder portal.
     */
    public function hasPortalAccess(): bool
    {
        return $this->user_id !== null;
    }

    /**
     * @return HasMany<DocumentShare, $this>
     */
    public function directDocumentShares(): HasMany
    {
        return $this->hasMany(DocumentShare::class);
    }

    /**
     * Documents shared directly with this contact or with their organization.
     *
     * @return Collection<int, DocumentShare>
     */
    public function accessibleDocumentShares(): Collection
    {
        return DocumentShare::query()
            ->where(fn ($query) => $query
                ->where('contact_id', $this->id)
                ->orWhere('organization_id', $this->organization_id))
            ->with(['document.currentVersion', 'document.event'])
            ->get();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'preferred_channel' => PreferredChannel::class,
        ];
    }
}
