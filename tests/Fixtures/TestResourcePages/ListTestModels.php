<?php

namespace Blemli\SoftRequired\Tests\Fixtures\TestResourcePages;

use Blemli\SoftRequired\Tests\Fixtures\TestResource;
use Filament\Resources\Pages\ListRecords;

class ListTestModels extends ListRecords
{
    protected static string $resource = TestResource::class;
}
