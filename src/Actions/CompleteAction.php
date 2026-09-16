<?php

namespace Blemli\SoftRequired\Actions;

use Blemli\SoftRequired\Support\CompletionModal;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;

/**
 * "Complete" for one record wherever a surface hands the action its record:
 * a kanban card, a relation manager, a custom page. The list pages get the
 * same modal through CompleteRecordAction instead (see the table hook).
 * Hidden for complete records; quiet when evaluated without a record.
 */
class CompleteAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'complete';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(fn (): string => __('softrequired-for-filament::softrequired.action.complete'))
            ->color('warning')
            ->link()
            // Pages may evaluate the modal configuration without a record at
            // hand (a kanban board caching its card actions) — stay quiet then.
            ->modalHeading(fn (?Model $record): string => $record?->completionTitle() ?? '')
            ->modalSubmitActionLabel(fn (): string => __('softrequired-for-filament::softrequired.action.save'))
            ->schema(fn (?Model $record): array => $record === null ? [] : CompletionModal::fields($record))
            ->fillForm(fn (?Model $record): array => $record === null ? [] : CompletionModal::prefill($record))
            ->visible(fn (?Model $record): bool => $record !== null && method_exists($record, 'isIncomplete') && $record->isIncomplete())
            ->action(function (?Model $record, array $data): void {
                if ($record === null) {
                    return;
                }

                CompletionModal::persist($record, $data);
            });
    }
}
