<?php

namespace Platform\Qm\Console;

use Illuminate\Console\Command;
use Platform\Qm\Models\QmFieldType;

class SeedFieldTypesCommand extends Command
{
    protected $signature = 'qm:seed-field-types';

    protected $description = 'Seeded die 17 System-Feldtypen (idempotent, kann beliebig oft ausgefuehrt werden).';

    public function handle(): int
    {
        $this->info('Seede QM System-Feldtypen...');

        $types = [
            ['key' => 'text', 'label' => 'Text', 'description' => 'Einzeiliges Textfeld', 'default_config' => ['max_length' => 255]],
            ['key' => 'number', 'label' => 'Zahl', 'description' => 'Numerischer Wert (Ganzzahl oder Dezimal)', 'default_config' => ['decimals' => 0, 'min' => null, 'max' => null]],
            ['key' => 'textarea', 'label' => 'Textbereich', 'description' => 'Mehrzeiliges Textfeld', 'default_config' => ['max_length' => 5000, 'rows' => 4]],
            ['key' => 'boolean', 'label' => 'Checkbox', 'description' => 'Ja/Nein Checkbox', 'default_config' => []],
            ['key' => 'yes_no', 'label' => 'Ja/Nein/N.A.', 'description' => 'Drei-Optionen-Auswahl: Ja, Nein, Nicht zutreffend', 'default_config' => ['options' => ['yes', 'no', 'na']]],
            ['key' => 'select', 'label' => 'Auswahl', 'description' => 'Einfach-Auswahl aus vordefinierten Optionen', 'default_config' => ['options' => []]],
            ['key' => 'multi_select', 'label' => 'Mehrfachauswahl', 'description' => 'Mehrfach-Auswahl aus vordefinierten Optionen', 'default_config' => ['options' => []]],
            ['key' => 'measurement', 'label' => 'Messwert', 'description' => 'Numerischer Messwert mit Einheit und Grenzwerten', 'default_config' => ['unit' => '', 'decimals' => 2, 'min' => null, 'max' => null, 'warning_min' => null, 'warning_max' => null, 'critical_min' => null, 'critical_max' => null]],
            ['key' => 'temperature', 'label' => 'Temperatur', 'description' => 'Temperaturmessung mit Einheit und Grenzwerten (HACCP)', 'default_config' => ['unit' => '°C', 'decimals' => 1, 'min' => -50, 'max' => 200, 'warning_min' => null, 'warning_max' => null, 'critical_min' => null, 'critical_max' => null]],
            ['key' => 'photo', 'label' => 'Foto', 'description' => 'Foto-Upload (Kamera oder Datei)', 'default_config' => ['max_files' => 5, 'max_size_mb' => 10]],
            ['key' => 'signature', 'label' => 'Unterschrift', 'description' => 'Digitale Unterschrift', 'default_config' => []],
            ['key' => 'date', 'label' => 'Datum', 'description' => 'Datumsfeld', 'default_config' => ['format' => 'Y-m-d']],
            ['key' => 'datetime', 'label' => 'Datum & Uhrzeit', 'description' => 'Datum und Uhrzeit', 'default_config' => ['format' => 'Y-m-d H:i']],
            ['key' => 'file', 'label' => 'Datei', 'description' => 'Datei-Upload', 'default_config' => ['max_files' => 3, 'max_size_mb' => 20, 'allowed_extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'png']]],
            ['key' => 'barcode_qr', 'label' => 'Barcode/QR', 'description' => 'Barcode- oder QR-Code-Scan', 'default_config' => ['scan_types' => ['barcode', 'qr']]],
            ['key' => 'person', 'label' => 'Person', 'description' => 'Person/Verantwortlicher', 'default_config' => ['allow_free_text' => true]],
            ['key' => 'location', 'label' => 'Standort', 'description' => 'Standort/Ort', 'default_config' => ['allow_gps' => true, 'allow_free_text' => true]],
        ];

        foreach ($types as $type) {
            QmFieldType::updateOrCreate(
                ['key' => $type['key'], 'team_id' => null],
                [
                    'label' => $type['label'],
                    'description' => $type['description'],
                    'is_system' => true,
                    'default_config' => $type['default_config'],
                ]
            );
        }

        $this->info(count($types) . ' System-Feldtypen geseeded.');

        return self::SUCCESS;
    }
}
