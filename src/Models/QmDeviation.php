<?php

namespace Platform\Qm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Platform\ActivityLog\Traits\LogsActivity;
use Symfony\Component\Uid\UuidV7;

class QmDeviation extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'qm_deviations';

    protected $fillable = [
        'uuid',
        'qm_instance_id',
        'qm_instance_response_id',
        'title',
        'description',
        'severity',
        'status',
        'workflow_type',
        'corrective_action',
        'resolved_at',
        'resolved_by_user_id',
        'root_cause',
        'preventive_action',
        'acknowledged_at',
        'acknowledged_by_user_id',
        'verified_at',
        'verified_by_user_id',
        'escalation_level',
        'escalated_at',
        'created_by_user_id',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'verified_at' => 'datetime',
        'escalated_at' => 'datetime',
        'escalation_level' => 'integer',
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

    public function response(): BelongsTo
    {
        return $this->belongsTo(QmInstanceResponse::class, 'qm_instance_response_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'created_by_user_id');
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'resolved_by_user_id');
    }

    public function acknowledgedByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'acknowledged_by_user_id');
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'verified_by_user_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(QmAttachment::class, 'attachable');
    }

    public function scopeForInstance($query, int $instanceId)
    {
        return $query->where('qm_instance_id', $instanceId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', ['resolved', 'verified']);
    }
}
