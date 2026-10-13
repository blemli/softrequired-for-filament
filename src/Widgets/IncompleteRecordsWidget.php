<?php

namespace Blemli\SoftRequired\Widgets;

use Blemli\SoftRequired\SoftRequired;
use Blemli\SoftRequired\Support\CompletionModal;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * The completion inbox: every incomplete record with exactly what is
 * missing, an inline "Complete" modal reusing the actual form fields,
 * and a link to the full form.
 */
class IncompleteRecordsWidget extends Widget implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas {
        getDefaultTestingSchemaName as protected getDefaultTestingSchemaNameFromSchemas;
    }

    protected string $view = 'softrequired-for-filament::completion-widget';

    protected int | string | array $columnSpan = 'full';

    /**
     * Access only — never the data. Filament re-checks canView() on every
     * hydrate and aborts with 403 when it fails, so a widget that hides on
     * «nothing incomplete» would break the very request that completes the
     * last record. Whether there is anything to show is the render's call.
     */
    public static function canView(): bool
    {
        return app(SoftRequired::class)->isWidgetEnabled();
    }

    /**
     * Whether the inbox has anything to list right now — for a page that
     * wants to decide the widget's place before rendering it.
     */
    public static function hasEntries(): bool
    {
        return static::canView() && app(SoftRequired::class)->hasIncompleteRecords();
    }

    public static function getSort(): int
    {
        return config('softrequired-for-filament.widget.sort') ?? parent::getSort();
    }

    public function getColumnSpan(): int | string | array
    {
        return config('softrequired-for-filament.widget.column_span') ?? $this->columnSpan;
    }

    public function getHeading(): string
    {
        return __('softrequired-for-filament::softrequired.widget.heading');
    }

    /**
     * Re-render when anything was completed (through this inbox, a list
     * row or a board card) and on the app's generic `refresh` — the usual
     * answer to a page restored from a navigation snapshot.
     *
     * @return array<string, string>
     */
    protected function getListeners(): array
    {
        return [
            ...parent::getListeners(),
            SoftRequired::COMPLETED_EVENT => '$refresh',
            'refresh' => '$refresh',
        ];
    }

    public function getDefaultTestingSchemaName(): ?string
    {
        return $this->getMountedActionSchemaName() ?? $this->getDefaultTestingSchemaNameFromSchemas();
    }

    /**
     * @return array<class-string, array{label: string, url: ?string, tab: string, count: int, more: int, records: array<int, array{key: mixed, title: string, missing: list<string>, editUrl: ?string}>}>
     */
    public function getEntries(): array
    {
        $manager = app(SoftRequired::class);
        $limit = (int) config('softrequired-for-filament.widget.records_limit', 5);

        $entries = [];

        foreach ($manager->widgetEntries() as $model => $entry) {
            $count = $model::incomplete()->count();

            if ($count === 0) {
                continue;
            }

            $resource = $manager->resourceFor($model);

            $records = $model::incomplete()
                ->limit($limit)
                ->get()
                ->map(fn (Model $record): array => [
                    'key' => $record->getKey(),
                    'title' => $record->completionTitle(),
                    'missing' => array_values($record->getIncompleteAttributes()),
                    'editUrl' => $resource === null
                        ? null
                        : rescue(fn (): string => $resource::getUrl('edit', ['record' => $record]), report: false),
                ])
                ->all();

            // Tabs are keyed by model, not by position: entries vanish as
            // they are completed, and a tab chosen by index would point at
            // the wrong model (or at nothing) after the next morph.
            $entries[$model] = [
                ...$entry,
                'tab' => Str::kebab(class_basename($model)),
                'count' => $count,
                'records' => $records,
                'more' => max(0, $count - count($records)),
            ];
        }

        return $entries;
    }

    public function completeAction(): Action
    {
        return Action::make('complete')
            ->label(__('softrequired-for-filament::softrequired.action.complete'))
            ->color('warning')
            ->link()
            ->modalHeading(fn (array $arguments): string => $this->resolveRecord($arguments)?->completionTitle() ?? '')
            ->modalSubmitActionLabel(__('softrequired-for-filament::softrequired.action.save'))
            ->schema(function (array $arguments): array {
                $record = $this->resolveRecord($arguments);

                return $record === null ? [] : CompletionModal::fields($record);
            })
            ->fillForm(function (array $arguments): array {
                $record = $this->resolveRecord($arguments);

                return $record === null ? [] : CompletionModal::prefill($record);
            })
            ->action(function (array $arguments, array $data): void {
                $record = $this->resolveRecord($arguments);

                if ($record === null) {
                    return;
                }

                CompletionModal::persist($record, $data, $this);
            });
    }

    /**
     * Action arguments come from the client — only models the manager
     * tracks are accepted, and record-level authorization applies.
     */
    protected function resolveRecord(array $arguments): ?Model
    {
        $model = $arguments['model'] ?? null;

        if (! is_string($model) || ! array_key_exists($model, app(SoftRequired::class)->widgetEntries())) {
            return null;
        }

        return CompletionModal::resolveRecord($model, $arguments['key'] ?? null);
    }
}
