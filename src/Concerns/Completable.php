<?php

namespace Blemli\SoftRequired\Concerns;

use Blemli\SoftRequired\SoftRequired;
use Filament\Forms\Components\Field;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

trait Completable
{
    /**
     * @return list<string>
     */
    public static function completableAttributes(): array
    {
        return array_keys(static::completableFields());
    }

    /**
     * @return array<string, array{label: string, warn: bool}>
     */
    public static function completableFields(): array
    {
        return app(SoftRequired::class)->fieldsFor(static::class);
    }

    /**
     * The `protected array $completable` override, when the model declares
     * one — it bypasses form introspection entirely.
     *
     * @return list<string>|null
     */
    public static function declaredCompletableAttributes(): ?array
    {
        $instance = new static;

        return property_exists($instance, 'completable') ? $instance->completable : null;
    }

    public function scopeIncomplete(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            foreach (static::completableAttributes() as $attribute) {
                $this->applyAttributeEmptyClause($query, $attribute);
            }
        });
    }

    public function scopeComplete(Builder $query): Builder
    {
        return $query->whereNot(function (Builder $query): void {
            foreach (static::completableAttributes() as $attribute) {
                $this->applyAttributeEmptyClause($query, $attribute);
            }
        });
    }

    protected function applyAttributeEmptyClause(Builder $query, string $attribute): void
    {
        $query->orWhereNull($attribute);

        $cast = $this->getCasts()[$attribute] ?? null;
        $castType = $cast === null ? null : strtolower(explode(':', $cast)[0]);

        if (in_array($castType, ['array', 'json', 'object', 'collection'], true)) {
            $query->orWhereIn($attribute, ['[]', '{}']);

            return;
        }

        // Only string-ish columns compare against '' — on a numeric column
        // some databases would coerce '' to 0 and swallow real values.
        if ($castType === null || $castType === 'string') {
            $query->orWhere($attribute, '');
        }
    }

    public function isComplete(): bool
    {
        return $this->getIncompleteAttributes() === [];
    }

    public function isIncomplete(): bool
    {
        return ! $this->isComplete();
    }

    /**
     * @return array<string, string> attribute => translated label
     */
    public function getIncompleteAttributes(): array
    {
        $incomplete = [];

        foreach (static::completableFields() as $attribute => $field) {
            if (! $this->isAttributeComplete($attribute, $this->getAttribute($attribute))) {
                $incomplete[$attribute] = $field['label'];
            }
        }

        return $incomplete;
    }

    /**
     * How the dashboard widget labels this model when it is tracked without
     * a Filament resource (via the widget.models config).
     */
    public static function completionLabel(): string
    {
        return Str::headline(Str::plural(class_basename(static::class)));
    }

    /**
     * Where the dashboard widget links for this model when it is tracked
     * without a Filament resource. null renders a plain, unlinked stat.
     */
    public static function completionUrl(): ?string
    {
        return null;
    }

    /**
     * How the completion widget titles one record of this model.
     */
    public function completionTitle(): string
    {
        return Str::headline(class_basename(static::class)) . ' #' . $this->getKey();
    }

    /**
     * Form fields for the inline completion modal. null lets the plugin
     * reuse the actual fields of the model's resource form (or plain text
     * inputs when there is no resource) — override to hand it the same
     * field definitions a relation manager or custom form uses.
     *
     * @param  list<string>  $attributes
     * @return array<Field>|null
     */
    public static function completionFormFields(array $attributes): ?array
    {
        return null;
    }

    /**
     * Override to customize what counts as complete for a single attribute.
     * Affects isComplete()/getIncompleteAttributes() only — the SQL scopes
     * stay null-or-empty; override scopeIncomplete()/scopeComplete() when
     * custom emptiness must reach queries too.
     */
    public function isAttributeComplete(string $attribute, mixed $value): bool
    {
        return filled($value);
    }
}
