<?php

namespace Blemli\SoftRequired\Livewire;

use Blemli\SoftRequired\Filters\IncompleteFilter;
use Blemli\SoftRequired\SoftRequired;
use Filament\Resources\Pages\ListRecords;
use Livewire\ComponentHook;

/**
 * Appends the Incomplete filter after the table is built. It cannot be
 * added via Table::configureUsing — that runs inside Table::make(), and
 * the resource's own ->filters([...]) call would reset it away again.
 *
 * Injection is attempted at several lifecycle points because provider
 * boot order decides whether this hook's mount/hydrate run before or
 * after Filament builds the table: whichever attempt finds the table
 * first wins, the rest are no-ops.
 */
class TableFilterHook extends ComponentHook
{
    public function mount(): void
    {
        $this->inject();
    }

    public function hydrate(): void
    {
        $this->inject();
    }

    public function call(string $method, array $params, callable $returnEarly): void
    {
        $this->inject();
    }

    public function render(): void
    {
        $this->inject();
    }

    protected function inject(): void
    {
        $page = $this->component;

        if (! $page instanceof ListRecords) {
            return;
        }

        // Too early — Filament has not built the table yet; a later
        // lifecycle attempt will land after it has.
        if (! (fn (): bool => isset($this->table))->call($page)) {
            return;
        }

        $manager = app(SoftRequired::class);

        if (! $manager->shouldAddFilterTo($page)) {
            return;
        }

        $table = $page->getTable();

        if ($manager->isFilterDisabledFor($table)) {
            return;
        }

        if ($table->getFilter('incomplete', withHidden: true) !== null) {
            return;
        }

        $table->pushFilters([IncompleteFilter::make()]);

        // The filters form was already materialized and filled when the
        // table booted — replay that with the appended filter included.
        $form = $page->getTableFiltersForm();
        $form->components($table->getFiltersFormSchema());
        $form->fill($page->tableFilters);
    }
}
