<?php

namespace Blemli\SoftRequired\Support;

use Blemli\SoftRequired\Exceptions\IntrospectionFailedException;
use Filament\Facades\Filament;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Repeater;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Generator;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SchemaIntrospector
{
    /**
     * Build the model's resource form standalone and collect its
     * soft-required fields.
     *
     * @param  class-string  $modelClass
     * @return array<string, array{label: string, warn: bool}>
     */
    public function softRequiredFields(string $modelClass): array
    {
        try {
            $resource = $this->resolveResource($modelClass);

            if ($resource === null) {
                throw new RuntimeException('no Filament resource is registered for this model.');
            }

            $schema = $resource::form(
                Schema::make(app(SchemaProbe::class))
                    ->model($modelClass)
                    ->operation('create'),
            );

            $fields = [];

            foreach ($this->fields($schema->getComponents(withHidden: true)) as $field) {
                if (! $field->hasMeta('softRequired')) {
                    continue;
                }

                // Dotted names bind to relationship state, not a column.
                if (str_contains($field->getName(), '.')) {
                    continue;
                }

                // A closure condition that cannot be evaluated outside a
                // request still counts as soft-required.
                if (! rescue(fn (): bool => $field->isSoftRequired(), true, report: false)) {
                    continue;
                }

                $fields[$field->getName()] = [
                    'label' => (string) $field->getLabel(),
                    'warn' => (bool) rescue(fn (): bool => $field->shouldWarnWhenSoftRequired(), true, report: false),
                ];
            }

            return $fields;
        } catch (Throwable $exception) {
            throw IntrospectionFailedException::for($modelClass, $exception);
        }
    }

    /**
     * Laravel's dependent validation rules — their parameters reference
     * other fields (required_with:end, after:start, gt:min, …).
     */
    protected const DEPENDENT_RULES = [
        'accepted_if', 'after', 'after_or_equal', 'before', 'before_or_equal',
        'declined_if', 'different', 'exclude_if', 'exclude_unless',
        'exclude_with', 'exclude_without', 'gt', 'gte', 'in_array', 'lt',
        'lte', 'missing_if', 'missing_unless', 'missing_with',
        'missing_with_all', 'prohibited_if', 'prohibited_unless', 'prohibits',
        'required_if', 'required_if_accepted', 'required_if_declined',
        'required_unless', 'required_with', 'required_with_all',
        'required_without', 'required_without_all', 'same',
    ];

    /**
     * The model's resource form fields for the given attributes — the
     * actual Field objects, freshly built, in form order. Fields that the
     * requested fields' validation rules depend on are pulled in
     * transitively (in both directions), so the extracted form validates
     * exactly like the full one. Empty when the model has no resource or
     * the build fails.
     *
     * @param  class-string  $modelClass
     * @param  list<string>  $attributes
     * @return array<Field>
     */
    public function formFields(string $modelClass, array $attributes): array
    {
        try {
            $resource = $this->resolveResource($modelClass);

            if ($resource === null) {
                return [];
            }

            $schema = $resource::form(
                Schema::make(app(SchemaProbe::class))
                    ->model($modelClass)
                    ->operation('edit'),
            );

            $fields = [];

            foreach ($this->fields($schema->getComponents(withHidden: true)) as $field) {
                $fields[$field->getName()] ??= $field;
            }

            $names = $this->expandWithRuleDependencies($fields, $attributes);

            // Pulled-in dependency fields are context, not the task: greyed
            // out, and therefore neither validated nor saved.
            return collect($fields)
                ->only($names)
                ->map(fn (Field $field): Field => in_array($field->getName(), $attributes, true)
                    ? $field
                    : $field->disabled())
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * The requested names plus every field reachable through dependent
     * validation rules, in either direction: a rule on a requested field
     * referencing another, or a rule elsewhere referencing a requested
     * field — recursively, cycle-safe.
     *
     * @param  array<string, Field>  $fields
     * @param  list<string>  $attributes
     * @return list<string>
     */
    protected function expandWithRuleDependencies(array $fields, array $attributes): array
    {
        $adjacency = [];

        foreach ($fields as $name => $field) {
            foreach ($this->ruleDependencies($field, $fields) as $other) {
                $adjacency[$name][] = $other;
                $adjacency[$other][] = $name;
            }
        }

        $queue = array_values(array_intersect(array_keys($fields), $attributes));
        $included = array_fill_keys($queue, true);

        while ($queue !== []) {
            foreach ($adjacency[array_shift($queue)] ?? [] as $other) {
                if (! isset($included[$other])) {
                    $included[$other] = true;
                    $queue[] = $other;
                }
            }
        }

        return array_keys($included);
    }

    /**
     * Field names this field's string rules reference. Rule parameters may
     * be absolute state paths — only the trailing segment is matched, and
     * only against fields that actually exist in the form.
     *
     * @param  array<string, Field>  $fields
     * @return list<string>
     */
    protected function ruleDependencies(Field $field, array $fields): array
    {
        $rules = rescue(fn (): array => $field->getValidationRules(), [], report: false);

        $dependencies = [];

        foreach ($rules as $rule) {
            if (! is_string($rule) || ! str_contains($rule, ':')) {
                continue;
            }

            [$name, $parameters] = explode(':', $rule, 2);

            if (! in_array(strtolower($name), self::DEPENDENT_RULES, true)) {
                continue;
            }

            foreach (explode(',', $parameters) as $parameter) {
                $candidate = Str::afterLast(trim($parameter), '.');

                if ($candidate !== $field->getName() && isset($fields[$candidate])) {
                    $dependencies[] = $candidate;
                }
            }
        }

        return $dependencies;
    }

    /**
     * @param  class-string  $modelClass
     * @return class-string<resource>|null
     */
    public function resolveResource(string $modelClass): ?string
    {
        $resource = Filament::getCurrentOrDefaultPanel()?->getModelResource($modelClass);

        if ($resource !== null) {
            return $resource;
        }

        foreach (Filament::getPanels() as $panel) {
            $resource = $panel->getModelResource($modelClass);

            if ($resource !== null) {
                return $resource;
            }
        }

        return null;
    }

    /**
     * All fields of the schema, recursing through layout containers but
     * never into repeaters or builders — their rows map to relations, not
     * columns, so soft-required inside them stays form-only.
     *
     * @param  array<mixed>  $components
     * @return Generator<int, Field>
     */
    protected function fields(array $components): Generator
    {
        foreach ($components as $component) {
            if ($component instanceof Repeater || $component instanceof Builder) {
                continue;
            }

            if ($component instanceof Field) {
                yield $component;
            }

            foreach ($component->getChildSchemas(withHidden: true) as $childSchema) {
                yield from $this->fields($childSchema->getComponents(withHidden: true));
            }
        }
    }
}
