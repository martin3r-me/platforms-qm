<?php

namespace Platform\Qm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Symfony\Component\Uid\UuidV7;

class QmAttachment extends Model
{
    protected $table = 'qm_attachments';

    protected $fillable = [
        'uuid',
        'attachable_type',
        'attachable_id',
        'type',
        'filename',
        'original_filename',
        'mime_type',
        'size',
        'disk',
        'path',
        'metadata',
        'uploaded_by_user_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'size' => 'integer',
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

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedByUser()
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'uploaded_by_user_id');
    }
}
