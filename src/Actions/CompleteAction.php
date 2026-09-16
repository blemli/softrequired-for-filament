<?php

namespace Blemli\SoftRequired\Actions;

use Blemli\SoftRequired\Support\CompletionModal;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;

/**
 * "Complete" for one record wherever a record is at hand: pushed onto every
 * Completable resource's list table (rows and cards alike) by the table
 * hook, and usable on any other surface that hands the action a record —
 * a kanban card, a relation manager. Hidden for complete records.
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
            ->modalHeading(function (Model $record): string {
                file_put_contents('/tmp/softreq-debug.txt', "heading\n", FILE_APPEND);

                return $record->completionTitle();
            })
            ->modalSubmitActionLabel(fn (): string => __('softrequired-for-filament::softrequired.action.save'))
            ->schema(function (Model $record): array {
                file_put_contents('/tmp/softreq-debug.txt', 'schema:' . count(CompletionModal::fields($record)) . "\n", FILE_APPEND);

                return CompletionModal::fields($record);
            })
            ->fillForm(function (Model $record): array {
                file_put_contents('/tmp/softreq-debug.txt', "fill\n", FILE_APPEND);

                return CompletionModal::prefill($record);
            })
            ->visible(fn (?Model $record): bool => $record !== null && method_exists($record, 'isIncomplete') && $record->isIncomplete())
            ->action(function (Model $record, array $data): void {
                file_put_contents('/tmp/softreq-debug.txt', json_encode(['data' => $data, 'incomplete' => array_keys($record->getIncompleteAttributes()), 'fields' => array_map(fn ($f) => $f->getName(), CompletionModal::fields($record))]));
                CompletionModal::persist($record, $data);
            });
    }
}
