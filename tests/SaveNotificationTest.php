<?php

use Blemli\SoftRequired\Tests\Fixtures\TestModel;
use Blemli\SoftRequired\Tests\Fixtures\TestResourcePages\CreateTestModel;
use Blemli\SoftRequired\Tests\Fixtures\TestResourcePages\EditTestModel;

use function Pest\Livewire\livewire;

beforeEach(function () {
    loginUser();
    bootPanel();
});

it('saves the record and warns about missing soft-required fields', function () {
    livewire(CreateTestModel::class)
        ->fillForm([
            'title' => 'Car',
            'nickname' => 'nick',
            'score' => 1,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified(__('softrequired-for-filament::softrequired.notification.title'));

    expect(TestModel::count())->toBe(1);
});

it('stays silent when every warn-enabled field is filled', function () {
    // phone and meta.color are warn: false — they may stay empty silently.
    livewire(CreateTestModel::class)
        ->fillForm([
            'title' => 'Car',
            'email' => 'x@example.com',
            'nickname' => 'nick',
            'score' => 1,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotNotified(__('softrequired-for-filament::softrequired.notification.title'));

    expect(TestModel::count())->toBe(1);
});

it('stays silent in mode none', function () {
    config()->set('softrequired-for-filament.on_incomplete_save', 'none');

    livewire(CreateTestModel::class)
        ->fillForm(['title' => 'Car'])
        ->call('create')
        ->assertNotNotified(__('softrequired-for-filament::softrequired.notification.title'));

    expect(TestModel::count())->toBe(1);
});

it('warns on the edit page too', function () {
    $record = TestModel::create(['title' => 'Car']);

    livewire(EditTestModel::class, ['record' => $record->getRouteKey()])
        ->fillForm(['title' => 'Car', 'nickname' => 'nick', 'score' => 2])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified(__('softrequired-for-filament::softrequired.notification.title'));
});

it('still enforces hard validation before anything else', function () {
    livewire(CreateTestModel::class)
        ->fillForm(['title' => null])
        ->call('create')
        ->assertHasFormErrors(['title' => 'required']);

    expect(TestModel::count())->toBe(0);
});

it('shows the field warning hint while soft-required fields are empty', function () {
    livewire(CreateTestModel::class)
        ->assertSee(__('softrequired-for-filament::softrequired.field.hint'));
});

it('hides the field warning once everything is filled', function () {
    livewire(CreateTestModel::class)
        ->fillForm([
            'title' => 'Car',
            'email' => 'x@example.com',
            'nickname' => 'nick',
            'score' => 1,
        ])
        ->assertDontSee(__('softrequired-for-filament::softrequired.field.hint'));
});
