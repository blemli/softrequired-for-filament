<?php

namespace Blemli\SoftRequired\Tests\Fixtures;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OptOutResource extends Resource
{
    protected static ?string $model = TestModel::class;

    protected static ?string $slug = 'opt-outs';

    public static function form(Schema $schema): Schema
    {
        return TestResource::form($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title'),
            ])
            ->withoutIncompleteFilter();
    }

    public static function getPages(): array
    {
        return [
            'index' => OptOutResourcePages\ListOptOuts::route('/'),
        ];
    }
}
