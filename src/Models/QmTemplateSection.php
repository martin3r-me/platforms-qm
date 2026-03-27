<?php

namespace Platform\Qm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;

class QmTemplateSection extends Model
{
    protected $table = 'qm_template_sections';

    protected $fillable = [
        'uuid',
        'qm_template_id',
        'qm_section_id',
        'position',
        'is_required',
    ];

    protected $casts = [
        'is_required' => 'boolean',
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

    public function template(): BelongsTo
    {
        return $this->belongsTo(QmTemplate::class, 'qm_template_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(QmSection::class, 'qm_section_id');
    }
}
