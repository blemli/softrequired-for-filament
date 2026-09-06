<?php

use Blemli\SoftRequired\Tests\Fixtures\ArrayModel;
use Blemli\SoftRequired\Tests\Fixtures\TestModel;
use Blemli\SoftRequired\Tests\Fixtures\TestResourcePages\ListTestModels;
use Blemli\SoftRequired\Tests\Fixtures\User;
use Blemli\SoftRequired\Widgets\IncompleteRecordsWidget;
use Blemli\SoftRequired\Widgets\ResourceIncompleteWidget;

use function Pest\Livewire\livewire;

beforeEach(function () {
    loginUser();
    bootPanel();
});

it('hides itself when there is nothing to complete', function () {
    TestModel::create([
        'title' => 'done',
        'email' => 'x@example.com',
        'phone' => '1',
        'nickname' => 'nick',
        'score' => 1,
    ]);

    expect(IncompleteRecordsWidget::canView())->toBeFalse();
});

it('hides itself when there are no records at all', function () {
    expect(IncompleteRecordsWidget::canView())->toBeFalse();
});

it('lists incomplete records with their missing field labels and a deep link', function () {
    TestModel::create(['title' => 'missing things']);

    expect(IncompleteRecordsWidget::canView())->toBeTrue();

    livewire(IncompleteRecordsWidget::class)
        ->assertSee(__('softrequired-for-filament::softrequired.widget.heading'))
        ->assertSee('Test Model #1')
        ->assertSee('Email')
        ->assertSee(__('softrequired-for-filament::softrequired.widget.show_all'))
        ->assertSee(__('softrequired-for-filament::softrequired.action.complete'))
        ->assertSeeHtml('filters%5Bincomplete%5D%5BisActive%5D');
});

it('completes a record inline using the actual resource form fields', function () {
    $record = TestModel::create(['title' => 'incomplete']);

    livewire(IncompleteRecordsWidget::class)
        ->callAction('complete', data: [
            'email' => 'now@example.com',
            'phone' => '123',
            'nickname' => 'nick',
            'score' => 3,
        ], arguments: ['model' => TestModel::class, 'key' => $record->getKey()])
        ->assertNotified(__('softrequired-for-filament::softrequired.action.completed'));

    expect($record->refresh()->email)->toBe('now@example.com')
        ->and($record->isComplete())->toBeTrue();
});

it('rejects completion for models it does not track', function () {
    $user = User::create([
        'name' => 'Victim',
        'email' => 'victim@example.com',
        'password' => bcrypt('secret'),
    ]);

    livewire(IncompleteRecordsWidget::class)
        ->mountAction('complete', arguments: [
            'model' => User::class,
            'key' => $user->getKey(),
        ]);

    expect($user->refresh()->name)->toBe('Victim');
});

it('can be disabled via config', function () {
    TestModel::create(['title' => 'missing']);

    config()->set('softrequired-for-filament.widget.enabled', false);

    expect(IncompleteRecordsWidget::canView())->toBeFalse();
});

it('tracks resource-less models listed in widget.models', function () {
    config()->set('softrequired-for-filament.widget.models', [ArrayModel::class]);

    // ArrayModel has $completable = ['tags'] and no Filament resource.
    ArrayModel::create(['title' => 'no tags yet', 'tags' => []]);

    expect(IncompleteRecordsWidget::canView())->toBeTrue();

    livewire(IncompleteRecordsWidget::class)
        ->assertSee(ArrayModel::completionLabel())
        ->assertSee('Array Model #1');
});

it('defaults resource-less labels to the headline plural of the class name', function () {
    expect(ArrayModel::completionLabel())->toBe('Array Models')
        ->and(ArrayModel::completionUrl())->toBeNull();
});

it('shows tabs when more than one model has incomplete records', function () {
    config()->set('softrequired-for-filament.widget.models', [ArrayModel::class]);

    TestModel::create(['title' => 'missing']);
    ArrayModel::create(['title' => 'no tags', 'tags' => []]);

    livewire(IncompleteRecordsWidget::class)
        ->assertSee(__('softrequired-for-filament::softrequired.widget.all'));
});

it('shows no tabs for a single model', function () {
    TestModel::create(['title' => 'missing']);

    livewire(IncompleteRecordsWidget::class)
        ->assertDontSee(__('softrequired-for-filament::softrequired.widget.all'));
});

it('truncates long lists and says how many more there are', function () {
    config()->set('softrequired-for-filament.widget.records_limit', 2);

    foreach (range(1, 5) as $i) {
        TestModel::create(['title' => "missing {$i}"]);
    }

    livewire(IncompleteRecordsWidget::class)
        ->assertSee(__('softrequired-for-filament::softrequired.widget.more', ['count' => 3]));
});

it('renders a resource widget above the list table while records are incomplete', function () {
    TestModel::create(['title' => 'missing']);

    livewire(ListTestModels::class)
        ->assertSeeLivewire(ResourceIncompleteWidget::class);
});

it('renders no resource widget when everything is complete', function () {
    TestModel::create([
        'title' => 'done',
        'email' => 'x@example.com',
        'phone' => '1',
        'nickname' => 'nick',
        'score' => 1,
    ]);

    livewire(ListTestModels::class)
        ->assertDontSeeLivewire(ResourceIncompleteWidget::class);
});

it('renders no resource widget when disabled via config', function () {
    TestModel::create(['title' => 'missing']);

    config()->set('softrequired-for-filament.resource_widget.enabled', false);

    livewire(ListTestModels::class)
        ->assertDontSeeLivewire(ResourceIncompleteWidget::class);
});
