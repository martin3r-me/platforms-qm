<?php

use Platform\Qm\Livewire\Dashboard\Index as Dashboard;
use Platform\Qm\Livewire\FieldType\Index as FieldTypeIndex;
use Platform\Qm\Livewire\FieldType\Show as FieldTypeShow;
use Platform\Qm\Livewire\FieldDefinition\Index as FieldDefinitionIndex;
use Platform\Qm\Livewire\FieldDefinition\Show as FieldDefinitionShow;
use Platform\Qm\Livewire\Section\Index as SectionIndex;
use Platform\Qm\Livewire\Section\Show as SectionShow;
use Platform\Qm\Livewire\Template\Index as TemplateIndex;
use Platform\Qm\Livewire\Template\Show as TemplateShow;
use Platform\Qm\Livewire\Instance\Index as InstanceIndex;
use Platform\Qm\Livewire\Instance\Show as InstanceShow;

Route::get('/', Dashboard::class)->name('qm.dashboard');

Route::get('/field-types', FieldTypeIndex::class)->name('qm.field-types.index');
Route::get('/field-types/{fieldType}', FieldTypeShow::class)->name('qm.field-types.show');

Route::get('/field-definitions', FieldDefinitionIndex::class)->name('qm.field-definitions.index');
Route::get('/field-definitions/{fieldDefinition}', FieldDefinitionShow::class)->name('qm.field-definitions.show');

Route::get('/sections', SectionIndex::class)->name('qm.sections.index');
Route::get('/sections/{section}', SectionShow::class)->name('qm.sections.show');

Route::get('/templates', TemplateIndex::class)->name('qm.templates.index');
Route::get('/templates/{template}', TemplateShow::class)->name('qm.templates.show');

Route::get('/instances', InstanceIndex::class)->name('qm.instances.index');
Route::get('/instances/{instance}', InstanceShow::class)->name('qm.instances.show');
