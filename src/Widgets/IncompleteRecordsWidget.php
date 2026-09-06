<?php

namespace Blemli\SoftRequired\Widgets;

use Blemli\SoftRequired\SoftRequired;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Field;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

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

    public static function canView(): bool
    {
        $manager = app(SoftRequired::class);

        return $manager->isWidgetEnabled() && $manager->hasIncompleteRecords();
    }

    public static function getSort(): int
    {
        return config('softrequired-for-filament.widget.sort') ?? parent::getSort();
    }

    public function getHeading(): string
    {
        return __('softrequired-for-filament::softrequired.widget.heading');
    }

    public function getDefaultTestingSchemaName(): ?string
    {
        return $this->getMountedActionSchemaName() ?? $this->getDefaultTestingSchemaNameFromSchemas();
    }

    /**
     * @return array<class-string, array{label: string, url: ?string, count: int, records: array<int, array{key: mixed, title: string, missing: list<string>, editUrl: ?string}>}>
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

            $entries[$model] = [
                ...$entry,
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

                return $record === null ? [] : $this->modalFields($record);
            })
            ->fillForm(function (array $arguments): array {
                $record = $this->resolveRecord($arguments);

                return $record?->only($this->modalAttributes($record)) ?? [];
            })
            ->action(function (array $arguments, array $data): void {
                $record = $this->resolveRecord($arguments);

                if ($record === null) {
                    return;
                }

                // Companion fields are disabled and never dehydrate — only
                // the actually-missing attributes may be written.
                $record->fill(Arr::only($data, array_keys($record->getIncompleteAttributes())))->save();

                Notification::make()
                    ->success()
                    ->title(__('softrequired-for-filament::softrequired.action.completed'))
                    ->send();
            });
    }

    /**
     * The modal's fields: the record's missing attributes plus every field
     * their validation rules depend on, recursively.
     *
     * @return array<Field>
     */
    protected function modalFields(Model $record): array
    {
        return app(SoftRequired::class)->completionFormFields(
            $record::class,
            array_keys($record->getIncompleteAttributes()),
        );
    }

    /**
     * Everything shown in the modal (missing + greyed-out companions), for
     * prefilling — always derived server-side, never from client data.
     *
     * @return list<string>
     */
    protected function modalAttributes(Model $record): array
    {
        return array_map(
            fn ($field): string => $field->getName(),
            $this->modalFields($record),
        );
    }

    /**
     * Action arguments come from the client — only models the manager
     * tracks are accepted, and record-level authorization applies.
     */
    protected function resolveRecord(array $arguments): ?Model
    {
        $manager = app(SoftRequired::class);
        $model = $arguments['model'] ?? null;
        $key = $arguments['key'] ?? null;

        if (! is_string($model) || $key === null || ! array_key_exists($model, $manager->widgetEntries())) {
            return null;
        }

        $record = $model::query()->find($key);

        if ($record === null) {
            return null;
        }

        $resource = $manager->resourceFor($model);

        if ($resource !== null && ! $resource::canEdit($record)) {
            return null;
        }

        if ($resource === null && Gate::getPolicyFor($model) !== null && ! Gate::allows('update', $record)) {
            return null;
        }

        return $record;
    }
}
