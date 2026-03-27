<?php

namespace Platform\Qm\Services;

use Platform\Qm\Models\QmTemplate;
use Platform\Qm\Models\QmTemplateSection;

class QmTemplateService
{
    public function create(array $data): QmTemplate
    {
        return QmTemplate::create($data);
    }

    public function update(QmTemplate $template, array $data): QmTemplate
    {
        $fillable = ['name', 'description', 'status', 'version', 'settings', 'i18n'];

        foreach ($fillable as $field) {
            if (array_key_exists($field, $data)) {
                $template->{$field} = $data[$field];
            }
        }

        $template->save();
        return $template;
    }

    public function delete(QmTemplate $template): void
    {
        $instanceCount = $template->instances()->count();
        if ($instanceCount > 0) {
            throw new \RuntimeException("Template hat {$instanceCount} Instanz(en) und kann nicht geloescht werden. Archiviere es stattdessen.");
        }

        $template->delete();
    }

    /**
     * Add a section to a template.
     */
    public function addSection(QmTemplate $template, int $sectionId, array $options = []): QmTemplateSection
    {
        $maxPosition = $template->templateSections()->max('position') ?? -1;

        return $template->templateSections()->create([
            'qm_section_id' => $sectionId,
            'position' => $options['position'] ?? ($maxPosition + 1),
            'is_required' => $options['is_required'] ?? true,
        ]);
    }

    /**
     * Remove a section from a template.
     */
    public function removeSection(QmTemplate $template, int $sectionId): void
    {
        $template->templateSections()
            ->where('qm_section_id', $sectionId)
            ->delete();
    }

    /**
     * Reorder sections in a template.
     */
    public function reorderSections(QmTemplate $template, array $templateSectionIds): void
    {
        foreach ($templateSectionIds as $position => $tsSectionId) {
            $template->templateSections()
                ->where('id', $tsSectionId)
                ->update(['position' => $position]);
        }
    }

    /**
     * Duplicate a template (deep copy sections + fields).
     */
    public function duplicate(QmTemplate $template, string $newName, int $userId): QmTemplate
    {
        $newTemplate = QmTemplate::create([
            'team_id' => $template->team_id,
            'name' => $newName,
            'description' => $template->description,
            'status' => 'draft',
            'version' => '1.0',
            'settings' => $template->settings,
            'i18n' => $template->i18n,
            'created_by_user_id' => $userId,
        ]);

        // Copy template-section assignments
        foreach ($template->templateSections as $ts) {
            $newTemplate->templateSections()->create([
                'qm_section_id' => $ts->qm_section_id,
                'position' => $ts->position,
                'is_required' => $ts->is_required,
            ]);
        }

        return $newTemplate->load('templateSections');
    }

    /**
     * Create a snapshot array from a template for freezing into instances.
     */
    public function createSnapshot(QmTemplate $template): array
    {
        return $template->toSnapshotArray();
    }
}
