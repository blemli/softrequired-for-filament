<?php

use Filament\Forms\Components\TextInput;

it('stores the marker with warn defaulting to true', function () {
    $field = TextInput::make('email')->softRequired();

    expect($field->isSoftRequired())->toBeTrue()
        ->and($field->shouldWarnWhenSoftRequired())->toBeTrue();
});

it('supports the silent warn: false variant', function () {
    $field = TextInput::make('phone')->softRequired(warn: false);

    expect($field->isSoftRequired())->toBeTrue()
        ->and($field->shouldWarnWhenSoftRequired())->toBeFalse();
});

it('supports closure conditions like required()', function () {
    expect(TextInput::make('a')->softRequired(fn (): bool => true)->isSoftRequired())->toBeTrue()
        ->and(TextInput::make('b')->softRequired(fn (): bool => false)->isSoftRequired())->toBeFalse();
});

it('leaves unmarked fields untouched', function () {
    $field = TextInput::make('anything');

    expect($field->isSoftRequired())->toBeFalse()
        ->and($field->hasMeta('softRequired'))->toBeFalse();
});

it('never blocks validation — the field stays optional', function () {
    expect(TextInput::make('email')->softRequired()->isRequired())->toBeFalse();
});

it('forces live(onBlur) so the warning updates while working', function () {
    expect(TextInput::make('a')->softRequired()->isLive())->toBeTrue();
});

it('leaves reactivity alone when the live config is off', function () {
    config()->set('softrequired-for-filament.live', false);

    $field = TextInput::make('a')->softRequired();

    expect((fn () => $this->isLive)->call($field))->toBeNull();
});
