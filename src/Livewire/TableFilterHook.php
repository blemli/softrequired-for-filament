<?php

namespace Blemli\SoftRequired\Livewire;

use Blemli\SoftRequired\Actions\CompleteRecordAction;
use Blemli\SoftRequired\Filters\IncompleteFilter;
use Blemli\SoftRequired\SoftRequired;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Livewire\ComponentHook;

/**
 * Appends the Incomplete filter — and the per-record "Complete" action that
 * goes with it — after the table is built. It cannot be
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

        $manager = app(SoftRequired::class);

        if (! $manager->shouldAddFilterTo($page)) {
            return;
        }

        $model = $page::getResource()::getModel();

        // The modal lives on the page, cached before Filament boots the
        // table — that is what keeps it resolvable on submit (hydrate runs
        // before booted). Re-caching on later attempts replaces the entry.
        $page->cacheAction(CompleteRecordAction::make()->forModel($model));

        // Too early — Filament has not built the table yet; a later
        // lifecycle attempt will land after it has.
        if (! (fn (): bool => isset($this->table))->call($page)) {
            return;
        }

        $table = $page->getTable();

        if ($manager->isFilterDisabledFor($table)) {
            return;
        }

        if ($table->getFilter('incomplete', withHidden: true) === null) {
            $table->pushFilters([IncompleteFilter::make()]);

            // A filter without a state entry counts as ACTIVE when the query
            // is built (Filament defaults a missing isActive to true), so the
            // appended filter needs its inactive default written into the
            // page state — otherwise the list is silently narrowed on first
            // load, with the checkbox still unticked.
            if (! isset($page->tableFilters['incomplete'])) {
                $page->tableFilters = ($page->tableFilters ?? []) + ['incomplete' => ['isActive' => false]];
            }

            // The filters form was already materialized and filled when the
            // table booted — replay that with the appended filter included.
            $form = $page->getTableFiltersForm();
            $form->components($table->getFiltersFormSchema());
            $form->fill($page->tableFilters);
        }

        if ($table->getAction('complete') === null) {
            // Rows and cards alike: the grid layout renders record actions
            // inside each card. The button only mounts the page action —
            // it is never mounted itself. Shown while the Incomplete filter
            // is active (or always, when the plugin says so) and only for
            // records that still miss something.
            $table->pushRecordActions([
                Action::make('complete')
                    ->label(fn (): string => __('softrequired-for-filament::softrequired.action.complete'))
                    ->color('warning')
                    ->link()
                    ->livewireClickHandlerEnabled(false)
                    ->alpineClickHandler(fn (Model $record): string => '$wire.mountAction(\'completeRecord\', ' . json_encode(['key' => (string) $record->getKey()]) . ')')
                    ->visible(fn (?Model $record, Table $table): bool => $record !== null
                        && $record->isIncomplete()
                        && ($manager->isCompleteActionAlwaysVisible() || static::incompleteFilterActive($table))),
            ]);
        }
    }

    protected static function incompleteFilterActive(Table $table): bool
    {
        return (bool) ($table->getLivewire()->getTableFilterState('incomplete')['isActive'] ?? false);
    }
}
