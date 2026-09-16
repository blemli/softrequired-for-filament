<?php

namespace Blemli\SoftRequired\Actions;

use Blemli\SoftRequired\Support\CompletionModal;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;

/**
 * The "Complete" modal as a PAGE action driven by arguments (`key`): the
 * list pages get it cached by the table hook and every row/card button just
 * mounts it. A table action could not carry the modal — Filament resolves
 * mounted table actions while booting the table, before any hook can push
 * an action into it, and would silently unmount the modal on submit.
 */
class CompleteRecordAction extends Action
{
    protected ?string $modelClass = null;

    public static function getDefaultName(): ?string
    {
        return 'completeRecord';
    }

    public function forModel(string $modelClass): static
    {
        $this->modelClass = $modelClass;

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(fn (): string => __('softrequired-for-filament::softrequired.action.complete'))
            ->color('warning')
            ->link()
            ->modalHeading(fn (array $arguments): string => $this->resolve($arguments)?->completionTitle() ?? '')
            ->modalSubmitActionLabel(fn (): string => __('softrequired-for-filament::softrequired.action.save'))
            ->schema(function (array $arguments): array {
                $record = $this->resolve($arguments);

                return $record === null ? [] : CompletionModal::fields($record);
            })
            ->fillForm(function (array $arguments): array {
                $record = $this->resolve($arguments);

                return $record === null ? [] : CompletionModal::prefill($record);
            })
            ->action(function (array $arguments, array $data): void {
                $record = $this->resolve($arguments);

                if ($record === null) {
                    return;
                }

                CompletionModal::persist($record, $data);
            });
    }

    protected function resolve(array $arguments): ?Model
    {
        return CompletionModal::resolveRecord($this->modelClass, $arguments['key'] ?? null);
    }
}
