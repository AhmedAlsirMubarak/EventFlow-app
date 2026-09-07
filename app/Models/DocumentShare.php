<?php

namespace App\Models;

use Database\Factories\DocumentShareFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable(['document_id', 'organization_id', 'contact_id', 'shared_by'])]
class DocumentShare extends Model
{
    /** @use HasFactory<DocumentShareFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sharer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    /**
     * @return HasMany<DocumentActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(DocumentActivity::class);
    }

    /**
     * @return HasMany<DocumentAcknowledgment, $this>
     */
    public function acknowledgments(): HasMany
    {
        return $this->hasMany(DocumentAcknowledgment::class);
    }

    /**
     * Contacts who can access this share: the specific contact, or every
     * contact belonging to the shared organization.
     *
     * @return Collection<int, Contact>
     */
    public function recipientContacts(): Collection
    {
        if ($this->contact_id) {
            return collect([$this->contact])->filter();
        }

        return $this->organization?->contacts ?? collect();
    }
}
