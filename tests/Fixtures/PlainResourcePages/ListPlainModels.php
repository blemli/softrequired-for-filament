<?php

namespace Blemli\SoftRequired\Tests\Fixtures\PlainResourcePages;

use Blemli\SoftRequired\Tests\Fixtures\PlainResource;
use Filament\Resources\Pages\ListRecords;

class ListPlainModels extends ListRecords
{
    protected static string $resource = PlainResource::class;
}
