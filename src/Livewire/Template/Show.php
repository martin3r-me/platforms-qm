<?php

namespace Platform\Qm\Livewire\Template;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Qm\Models\QmTemplate;

class Show extends Component
{
    public QmTemplate $template;
    public string $activePhase = '';
    public ?int $expandedSection = null;

    public function mount(QmTemplate $template)
    {
        $teamId = Auth::user()?->current_team_id;

        if ((int) $template->team_id !== $teamId) {
            abort(403);
        }

        $this->template = $template;
    }

    public function setPhase(string $phase): void
    {
        $this->activePhase = $phase;
        $this->expandedSection = null;
    }

    public function toggleSection(int $sectionId): void
    {
        $this->expandedSection = $this->expandedSection === $sectionId ? null : $sectionId;
    }

    public function render()
    {
        $this->template->load([
            'createdByUser',
            'templateSections.section.sectionFields.fieldDefinition.fieldType',
            'wizardFields',
            'wizardRules',
        ]);
        $this->template->loadCount('instances');

        // Group sections by phase_label
        $phases = [];
        $totalFields = 0;
        $requiredSections = 0;

        foreach ($this->template->templateSections as $ts) {
            $phase = $ts->phase_label ?: 'Allgemein';
            if (!isset($phases[$phase])) {
                $phases[$phase] = [
                    'label' => $phase,
                    'sections' => [],
                    'field_count' => 0,
                ];
            }
            $fieldCount = $ts->section->sectionFields->count();
            $phases[$phase]['sections'][] = $ts;
            $phases[$phase]['field_count'] += $fieldCount;
            $totalFields += $fieldCount;
            if ($ts->is_required) {
                $requiredSections++;
            }
        }

        // Set initial active phase
        if (empty($this->activePhase) && !empty($phases)) {
            $this->activePhase = array_key_first($phases);
        }

        return view('qm::livewire.template.show', [
            'phases' => $phases,
            'totalFields' => $totalFields,
            'requiredSections' => $requiredSections,
        ])->layout('platform::layouts.app');
    }
}
