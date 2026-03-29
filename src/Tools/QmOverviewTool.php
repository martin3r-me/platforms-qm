<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class QmOverviewTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'qm.overview.GET';
    }

    public function getDescription(): string
    {
        return 'GET /qm/overview - Zeigt Uebersicht ueber das QM-Modul (Quality Management Checklisten, Konzepte, Feldtypen, verfuegbare Tools).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => new \stdClass(),
            'required' => [],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            return ToolResult::success([
                'module' => 'qm',
                'title' => 'Quality Management Checklisten',
                'scope' => [
                    'team_scoped' => true,
                    'team_id_source' => 'ToolContext.team bzw. team_id Parameter',
                ],
                'concepts' => [
                    'qm_field_types' => [
                        'table' => 'qm_field_types',
                        'note' => 'Feldtypen (System + Custom). 17 System-Typen geseeded. Teams koennen eigene erstellen.',
                        'system_types' => ['text', 'number', 'textarea', 'boolean', 'yes_no', 'select', 'multi_select', 'measurement', 'temperature', 'photo', 'signature', 'date', 'datetime', 'file', 'barcode_qr', 'person', 'location'],
                    ],
                    'qm_field_definitions' => [
                        'table' => 'qm_field_definitions',
                        'note' => 'Konfigurierte Felder mit Titel, Beschreibung, i18n, Validierungsregeln. Basieren auf einem FieldType.',
                    ],
                    'qm_sections' => [
                        'table' => 'qm_sections',
                        'note' => 'Logische Gruppen von Feldern (z.B. "Temperaturkontrolle", "Hygiene"). Enthaelt SectionFields mit Verhaltensregeln.',
                    ],
                    'qm_section_fields' => [
                        'table' => 'qm_section_fields',
                        'note' => 'Pivot: Felder in Sektionen mit Position, Pflichtfeld-Flag und Verhaltensregeln (always, conditional, random, time_based, risk_based).',
                    ],
                    'qm_templates' => [
                        'table' => 'qm_templates',
                        'note' => 'Blueprint-Checklisten aus Sektionen. Settings: HACCP on/off, Deviation-Workflow, Eskalation, Signatur.',
                    ],
                    'qm_template_sections' => [
                        'table' => 'qm_template_sections',
                        'note' => 'Pivot: Sektionen in Templates mit Position und Pflicht-Flag.',
                    ],
                    'qm_instances' => [
                        'table' => 'qm_instances',
                        'note' => 'Ausgefuellte Checklisten. Snapshot der Template-Struktur bei Erstellung. Public Token fuer Gastzugang. Recurrence moeglich.',
                    ],
                    'qm_instance_responses' => [
                        'table' => 'qm_instance_responses',
                        'note' => 'Einzelne Feld-Antworten einer Instanz. JSON-Value fuer Flexibilitaet.',
                    ],
                    'qm_deviations' => [
                        'table' => 'qm_deviations',
                        'note' => 'Abweichungen mit einfachem oder HACCP-Workflow. Eskalation moeglich.',
                    ],
                    'qm_attachments' => [
                        'table' => 'qm_attachments',
                        'note' => 'Polymorphe Anhaenge (Fotos, Dateien, Signaturen) auf Responses oder Deviations.',
                    ],
                    'qm_lookup_tables' => [
                        'table' => 'qm_lookup_tables',
                        'note' => 'Team-eigene Stammdaten-Tabellen (z.B. "Event-Typ", "Location", "Maschinentyp"). Eintraege in qm_lookup_entries.',
                    ],
                    'qm_lookup_entries' => [
                        'table' => 'qm_lookup_entries',
                        'note' => 'Eintraege pro Lookup-Tabelle mit label, value, metadata. Datenquelle fuer Wizard-Select-Felder.',
                    ],
                    'qm_wizard_fields' => [
                        'table' => 'qm_wizard_fields',
                        'note' => 'Startfelder pro Template - was der Wizard beim Erstellen einer Checkliste abfragt (Typ, Reihenfolge, Pflicht, Datenquelle).',
                    ],
                    'qm_wizard_rules' => [
                        'table' => 'qm_wizard_rules',
                        'note' => 'Aktivierungsregeln: "Wenn Wizard-Feld X = Wert Y -> bestimmte Sections ein-/ausblenden".',
                    ],
                    'qm_wizard_rule_sections' => [
                        'table' => 'qm_wizard_rule_sections',
                        'note' => 'Pivot: Regel -> Template-Section -> Effekt (show/hide).',
                    ],
                ],
                'wizard_concept' => [
                    'description' => 'Der Wizard ermoeglicht kontextabhaengige Checklisten. Teams definieren Lookup-Tabellen (Stammdaten) und konfigurieren Wizard-Felder + Regeln pro Template.',
                    'workflow' => '1. Lookup-Tabellen anlegen (Stammdaten) -> 2. Wizard-Felder am Template konfigurieren -> 3. Regeln definieren (wenn Feld=Wert -> Sections show/hide) -> 4. Wizard ausfuellen -> 5. Instanz mit nur relevanten Sections erstellen',
                    'section_categories' => 'Sections haben category: standard (immer dabei) oder addon (nur per Wizard-Regel aktiviert)',
                ],
                'behavior_rules' => [
                    'always' => 'Feld wird immer angezeigt',
                    'conditional' => 'Feld erscheint wenn Bedingung erfuellt (field_uuid, operator, value)',
                    'random' => 'Feld erscheint zufaellig (sampling_rate in %)',
                    'time_based' => 'Feld nur an bestimmten Tagen/Zeiten',
                    'risk_based' => 'Feld haeufiger nach Abweichungen (base_rate, escalation_rate, lookback_days)',
                ],
                'template_settings' => [
                    'haccp_enabled' => 'HACCP-Modus aktivieren (voller Deviation-Workflow)',
                    'deviation_workflow' => 'simple oder full',
                    'escalation_enabled' => 'Automatische Eskalation',
                    'require_signature' => 'Unterschrift erforderlich',
                    'allow_ad_hoc_fields' => 'Ad-hoc Felder beim Ausfuellen',
                    'auto_close_after_hours' => 'Automatisch schliessen nach X Stunden',
                ],
                'hierarchy' => 'FieldType -> FieldDefinition -> SectionField -> Section -> TemplateSection -> Template -> Instance -> Response',
                'related_tools' => [
                    'field_types' => [
                        'list' => 'qm.field-types.GET',
                        'get' => 'qm.field-type.GET',
                        'create' => 'qm.field-types.POST',
                    ],
                    'field_definitions' => [
                        'list' => 'qm.field-definitions.GET',
                        'get' => 'qm.field-definition.GET',
                        'create' => 'qm.field-definitions.POST',
                        'update' => 'qm.field-definitions.PUT',
                        'delete' => 'qm.field-definitions.DELETE',
                    ],
                    'sections' => [
                        'list' => 'qm.sections.GET',
                        'get' => 'qm.section.GET',
                        'create' => 'qm.sections.POST',
                        'update' => 'qm.sections.PUT',
                        'delete' => 'qm.sections.DELETE',
                        'manage_fields' => 'qm.sections.fields.PUT',
                    ],
                    'templates' => [
                        'list' => 'qm.templates.GET',
                        'get' => 'qm.template.GET',
                        'create' => 'qm.templates.POST',
                        'update' => 'qm.templates.PUT',
                        'delete' => 'qm.templates.DELETE',
                        'manage_sections' => 'qm.templates.sections.PUT',
                        'duplicate' => 'qm.templates.duplicate.POST',
                    ],
                    'instances' => [
                        'list' => 'qm.instances.GET',
                        'get' => 'qm.instances.id.GET',
                        'create' => 'qm.instances.POST',
                        'update' => 'qm.instances.PUT',
                        'submit_responses' => 'qm.instances.responses.PUT',
                        'complete' => 'qm.instances.complete.POST',
                        'public_link' => 'qm.instances.public-link.POST',
                    ],
                    'deviations' => [
                        'list' => 'qm.deviations.GET',
                        'get' => 'qm.deviations.id.GET',
                        'update' => 'qm.deviations.PUT',
                        'escalate' => 'qm.deviations.escalate.POST',
                        'verify' => 'qm.deviations.verify.POST',
                    ],
                    'lookup_tables' => [
                        'list' => 'qm.lookup-tables.GET',
                        'get' => 'qm.lookup-table.GET',
                        'create' => 'qm.lookup-tables.POST',
                        'update' => 'qm.lookup-tables.PUT',
                        'delete' => 'qm.lookup-tables.DELETE',
                        'manage_entries' => 'qm.lookup-entries.PUT',
                    ],
                    'wizard' => [
                        'get_config' => 'qm.wizard.GET',
                        'manage_fields' => 'qm.wizard.fields.PUT',
                        'manage_rules' => 'qm.wizard.rules.PUT',
                        'manage_rule_sections' => 'qm.wizard.rule-sections.PUT',
                        'evaluate' => 'qm.wizard.evaluate.POST',
                        'create_instance' => 'qm.wizard.create-instance.POST',
                    ],
                    'analytics' => [
                        'stats' => 'qm.stats.GET',
                        'export' => 'qm.export.GET',
                    ],
                    'schedule' => [
                        'recurrence' => 'qm.instances.schedule.PUT',
                    ],
                ],
                'artisan_commands' => [
                    'qm:process-recurrences' => 'Verarbeitet wiederkehrende Checklisten (laeuft stuendlich)',
                    'qm:process-escalations' => 'Prueft und eskaliert offene Abweichungen (laeuft alle 15 Min)',
                ],
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der QM-Uebersicht: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'overview',
            'tags' => ['overview', 'help', 'qm', 'checklisten'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
