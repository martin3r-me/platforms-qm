<?php

namespace Platform\Qm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\Uid\UuidV7;

class QmLookupEntry extends Model
{
    use SoftDeletes;

    protected $table = 'qm_lookup_entries';

    protected $fillable = [
        'uuid',
        'qm_lookup_table_id',
        'label',
        'value',
        'description',
        'sort_order',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
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

    public function lookupTable(): BelongsTo
    {
        return $this->belongsTo(QmLookupTable::class, 'qm_lookup_table_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
