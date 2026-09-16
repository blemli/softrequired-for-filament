<?php

use Blemli\SoftRequired\Tests\Fixtures\OptOutResourcePages\ListOptOuts;
use Blemli\SoftRequired\Tests\Fixtures\PlainResourcePages\ListPlainModels;
use Blemli\SoftRequired\Tests\Fixtures\TestModel;
use Blemli\SoftRequired\Tests\Fixtures\TestResource;
use Blemli\SoftRequired\Tests\Fixtures\TestResourcePages\ListTestModels;

use function Pest\Livewire\livewire;

beforeEach(function () {
    loginUser();
    bootPanel();
});

it('appends the incomplete filter although the resource declares its own filters', function () {
    $table = livewire(ListTestModels::class)->instance()->getTable();

    expect($table->getFilter('incomplete'))->not->toBeNull()
        ->and($table->getFilter('titled'))->not->toBeNull();
});

it('renders the filter into the filters form', function () {
    livewire(ListTestModels::class)
        ->assertSee(__('softrequired-for-filament::softrequired.filter.label'));
});

it('shows complete records until the filter is switched on', function () {
    $incomplete = TestModel::create(['title' => 'missing things']);
    $complete = TestModel::create(['title' => 'done', 'email' => 'x@example.com', 'phone' => '1', 'nickname' => 'nick', 'score' => 1]);

    // Filament treats a filter without a state entry as active — the hook
    // must write the inactive default for the filter it appends.
    livewire(ListTestModels::class)
        ->assertCanSeeTableRecords([$incomplete, $complete]);
});

it('narrows the table to incomplete records when active', function () {
    $incomplete = TestModel::create(['title' => 'missing things']);
    $complete = TestModel::create([
        'title' => 'done',
        'email' => 'x@example.com',
        'phone' => '1',
        'nickname' => 'nick',
        'score' => 1,
    ]);

    livewire(ListTestModels::class)
        ->filterTable('incomplete')
        ->assertCanSeeTableRecords([$incomplete])
        ->assertCanNotSeeTableRecords([$complete]);
});

it('stays away from resources without the Completable trait', function () {
    $table = livewire(ListPlainModels::class)->instance()->getTable();

    expect($table->getFilter('incomplete'))->toBeNull();
});

it('respects the withoutIncompleteFilter table macro', function () {
    $table = livewire(ListOptOuts::class)->instance()->getTable();

    expect($table->getFilter('incomplete'))->toBeNull();
});

it('respects the except config', function () {
    config()->set('softrequired-for-filament.table_filter.except', [TestResource::class]);

    $table = livewire(ListTestModels::class)->instance()->getTable();

    expect($table->getFilter('incomplete'))->toBeNull();
});

it('can be disabled entirely via config', function () {
    config()->set('softrequired-for-filament.table_filter.enabled', false);

    $table = livewire(ListTestModels::class)->instance()->getTable();

    expect($table->getFilter('incomplete'))->toBeNull();
});

it('offers the complete action on incomplete records only while the filter is active', function () {
    $incomplete = TestModel::create(['title' => 'missing things']);
    $complete = TestModel::create(['title' => 'done', 'email' => 'x@example.com', 'phone' => '1', 'nickname' => 'nick', 'score' => 1]);

    livewire(ListTestModels::class)
        ->assertTableActionHidden('complete', $incomplete)
        ->assertTableActionHidden('complete', $complete)
        ->filterTable('incomplete')
        ->assertTableActionVisible('complete', $incomplete)
        ->resetTableFilters()
        ->assertTableActionHidden('complete', $incomplete);
});

it('always offers the complete action when the plugin says so, but never on complete records', function () {
    config()->set('softrequired-for-filament.table_action.always', true);
    $incomplete = TestModel::create(['title' => 'missing things']);
    $complete = TestModel::create(['title' => 'done', 'email' => 'x@example.com', 'phone' => '1', 'nickname' => 'nick', 'score' => 1]);

    livewire(ListTestModels::class)
        ->assertTableActionVisible('complete', $incomplete)
        ->assertTableActionHidden('complete', $complete);
});

it('completes a record from the list and writes only the missing attributes', function () {
    $record = TestModel::create(['title' => 'missing things']);

    // The row/card button mounts the page action with the record key.
    livewire(ListTestModels::class)
        ->filterTable('incomplete')
        ->assertTableActionVisible('complete', $record)
        ->callAction('completeRecord', data: ['email' => 'done@example.com', 'title' => 'renamed'], arguments: ['key' => $record->getKey()])
        ->assertHasNoActionErrors()
        ->assertNotified(__('softrequired-for-filament::softrequired.action.completed'));

    expect($record->refresh()->email)->toBe('done@example.com')
        ->and($record->title)->toBe('missing things');
});

it('refuses to complete records the user may not edit or that are not completable', function () {
    $record = TestModel::create(['title' => 'missing things']);

    livewire(ListTestModels::class)
        ->callAction('completeRecord', data: ['email' => 'x@example.com'], arguments: ['key' => 999]);

    expect($record->refresh()->email)->toBeNull();
});
