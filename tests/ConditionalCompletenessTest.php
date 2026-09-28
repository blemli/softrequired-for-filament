<?php

use Blemli\SoftRequired\Support\SchemaProbe;
use Blemli\SoftRequired\Tests\Fixtures\DraftModel;
use Blemli\SoftRequired\Tests\Fixtures\DraftResource;
use Blemli\SoftRequired\Tests\Fixtures\DraftResourcePages\CreateDraftModel;
use Blemli\SoftRequired\Tests\Fixtures\DraftResourcePages\EditDraftModel;
use Blemli\SoftRequired\Tests\Fixtures\DraftResourcePages\ListDraftModels;
use Blemli\SoftRequired\Widgets\IncompleteRecordsWidget;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

use function Pest\Livewire\livewire;

beforeEach(function () {
    loginUser();
    bootPanel();
});

it('lets a record opt out of completeness on the php side', function () {
    $draft = DraftModel::create(['title' => 'd', 'status' => 'draft', 'email' => null]);
    $live = DraftModel::create(['title' => 'l', 'status' => 'live', 'email' => null]);

    expect($draft->isComplete())->toBeTrue()
        ->and($draft->getIncompleteAttributes())->toBe([])
        ->and($live->isIncomplete())->toBeTrue()
        ->and($live->getIncompleteAttributes())->toHaveKey('email');
});

it('keeps the scopes in step with the php side', function () {
    DraftModel::create(['title' => 'draft-empty', 'status' => 'draft', 'email' => null]);
    DraftModel::create(['title' => 'draft-filled', 'status' => 'draft', 'email' => 'a@example.com']);
    DraftModel::create(['title' => 'live-empty', 'status' => 'live', 'email' => null]);
    DraftModel::create(['title' => 'live-filled', 'status' => 'live', 'email' => 'b@example.com']);
    DraftModel::create(['title' => 'unset-empty', 'status' => null, 'email' => null]);

    expect(DraftModel::incomplete()->pluck('title')->all())->toBe(['live-empty', 'unset-empty'])
        ->and(DraftModel::complete()->pluck('title')->all())->toBe(['draft-empty', 'draft-filled', 'live-filled']);

    DraftModel::all()->each(function (DraftModel $record): void {
        expect(DraftModel::incomplete()->whereKey($record)->exists())->toBe($record->isIncomplete(), $record->title);
    });
});

it('leaves other constraints intact around the required clause', function () {
    DraftModel::create(['title' => 'a', 'status' => 'live', 'email' => null]);
    DraftModel::create(['title' => 'b', 'status' => 'live', 'email' => null]);
    DraftModel::create(['title' => 'a', 'status' => 'draft', 'email' => null]);

    expect(DraftModel::where('title', 'a')->incomplete()->count())->toBe(1)
        ->and(DraftModel::where('title', 'a')->complete()->count())->toBe(1);
});

it('still introspects the soft-required attributes record-independently', function () {
    expect(DraftModel::completableAttributes())->toBe(['email']);
});

it('keeps the form hint quiet for a record that needs no completion', function () {
    $draft = DraftModel::create(['title' => 'd', 'status' => 'draft']);
    $live = DraftModel::create(['title' => 'l', 'status' => 'live']);

    $hintFor = function (DraftModel $record): ?string {
        $schema = Schema::make(app(SchemaProbe::class))
            ->model($record)
            ->components([TextInput::make('email')->softRequired()]);

        $schema->fill(['email' => null]);

        return $schema->getComponent('email')->getHint();
    };

    expect($hintFor($draft))->toBeNull()
        ->and($hintFor($live))->toBe(__('softrequired-for-filament::softrequired.field.hint'));
});

it('does not warn on save while the record needs no completion', function () {
    $draft = DraftModel::create(['title' => 'd', 'status' => 'draft']);

    livewire(EditDraftModel::class, ['record' => $draft->getRouteKey()])
        ->fillForm(['title' => 'd', 'email' => null])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotNotified(__('softrequired-for-filament::softrequired.notification.title'));

    $live = DraftModel::create(['title' => 'l', 'status' => 'live']);

    livewire(EditDraftModel::class, ['record' => $live->getRouteKey()])
        ->fillForm(['title' => 'l', 'email' => null])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified(__('softrequired-for-filament::softrequired.notification.title'));
});

it('lets the fresh model instance decide on create', function () {
    // A DraftModel without a status is required to be complete — the
    // create page warns like any other; a model whose defaults opt out
    // would stay quiet (see the app-level hint test above).
    livewire(CreateDraftModel::class)
        ->fillForm(['title' => 'n', 'email' => null])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified(__('softrequired-for-filament::softrequired.notification.title'));
});

it('hides the complete action and the widget for exempt records', function () {
    config()->set('softrequired-for-filament.table_action.always', true);

    $draft = DraftModel::create(['title' => 'd', 'status' => 'draft', 'email' => null]);

    livewire(ListDraftModels::class)
        ->assertTableActionHidden('complete', $draft)
        ->filterTable('incomplete')
        ->assertCanNotSeeTableRecords([$draft]);

    expect(IncompleteRecordsWidget::canView())->toBeFalse();

    $live = DraftModel::create(['title' => 'l', 'status' => 'live', 'email' => null]);

    livewire(ListDraftModels::class)
        ->assertTableActionVisible('complete', $live);

    expect(IncompleteRecordsWidget::canView())->toBeTrue();

    livewire(IncompleteRecordsWidget::class)
        ->assertSee(DraftResource::getPluralModelLabel());
});
