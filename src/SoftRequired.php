<?php

namespace Blemli\SoftRequired;

use Blemli\SoftRequired\Concerns\Completable;
use Blemli\SoftRequired\Contracts\HasIncompleteSaveMode;
use Blemli\SoftRequired\Support\SchemaIntrospector;
use Filament\Facades\Filament;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use WeakMap;

class SoftRequired
{
    /**
     * @var array<class-string, array<string, array{label: string, warn: bool}>>
     */
    protected array $fieldCache = [];

    /**
     * @var WeakMap<Table, true>
     */
    protected WeakMap $tablesWithoutFilter;

    public function __construct()
    {
        $this->tablesWithoutFilter = new WeakMap;
    }

    /**
     * The soft-required attributes of a model, introspected from its
     * Filament resource form once per request — or taken from the model's
     * `protected array $completable` override.
     *
     * @param  class-string  $modelClass
     * @return array<string, array{label: string, warn: bool}>
     */
    public function fieldsFor(string $modelClass): array
    {
        return $this->fieldCache[$modelClass] ??= $this->resolveFields($modelClass);
    }

    public function flush(): void
    {
        $this->fieldCache = [];
    }

    /**
     * @param  class-string  $modelClass
     * @return array<string, array{label: string, warn: bool}>
     */
    protected function resolveFields(string $modelClass): array
    {
        $declared = $modelClass::declaredCompletableAttributes();

        if ($declared !== null) {
            $labels = $this->declaredFieldLabels($modelClass, $declared);

            return collect($declared)->mapWithKeys(fn (string $attribute): array => [
                $attribute => ['label' => $labels[$attribute] ?? Str::headline($attribute), 'warn' => true],
            ])->all();
        }

        return app(SchemaIntrospector::class)->softRequiredFields($modelClass);
    }

    /**
     * Declared attributes have no resource form to take labels from — the
     * model's completionFormFields() override is the next best source, so
     * translated labels flow into badges and notifications.
     *
     * @param  class-string  $modelClass
     * @param  list<string>  $attributes
     * @return array<string, string>
     */
    protected function declaredFieldLabels(string $modelClass, array $attributes): array
    {
        $labels = [];

        foreach ($modelClass::completionFormFields($attributes) ?? [] as $field) {
            if ($field instanceof Field) {
                $labels[$field->getName()] = (string) $field->getLabel();
            }
        }

        return $labels;
    }

    /**
     * @param  class-string  $modelClass
     */
    public function usesCompletable(string $modelClass): bool
    {
        return in_array(Completable::class, class_uses_recursive($modelClass), true);
    }

    public function incompleteSaveModeFor(object $livewire): string
    {
        if ($livewire instanceof HasIncompleteSaveMode) {
            return $livewire->incompleteSaveMode();
        }

        return SoftRequiredPlugin::current()?->getOnIncompleteSave()
            ?? config('softrequired-for-filament.on_incomplete_save', 'notify');
    }

    public function shouldAddFilterTo(ListRecords $page): bool
    {
        $plugin = SoftRequiredPlugin::current();

        if (! ($plugin?->hasTableFilter() ?? config('softrequired-for-filament.table_filter.enabled', true))) {
            return false;
        }

        $resource = $page::getResource();

        $except = array_merge(
            config('softrequired-for-filament.table_filter.except', []),
            $plugin?->getExcept() ?? [],
        );

        if (in_array($resource, $except, true)) {
            return false;
        }

        $model = $resource::getModel();

        if (! $this->usesCompletable($model)) {
            return false;
        }

        return rescue(fn (): array => $this->fieldsFor($model), [], report: false) !== [];
    }

    public function disableFilterFor(Table $table): void
    {
        $this->tablesWithoutFilter[$table] = true;
    }

    public function isFilterDisabledFor(Table $table): bool
    {
        return isset($this->tablesWithoutFilter[$table]);
    }

    public function isWidgetEnabled(): bool
    {
        return SoftRequiredPlugin::current()?->hasWidget()
            ?? (bool) config('softrequired-for-filament.widget.enabled', true);
    }

    /**
     * Resources of the current panel whose model is Completable and has at
     * least one soft-required attribute.
     *
     * @return array<class-string<resource>>
     */
    public function completableResources(): array
    {
        $panel = Filament::getCurrentOrDefaultPanel();

        if ($panel === null) {
            return [];
        }

        $resources = [];

        foreach ($panel->getResources() as $resource) {
            $model = $resource::getModel();

            if (! $this->usesCompletable($model)) {
                continue;
            }

            if (rescue(fn (): array => $this->fieldsFor($model), [], report: false) === []) {
                continue;
            }

            $resources[] = $resource;
        }

        return $resources;
    }

    public function hasIncompleteRecords(): bool
    {
        foreach (array_keys($this->widgetEntries()) as $model) {
            if ($model::incomplete()->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Everything the dashboard widget reports: panel resources first, then
     * config-listed models that have no resource of their own.
     *
     * @return array<class-string, array{label: string, url: ?string}>
     */
    public function widgetEntries(): array
    {
        $entries = [];

        foreach ($this->completableResources() as $resource) {
            $entries[$resource::getModel()] = [
                'label' => $resource::getPluralModelLabel(),
                'url' => $this->filteredIndexUrl($resource),
            ];
        }

        foreach (config('softrequired-for-filament.widget.models', []) as $model) {
            if (isset($entries[$model]) || ! $this->usesCompletable($model)) {
                continue;
            }

            if (rescue(fn (): array => $this->fieldsFor($model), [], report: false) === []) {
                continue;
            }

            $entries[$model] = [
                'label' => $model::completionLabel(),
                'url' => $model::completionUrl(),
            ];
        }

        return $entries;
    }

    /**
     * @param  class-string<resource>  $resource
     */
    public function filteredIndexUrl(string $resource): string
    {
        return $resource::getUrl()
            . '?' . http_build_query(['filters' => ['incomplete' => ['isActive' => true]]]);
    }

    public function isResourceWidgetEnabled(): bool
    {
        return SoftRequiredPlugin::current()?->hasResourceWidget()
            ?? (bool) config('softrequired-for-filament.resource_widget.enabled', true);
    }

    /**
     * @param  class-string  $modelClass
     * @return class-string<resource>|null
     */
    public function resourceFor(string $modelClass): ?string
    {
        return app(SchemaIntrospector::class)->resolveResource($modelClass);
    }

    /**
     * The fields for the inline completion modal: the model's own override
     * first, then the actual resource form fields, then plain text inputs.
     *
     * @param  class-string  $modelClass
     * @param  list<string>  $attributes
     * @return array<Field>
     */
    public function completionFormFields(string $modelClass, array $attributes): array
    {
        $fields = $modelClass::completionFormFields($attributes);

        if ($fields !== null) {
            return $fields;
        }

        $fields = app(SchemaIntrospector::class)->formFields($modelClass, $attributes);

        if ($fields !== []) {
            return $fields;
        }

        $labels = rescue(fn (): array => $this->fieldsFor($modelClass), [], report: false);

        return array_map(
            fn (string $attribute): TextInput => TextInput::make($attribute)
                ->label($labels[$attribute]['label'] ?? Str::headline($attribute)),
            $attributes,
        );
    }
}
