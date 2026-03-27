<?php

namespace Platform\Qm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Platform\ActivityLog\Traits\LogsActivity;
use Symfony\Component\Uid\UuidV7;

class QmSection extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'qm_sections';

    protected $fillable = [
        'uuid',
        'team_id',
        'title',
        'description',
        'i18n',
        'created_by_user_id',
    ];

    protected $casts = [
        'i18n' => 'array',
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

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'created_by_user_id');
    }

    public function sectionFields(): HasMany
    {
        return $this->hasMany(QmSectionField::class, 'qm_section_id')->orderBy('position');
    }

    public function templates(): BelongsToMany
    {
        return $this->belongsToMany(QmTemplate::class, 'qm_template_sections', 'qm_section_id', 'qm_template_id')
            ->withPivot(['position', 'is_required'])
            ->withTimestamps();
    }

    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }
}
