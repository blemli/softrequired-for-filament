<?php

namespace Blemli\SoftRequired\Livewire;

use Blemli\SoftRequired\SoftRequired;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Livewire\Component;
use Livewire\ComponentHook;

use function Livewire\wrap;

class SaveHook extends ComponentHook
{
    /**
     * The confirm modal is a page action mounted from call() below; caching
     * it on every request keeps it resolvable both on the mounting request
     * and on the follow-up request that runs the confirmed action.
     */
    public function boot(): void
    {
        $page = $this->component;

        if (! $page instanceof CreateRecord && ! $page instanceof EditRecord) {
            return;
        }

        if (app(SoftRequired::class)->incompleteSaveModeFor($page) !== 'confirm') {
            return;
        }

        $page->cacheAction($this->makeConfirmAction());
    }

    /**
     * Intercept the page's submit method. The record always remains
     * saveable — depending on the mode the user is either warned after
     * the fact (notify) or asked first (confirm).
     */
    public function call(string $method, array $params, callable $returnEarly): void
    {
        $page = $this->component;

        $intercepts = ($page instanceof EditRecord && $method === 'save')
            || ($page instanceof CreateRecord && in_array($method, ['create', 'createAnother'], true));

        if (! $intercepts) {
            return;
        }

        $mode = app(SoftRequired::class)->incompleteSaveModeFor($page);

        if (! in_array($mode, ['notify', 'confirm'], true)) {
            return;
        }

        $missing = $this->missingFieldLabels($page);

        if ($missing === []) {
            return;
        }

        if ($mode === 'confirm') {
            $page->mountAction('softRequiredConfirmSave', [
                'method' => $method,
                'params' => $params,
                'missing' => $missing,
            ]);

            $returnEarly(null);

            return;
        }

        // The page method runs through wrap() so a ValidationException is
        // converted into component errors exactly like a native call.
        wrap($page)->{$method}(...$params);

        if ($page->getErrorBag()->isEmpty()) {
            Notification::make()
                ->warning()
                ->title(__('softrequired-for-filament::softrequired.notification.title'))
                ->body(__('softrequired-for-filament::softrequired.notification.body', [
                    'fields' => implode(', ', $missing),
                ]))
                ->send();
        }

        $returnEarly(null);
    }

    protected function makeConfirmAction(): Action
    {
        return Action::make('softRequiredConfirmSave')
            ->requiresConfirmation()
            ->color('warning')
            ->modalIcon(Heroicon::OutlinedExclamationTriangle)
            ->modalIconColor('warning')
            ->modalHeading(__('softrequired-for-filament::softrequired.modal.heading'))
            ->modalDescription(fn (array $arguments): string => __('softrequired-for-filament::softrequired.modal.description', [
                'fields' => implode(', ', $arguments['missing'] ?? []),
            ]))
            ->modalSubmitActionLabel(__('softrequired-for-filament::softrequired.modal.confirm'))
            ->action(function (array $arguments, Component $livewire): void {
                // An internal PHP call does not re-enter component hooks,
                // so the confirmed save cannot loop back into call().
                wrap($livewire)->{$arguments['method']}(...($arguments['params'] ?? []));
            });
    }

    /**
     * Labels of soft-required, warn-enabled fields that are still blank —
     * read from the live page schema so state and labels are current.
     *
     * @return list<string>
     */
    protected function missingFieldLabels(CreateRecord | EditRecord $page): array
    {
        $schema = rescue(fn () => $page->getSchema('form'), report: false);

        if ($schema === null) {
            return [];
        }

        $labels = [];

        foreach ($schema->getFlatFields(withHidden: true) as $field) {
            if (! $field->hasMeta('softRequired')) {
                continue;
            }

            if (! $field->isSoftRequired() || ! $field->shouldWarnWhenSoftRequired()) {
                continue;
            }

            if (blank($field->getState())) {
                $labels[] = (string) $field->getLabel();
            }
        }

        return array_values(array_unique($labels));
    }
}
