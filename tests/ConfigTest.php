<?php

use Blemli\SoftRequired\Contracts\HasIncompleteSaveMode;
use Blemli\SoftRequired\SoftRequired;
use Blemli\SoftRequired\SoftRequiredPlugin;
use Illuminate\Support\Arr;

it('defaults to notify', function () {
    expect(app(SoftRequired::class)->incompleteSaveModeFor(new stdClass))->toBe('notify');
});

it('reads the mode from config', function () {
    config()->set('softrequired-for-filament.on_incomplete_save', 'confirm');

    expect(app(SoftRequired::class)->incompleteSaveModeFor(new stdClass))->toBe('confirm');
});

it('lets the plugin fluent override the config', function () {
    config()->set('softrequired-for-filament.on_incomplete_save', 'notify');

    bootPanel();
    SoftRequiredPlugin::current()->onIncompleteSave('confirm');

    expect(app(SoftRequired::class)->incompleteSaveModeFor(new stdClass))->toBe('confirm');
});

it('lets a page contract override everything', function () {
    config()->set('softrequired-for-filament.on_incomplete_save', 'confirm');

    $page = new class implements HasIncompleteSaveMode
    {
        public function incompleteSaveMode(): string
        {
            return 'none';
        }
    };

    expect(app(SoftRequired::class)->incompleteSaveModeFor($page))->toBe('none');
});

it('keeps the en and de translations in sync', function () {
    $en = require __DIR__ . '/../resources/lang/en/softrequired.php';
    $de = require __DIR__ . '/../resources/lang/de/softrequired.php';

    expect(array_keys(Arr::dot($de)))->toBe(array_keys(Arr::dot($en)));
});
