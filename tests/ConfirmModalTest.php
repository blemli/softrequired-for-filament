<?php

use Blemli\SoftRequired\Tests\Fixtures\TestModel;
use Blemli\SoftRequired\Tests\Fixtures\TestResourcePages\CreateTestModel;
use Blemli\SoftRequired\Tests\Fixtures\TestResourcePages\EditTestModel;

use function Pest\Livewire\livewire;

beforeEach(function () {
    config()->set('softrequired-for-filament.on_incomplete_save', 'confirm');

    loginUser();
    bootPanel();
});

it('asks before saving an incomplete record and saves on confirm', function () {
    $page = livewire(CreateTestModel::class)
        ->fillForm(['title' => 'Car'])
        ->call('create');

    expect(TestModel::count())->toBe(0);

    $page->assertActionMounted('softRequiredConfirmSave');

    $page->callMountedAction();

    expect(TestModel::count())->toBe(1);
});

it('does not save when the modal is cancelled', function () {
    livewire(CreateTestModel::class)
        ->fillForm(['title' => 'Car'])
        ->call('create')
        ->assertActionMounted('softRequiredConfirmSave')
        ->unmountAction();

    expect(TestModel::count())->toBe(0);
});

it('saves directly when everything is filled', function () {
    livewire(CreateTestModel::class)
        ->fillForm([
            'title' => 'Car',
            'email' => 'x@example.com',
            'nickname' => 'nick',
            'score' => 1,
        ])
        ->call('create')
        ->assertActionNotMounted('softRequiredConfirmSave');

    expect(TestModel::count())->toBe(1);
});

it('asks on the edit page too', function () {
    $record = TestModel::create(['title' => 'Car']);

    $page = livewire(EditTestModel::class, ['record' => $record->getRouteKey()])
        ->fillForm(['title' => 'Renamed'])
        ->call('save');

    expect($record->refresh()->title)->toBe('Car');

    $page->assertActionMounted('softRequiredConfirmSave');

    $page->callMountedAction();

    expect($record->refresh()->title)->toBe('Renamed');
});
