<?php

namespace Platform\Qm\Livewire\Wizard;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Qm\Models\QmTemplate;
use Platform\Qm\Models\QmInstance;
use Platform\Qm\Services\QmWizardService;

class Create extends Component
{
    public QmTemplate $template;
    public int $step = 1;
    public array $answers = [];
    public string $instanceTitle = '';
    public ?array $evaluation = null;
    public ?int $createdInstanceId = null;

    public function mount(QmTemplate $template): void
    {
        $teamId = Auth::user()?->current_team_id;

        if ((int) $template->team_id !== $teamId) {
            abort(403);
        }

        $this->template = $template;
        $this->instanceTitle = $template->name . ' - ' . now()->format('d.m.Y');

        // Pre-initialize answer keys so Livewire can bind properly
        $service = new QmWizardService();
        $config = $service->getWizardConfig($template);
        foreach ($config['fields'] as $field) {
            if ($field['input_type'] === 'multi_select') {
                $this->answers[$field['technical_name']] = [];
            } else {
                $this->answers[$field['technical_name']] = '';
            }
        }
    }

    public function updatedAnswers(): void
    {
        $service = new QmWizardService();
        $this->evaluation = $service->evaluateWizard($this->template, $this->answers);
    }

    public function goToPreview(): void
    {
        $service = new QmWizardService();
        $config = $service->getWizardConfig($this->template);

        // Validate required fields
        $this->resetErrorBag();
        $hasErrors = false;

        foreach ($config['fields'] as $field) {
            if ($field['is_required']) {
                $value = $this->answers[$field['technical_name']] ?? null;
                $isEmpty = $value === null || $value === '' || $value === [];
                if ($isEmpty) {
                    $this->addError('answers.' . $field['technical_name'], 'Dieses Feld ist erforderlich.');
                    $hasErrors = true;
                }
            }
        }

        if ($hasErrors) {
            return;
        }

        $this->evaluation = $service->evaluateWizard($this->template, $this->answers);
        $this->step = 2;
    }

    public function goBack(): void
    {
        $this->step = 1;
    }

    public function createInstance(): void
    {
        $service = new QmWizardService();
        $user = Auth::user();

        $instance = $service->createInstanceFromWizard($this->template, $this->answers, [
            'team_id' => $user->current_team_id,
            'title' => $this->instanceTitle ?: ($this->template->name . ' - ' . now()->format('d.m.Y H:i')),
            'created_by_user_id' => $user->id,
        ]);

        $this->createdInstanceId = $instance->id;
        $this->step = 3;
    }

    public function render()
    {
        $service = new QmWizardService();
        $config = $service->getWizardConfig($this->template);

        // Group active sections by phase_label for preview
        $phaseGroups = [];
        if ($this->evaluation) {
            foreach ($this->evaluation['active_sections'] ?? [] as $section) {
                $phase = $section['phase_label'] ?: 'Allgemein';
                $phaseGroups[$phase][] = $section;
            }
        }

        $createdInstance = $this->createdInstanceId
            ? QmInstance::find($this->createdInstanceId)
            : null;

        return view('qm::livewire.wizard.create', [
            'config' => $config,
            'phaseGroups' => $phaseGroups,
            'createdInstance' => $createdInstance,
        ])->layout('platform::layouts.app');
    }
}
