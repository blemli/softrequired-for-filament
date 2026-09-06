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
