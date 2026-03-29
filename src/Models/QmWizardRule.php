<?php

namespace Platform\Qm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;

class QmWizardRule extends Model
{
    protected $table = 'qm_wizard_rules';

    protected $fillable = [
        'uuid',
        'qm_template_id',
        'name',
        'rule_type',
        'condition_field',
        'condition_operator',
        'condition_value',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'condition_value' => 'array',
        'is_active' => 'boolean',
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

    public function ruleSections(): HasMany
    {
        return $this->hasMany(QmWizardRuleSection::class, 'qm_wizard_rule_id');
    }
}
