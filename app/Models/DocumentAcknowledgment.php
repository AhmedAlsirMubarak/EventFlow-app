<?php

namespace App\Models;

use Database\Factories\DocumentAcknowledgmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['document_share_id', 'contact_id', 'document_version_id', 'name', 'organization_snapshot', 'statement', 'acknowledged_at'])]
class DocumentAcknowledgment extends Model
{
    /** @use HasFactory<DocumentAcknowledgmentFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<DocumentShare, $this>
     */
    public function share(): BelongsTo
    {
        return $this->belongsTo(DocumentShare::class, 'document_share_id');
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @return BelongsTo<DocumentVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'document_version_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
        ];
    }
}
