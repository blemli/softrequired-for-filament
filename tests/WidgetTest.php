<?php

use Blemli\SoftRequired\SoftRequired;
use Blemli\SoftRequired\Tests\Fixtures\ArrayModel;
use Blemli\SoftRequired\Tests\Fixtures\TestModel;
use Blemli\SoftRequired\Tests\Fixtures\TestResource;
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

    expect(IncompleteRecordsWidget::hasEntries())->toBeFalse();

    livewire(IncompleteRecordsWidget::class)
        ->assertDontSee(__('softrequired-for-filament::softrequired.widget.heading'));
});

it('hides itself when there are no records at all', function () {
    expect(IncompleteRecordsWidget::hasEntries())->toBeFalse();

    livewire(IncompleteRecordsWidget::class)
        ->assertDontSee(__('softrequired-for-filament::softrequired.widget.heading'));
});

/*
 * canView() is access, not data: Filament aborts a widget's hydrate with 403
 * when canView() fails, so the widget must survive every request after the
 * last record was completed.
 */
it('stays viewable while enabled, whatever the data', function () {
    expect(IncompleteRecordsWidget::canView())->toBeTrue();

    config()->set('softrequired-for-filament.widget.enabled', false);

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

it('takes its dashboard column span from the config', function () {
    expect((new IncompleteRecordsWidget)->getColumnSpan())->toBe('full');

    config()->set('softrequired-for-filament.widget.column_span', ['default' => 1, 'md' => 2]);

    expect((new IncompleteRecordsWidget)->getColumnSpan())->toBe(['default' => 1, 'md' => 2]);
});

/*
 * Completion has a signal. Every surface that saves through CompletionModal
 * announces it, and the widgets refresh on it — a record completed in a
 * list row or on a board card disappears from the inbox on the same page.
 */
it('announces a completion from the inbox and from the list action', function () {
    $record = TestModel::create(['title' => 'incomplete']);
    $data = ['email' => 'now@example.com', 'phone' => '123', 'nickname' => 'nick', 'score' => 3];

    livewire(IncompleteRecordsWidget::class)
        ->callAction('complete', data: $data, arguments: ['model' => TestModel::class, 'key' => $record->getKey()])
        ->assertDispatched(SoftRequired::COMPLETED_EVENT, model: TestModel::class, key: $record->getKey());

    $other = TestModel::create(['title' => 'incomplete too']);

    livewire(ListTestModels::class)
        ->filterTable('incomplete')
        ->callAction('completeRecord', data: $data, arguments: ['key' => $other->getKey()])
        ->assertDispatched(SoftRequired::COMPLETED_EVENT, model: TestModel::class, key: $other->getKey());
});

it('takes itself off the page once the last record is completed, without its parent', function () {
    $record = TestModel::create(['title' => 'incomplete']);
    $heading = __('softrequired-for-filament::softrequired.widget.heading');

    livewire(IncompleteRecordsWidget::class)
        ->assertSee($heading)
        ->callAction('complete', data: [
            'email' => 'now@example.com',
            'phone' => '123',
            'nickname' => 'nick',
            'score' => 3,
        ], arguments: ['model' => TestModel::class, 'key' => $record->getKey()])
        ->assertDontSee($heading);
});

it('catches up on a completion made elsewhere and on the generic refresh', function () {
    $record = TestModel::create(['title' => 'incomplete']);
    $heading = __('softrequired-for-filament::softrequired.widget.heading');

    $widget = livewire(IncompleteRecordsWidget::class)->assertSee($heading);

    $record->update(['email' => 'now@example.com', 'phone' => '123', 'nickname' => 'nick', 'score' => 3]);

    $widget
        ->assertSee($heading)
        ->dispatch(SoftRequired::COMPLETED_EVENT, model: TestModel::class, key: $record->getKey())
        ->assertDontSee($heading);

    TestModel::create(['title' => 'new and incomplete']);

    $widget
        ->assertDontSee($heading)
        ->dispatch('refresh')
        ->assertSee($heading);
});

it('keys the tabs by model, not by position', function () {
    config()->set('softrequired-for-filament.widget.models', [ArrayModel::class]);

    TestModel::create(['title' => 'missing']);
    ArrayModel::create(['title' => 'no tags', 'tags' => []]);

    livewire(IncompleteRecordsWidget::class)
        ->assertSeeHtml("tab === 'test-model'")
        ->assertSeeHtml("tab === 'array-model'")
        ->assertSeeHtml('wire:key="softrequired-inbox-test-model-array-model"')
        ->assertDontSeeHtml("tab === 'm0'");
});

it('drops the list stat as soon as the last record is completed', function () {
    $record = TestModel::create(['title' => 'missing']);

    $stat = livewire(ResourceIncompleteWidget::class, ['resource' => TestResource::class])
        ->assertSeeHtml('fi-wi-stats-overview')
        ->assertSee('1');

    $record->update(['email' => 'now@example.com', 'phone' => '123', 'nickname' => 'nick', 'score' => 3]);

    $stat
        ->dispatch(SoftRequired::COMPLETED_EVENT, model: TestModel::class, key: $record->getKey())
        ->assertDontSeeHtml('fi-wi-stats-overview');
});
