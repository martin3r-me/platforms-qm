<?php

namespace Platform\Qm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;

class QmWizardField extends Model
{
    protected $table = 'qm_wizard_fields';

    protected $fillable = [
        'uuid',
        'qm_template_id',
        'technical_name',
        'label',
        'input_type',
        'qm_lookup_table_id',
        'sort_order',
        'is_required',
        'description',
        'config',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'config' => 'array',
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

    public function lookupTable(): BelongsTo
    {
        return $this->belongsTo(QmLookupTable::class, 'qm_lookup_table_id');
    }
}
