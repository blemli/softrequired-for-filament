<?php

namespace Blemli\SoftRequired\Support;

use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Livewire\Component;

/**
 * Dummy Livewire component that lets a resource form schema be built
 * outside a real request: walking child schemas requires a livewire
 * instance, whose return type is non-nullable.
 */
class SchemaProbe extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public function render(): string
    {
        return '<div></div>';
    }
}
