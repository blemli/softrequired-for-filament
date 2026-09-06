<?php

use Blemli\SoftRequired\Exceptions\IntrospectionFailedException;
use Blemli\SoftRequired\SoftRequired;
use Blemli\SoftRequired\Tests\Fixtures\OrphanModel;
use Blemli\SoftRequired\Tests\Fixtures\OverrideModel;
use Blemli\SoftRequired\Tests\Fixtures\StrictModel;
use Blemli\SoftRequired\Tests\Fixtures\TestModel;

it('collects soft-required fields from the resource form', function () {
    $fields = app(SoftRequired::class)->fieldsFor(TestModel::class);

    expect(array_keys($fields))->toBe(['email', 'phone', 'nickname', 'score'])
        ->and($fields['email'])->toBe(['label' => 'Email', 'warn' => true])
        ->and($fields['phone']['warn'])->toBeFalse()
        ->and($fields['nickname']['warn'])->toBeTrue();
});

it('finds fields nested in sections and grids but skips repeaters and dotted names', function () {
    $fields = app(SoftRequired::class)->fieldsFor(TestModel::class);

    // email lives in a Section, phone/nickname in a Grid — found.
    // sku lives in a Repeater, meta.color has a dotted name — excluded.
    expect($fields)->not->toHaveKeys(['sku', 'meta.color', 'title', 'items']);
});

it('lets a declared $completable array bypass introspection', function () {
    expect(OverrideModel::completableAttributes())->toBe(['email'])
        ->and(OverrideModel::completableFields()['email']['label'])->toBe('Email');
});

it('takes declared labels from completionFormFields when the model provides them', function () {
    expect(StrictModel::completableFields()['email']['label'])
        ->toBe('E-Mail-Adresse');
});

it('pulls rule-dependent fields into the completion form recursively and greys them out', function () {
    // score --required_with--> min_score --same--> nickname
    $fields = app(SoftRequired::class)->completionFormFields(TestModel::class, ['score']);

    $names = array_map(fn ($field): string => $field->getName(), $fields);

    expect($names)->toContain('score', 'min_score', 'nickname')
        ->not->toContain('email', 'phone');

    $byName = collect($fields)->keyBy(fn ($field): string => $field->getName());

    expect($byName['score']->isDisabled())->toBeFalse()
        ->and($byName['min_score']->isDisabled())->toBeTrue()
        ->and($byName['nickname']->isDisabled())->toBeTrue();
});

it('throws a helpful exception when no resource exists for the model', function () {
    app(SoftRequired::class)->fieldsFor(OrphanModel::class);
})->throws(IntrospectionFailedException::class, '$completable');

it('caches per model class until flushed', function () {
    $manager = app(SoftRequired::class);

    $first = $manager->fieldsFor(TestModel::class);
    $manager->flush();

    expect($manager->fieldsFor(TestModel::class))->toBe($first);
});
