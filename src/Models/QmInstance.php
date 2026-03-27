<?php

namespace Platform\Qm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Platform\ActivityLog\Traits\LogsActivity;
use Symfony\Component\Uid\UuidV7;

class QmInstance extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'qm_instances';

    protected $fillable = [
        'uuid',
        'team_id',
        'qm_template_id',
        'title',
        'description',
        'status',
        'snapshot_data',
        'public_token',
        'score',
        'parent_instance_id',
        'recurrence_config',
        'due_at',
        'completed_at',
        'created_by_user_id',
        'completed_by_user_id',
    ];

    protected $casts = [
        'snapshot_data' => 'array',
        'recurrence_config' => 'array',
        'score' => 'decimal:2',
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
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

    public function template(): BelongsTo
    {
        return $this->belongsTo(QmTemplate::class, 'qm_template_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'created_by_user_id');
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'completed_by_user_id');
    }

    public function parentInstance(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_instance_id');
    }

    public function childInstances(): HasMany
    {
        return $this->hasMany(self::class, 'parent_instance_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(QmInstanceResponse::class, 'qm_instance_id');
    }

    public function deviations(): HasMany
    {
        return $this->hasMany(QmDeviation::class, 'qm_instance_id');
    }

    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOverdue($query)
    {
        return $query->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->whereNotIn('status', ['completed', 'cancelled']);
    }
}
