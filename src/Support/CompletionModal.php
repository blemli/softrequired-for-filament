<?php

namespace Blemli\SoftRequired\Support;

use Blemli\SoftRequired\SoftRequired;
use Filament\Forms\Components\Field;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

/**
 * The "Complete" modal, once: the dashboard widget and the table/card
 * action both build their fields, prefill and write through here, so the
 * two never drift apart.
 */
class CompletionModal
{
    /**
     * A record named by client-side action arguments — only Completable
     * models, and only when the caller may edit it (resource gate, else
     * policy).
     */
    public static function resolveRecord(?string $model, mixed $key): ?Model
    {
        $manager = app(SoftRequired::class);

        if (! is_string($model) || $key === null || ! class_exists($model) || ! $manager->usesCompletable($model)) {
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

    /**
     * The modal's fields: the record's missing attributes plus every field
     * their validation rules depend on, recursively.
     *
     * @return array<Field>
     */
    public static function fields(Model $record): array
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
    public static function attributes(Model $record): array
    {
        return array_map(
            fn (Field $field): string => $field->getName(),
            static::fields($record),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function prefill(Model $record): array
    {
        return $record->only(static::attributes($record));
    }

    /**
     * Companion fields are disabled and never dehydrate — only the
     * actually-missing attributes may be written.
     *
     * @param  array<string, mixed>  $data
     */
    public static function persist(Model $record, array $data): void
    {
        $record->fill(Arr::only($data, array_keys($record->getIncompleteAttributes())))->save();

        Notification::make()
            ->success()
            ->title(__('softrequired-for-filament::softrequired.action.completed'))
            ->send();
    }
}
