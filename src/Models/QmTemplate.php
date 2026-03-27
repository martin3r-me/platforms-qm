<?php

namespace Platform\Qm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Platform\ActivityLog\Traits\LogsActivity;
use Symfony\Component\Uid\UuidV7;

class QmTemplate extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'qm_templates';

    protected $fillable = [
        'uuid',
        'team_id',
        'name',
        'description',
        'status',
        'version',
        'settings',
        'i18n',
        'created_by_user_id',
    ];

    protected $casts = [
        'settings' => 'array',
        'i18n' => 'array',
    ];

    public const DEFAULT_SETTINGS = [
        'haccp_enabled' => false,
        'deviation_workflow' => 'simple',
        'escalation_enabled' => false,
        'escalation_levels' => [],
        'require_signature' => false,
        'allow_ad_hoc_fields' => false,
        'auto_close_after_hours' => null,
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
            if (empty($model->settings)) {
                $model->settings = self::DEFAULT_SETTINGS;
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

    public function templateSections(): HasMany
    {
        return $this->hasMany(QmTemplateSection::class, 'qm_template_id')->orderBy('position');
    }

    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(QmSection::class, 'qm_template_sections', 'qm_template_id', 'qm_section_id')
            ->withPivot(['position', 'is_required'])
            ->withTimestamps()
            ->orderByPivot('position');
    }

    public function instances(): HasMany
    {
        return $this->hasMany(QmInstance::class, 'qm_template_id');
    }

    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function getSetting(string $key, $default = null)
    {
        $settings = $this->settings ?? [];
        return $settings[$key] ?? self::DEFAULT_SETTINGS[$key] ?? $default;
    }

    /**
     * Build a snapshot of the full template structure for freezing into an instance.
     */
    public function toSnapshotArray(): array
    {
        $this->loadMissing(['templateSections.section.sectionFields.fieldDefinition.fieldType']);

        $sections = [];
        foreach ($this->templateSections as $ts) {
            $fields = [];
            foreach ($ts->section->sectionFields as $sf) {
                $fields[] = [
                    'field_definition_id' => $sf->qm_field_definition_id,
                    'title' => $sf->fieldDefinition->title,
                    'description' => $sf->fieldDefinition->description,
                    'field_type_key' => $sf->fieldDefinition->fieldType->key,
                    'config' => $sf->fieldDefinition->config,
                    'validation_rules' => $sf->fieldDefinition->validation_rules,
                    'position' => $sf->position,
                    'is_required' => $sf->is_required,
                    'behavior_rule' => $sf->behavior_rule,
                    'behavior_config' => $sf->behavior_config,
                ];
            }

            $sections[] = [
                'section_id' => $ts->qm_section_id,
                'title' => $ts->section->title,
                'description' => $ts->section->description,
                'position' => $ts->position,
                'is_required' => $ts->is_required,
                'fields' => $fields,
            ];
        }

        return [
            'template' => [
                'id' => $this->id,
                'uuid' => $this->uuid,
                'name' => $this->name,
                'version' => $this->version,
                'settings' => $this->settings,
            ],
            'sections' => $sections,
        ];
    }
}
