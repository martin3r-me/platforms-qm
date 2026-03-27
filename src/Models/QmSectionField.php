<?php

namespace Platform\Qm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;

class QmSectionField extends Model
{
    protected $table = 'qm_section_fields';

    protected $fillable = [
        'uuid',
        'qm_section_id',
        'qm_field_definition_id',
        'position',
        'is_required',
        'behavior_rule',
        'behavior_config',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'behavior_config' => 'array',
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

    public function section(): BelongsTo
    {
        return $this->belongsTo(QmSection::class, 'qm_section_id');
    }

    public function fieldDefinition(): BelongsTo
    {
        return $this->belongsTo(QmFieldDefinition::class, 'qm_field_definition_id');
    }
}
