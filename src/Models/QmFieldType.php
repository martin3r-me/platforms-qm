<?php

namespace Platform\Qm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Platform\ActivityLog\Traits\LogsActivity;
use Symfony\Component\Uid\UuidV7;

class QmFieldType extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'qm_field_types';

    protected $fillable = [
        'uuid',
        'key',
        'label',
        'description',
        'is_system',
        'team_id',
        'default_config',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'default_config' => 'array',
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

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class, 'team_id');
    }

    public function fieldDefinitions(): HasMany
    {
        return $this->hasMany(QmFieldDefinition::class, 'qm_field_type_id');
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    public function scopeForTeam($query, int $teamId)
    {
        return $query->where(function ($q) use ($teamId) {
            $q->where('is_system', true)
              ->orWhere('team_id', $teamId);
        });
    }
}
