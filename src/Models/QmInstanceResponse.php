<?php

namespace Platform\Qm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Symfony\Component\Uid\UuidV7;

class QmInstanceResponse extends Model
{
    protected $table = 'qm_instance_responses';

    protected $fillable = [
        'uuid',
        'qm_instance_id',
        'qm_field_definition_id',
        'qm_section_id',
        'value',
        'is_deviation',
        'notes',
        'responded_by_user_id',
        'responded_at',
    ];

    protected $casts = [
        'value' => 'array',
        'is_deviation' => 'boolean',
        'responded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                do {
                    $uuid = UuidV7::generate();
                } while (self::where('uuid', $uuid)->exists());
                $model->uuid = $uuid;
            }
        });
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(QmInstance::class, 'qm_instance_id');
    }

    public function fieldDefinition(): BelongsTo
    {
        return $this->belongsTo(QmFieldDefinition::class, 'qm_field_definition_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(QmSection::class, 'qm_section_id');
    }

    public function respondedByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'responded_by_user_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(QmAttachment::class, 'attachable');
    }
}
