# softrequired

> Between required and optional lies reality.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/blemli/softrequired-for-filament.svg?style=flat-square)](https://packagist.org/packages/blemli/softrequired-for-filament) [![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/blemli/softrequired-for-filament/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/blemli/softrequired-for-filament/actions?query=workflow%3Atests+branch%3Amain) [![Total Downloads](https://img.shields.io/packagist/dt/blemli/softrequired-for-filament.svg?style=flat-square)](https://packagist.org/packages/blemli/softrequired-for-filament)

`required()` doesn't produce data — it produces `asdf`, `test@example.com`, and blocked users. Some fields are required *by the business* but unknowable *at entry time*. `->softRequired()` lets people save and keep working, then tracks what's missing: a warning on the field, a note on save (or a confirm modal), an `incomplete()` scope, an automatic table filter, and a dashboard completion widget that lists what's missing per record — and lets you **complete it right there**, in a modal built from your actual form fields. No migrations, no status column — completeness is derived from the data, so it can never lie. And `Customer::incomplete()` is a machine-readable backlog: hand it to a human — or an AI — to fill the gaps.

## Installation

```bash
composer require blemli/softrequired-for-filament
php artisan softrequired-for-filament:install
```

Register the plugin in your panel provider: `->plugin(SoftRequiredPlugin::make())`

## Usage

```php
// Form — declare once, right where required() would go:
TextInput::make('email')->softRequired(),
TextInput::make('company')->softRequired(warn: false), // counts, but never nags

// Model — add the trait; the fields are introspected from your form:
use Completable;

Customer::incomplete()->count();      // scope, derived from the form
$customer->getIncompleteAttributes(); // ['email' => 'Email']
```

Saving always stays possible: empty soft-required fields show a warning hint, saving pops a warning notification ("Saved — still missing: Email"), the resource table grows an **Incomplete** filter, and a dashboard widget counts incomplete records until there are none. Prefer asking first? `'on_incomplete_save' => 'confirm'` shows a "Save anyway?" modal instead. Ships in English and German.

## When NOT to use this

- **The whole record may be empty?** That's a draft — use a status field.
- **The value can be derived?** Compute it, don't nag.
- **You're marking most of the form?** You're modelling a workflow, not completeness.

`softRequired()` is only for fields that are business-required, unknowable at entry, and suppliable later — by a person, or by an AI working through `Model::incomplete()`.

## License

MIT © [blemli](https://github.com/blemli)
