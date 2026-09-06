<?php

namespace Blemli\SoftRequired\Tests\Fixtures;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TestResource extends Resource
{
    protected static ?string $model = TestModel::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required(),
            Section::make('Contact')->components([
                TextInput::make('email')->softRequired(),
            ]),
            Grid::make(2)->components([
                TextInput::make('phone')->softRequired(warn: false),
                TextInput::make('nickname')->softRequired(fn (): bool => true),
            ]),
            // Dependency chain for the completion modal: score's rule
            // references min_score, whose rule references nickname.
            TextInput::make('score')->numeric()->softRequired()->requiredWith('min_score'),
            TextInput::make('min_score')->numeric()->rule('same:nickname'),
            // Dotted name: form-only, must never reach the scopes.
            TextInput::make('meta.color')->softRequired(warn: false),
            // Repeater internals: form-only, must never reach the scopes.
            Repeater::make('items')
                ->components([
                    TextInput::make('sku')->softRequired(),
                ])
                ->default([])
                ->dehydrated(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title'),
            ])
            // The resource declares its own filters — the plugin's filter
            // must survive this reset.
            ->filters([
                Filter::make('titled')->query(fn (Builder $query): Builder => $query->whereNotNull('title')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => TestResourcePages\ListTestModels::route('/'),
            'create' => TestResourcePages\CreateTestModel::route('/create'),
            'edit' => TestResourcePages\EditTestModel::route('/{record}/edit'),
        ];
    }
}
