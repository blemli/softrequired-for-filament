<?php

namespace Blemli\SoftRequired\Tests\Fixtures;

use Blemli\SoftRequired\Concerns\Completable;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;

class StrictModel extends Model
{
    use Completable;

    protected $table = 'test_models';

    protected $guarded = [];

    protected array $completable = ['email'];

    public static function completionFormFields(array $attributes): ?array
    {
        return [
            TextInput::make('email')->label('E-Mail-Adresse'),
        ];
    }

    public function isAttributeComplete(string $attribute, mixed $value): bool
    {
        if ($attribute === 'email') {
            return str_contains((string) $value, '@');
        }

        return filled($value);
    }
}
