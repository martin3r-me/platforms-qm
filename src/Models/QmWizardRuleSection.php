<?php

namespace Platform\Qm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QmWizardRuleSection extends Model
{
    protected $table = 'qm_wizard_rule_sections';

    protected $fillable = [
        'qm_wizard_rule_id',
        'qm_template_section_id',
        'effect',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(QmWizardRule::class, 'qm_wizard_rule_id');
    }

    public function templateSection(): BelongsTo
    {
        return $this->belongsTo(QmTemplateSection::class, 'qm_template_section_id');
    }
}
