<?php

use Blemli\SoftRequired\Tests\Fixtures\ArrayModel;
use Blemli\SoftRequired\Tests\Fixtures\StrictModel;
use Blemli\SoftRequired\Tests\Fixtures\TestModel;

function makeComplete(array $overrides = []): TestModel
{
    return TestModel::create([
        'title' => 'complete',
        'email' => 'x@example.com',
        'phone' => '123',
        'nickname' => 'nick',
        'score' => 5,
        ...$overrides,
    ]);
}

it('partitions records into incomplete and complete', function () {
    $nullEmail = makeComplete(['title' => 'null-email', 'email' => null]);
    $emptyEmail = makeComplete(['title' => 'empty-email', 'email' => '']);
    $complete = makeComplete();

    expect(TestModel::incomplete()->pluck('id')->all())->toBe([$nullEmail->id, $emptyEmail->id])
        ->and(TestModel::complete()->pluck('id')->all())->toBe([$complete->id]);
});

it('treats zero as a real value on numeric casts', function () {
    $zeroScore = makeComplete(['score' => 0]);

    expect(TestModel::incomplete()->pluck('id')->all())->not->toContain($zeroScore->id)
        ->and($zeroScore->isComplete())->toBeTrue();
});

it('treats an empty json array as incomplete on array casts', function () {
    $empty = ArrayModel::create(['title' => 'e', 'tags' => []]);
    $filled = ArrayModel::create(['title' => 'f', 'tags' => ['a']]);

    $incomplete = ArrayModel::incomplete()->pluck('id')->all();

    expect($incomplete)->toContain($empty->id)
        ->and($incomplete)->not->toContain($filled->id);
});

it('groups its clauses so other constraints stay intact', function () {
    makeComplete(['title' => 'a', 'email' => null]);
    makeComplete(['title' => 'b', 'email' => null]);

    expect(TestModel::where('title', 'a')->incomplete()->count())->toBe(1);
});

it('reports incomplete attributes with their labels', function () {
    $record = makeComplete(['email' => null, 'phone' => '']);

    expect($record->isIncomplete())->toBeTrue()
        ->and($record->getIncompleteAttributes())->toBe([
            'email' => 'Email',
            'phone' => 'Phone',
        ]);
});

it('is complete when every completable attribute is filled', function () {
    expect(makeComplete()->isComplete())->toBeTrue();
});

it('respects an isAttributeComplete override on the php side', function () {
    $record = new StrictModel(['email' => 'not-an-email']);

    expect($record->getIncompleteAttributes())->toHaveKey('email');

    $record->email = 'real@example.com';

    expect($record->isComplete())->toBeTrue();
});
