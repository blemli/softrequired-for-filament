<?php

namespace Blemli\SoftRequired\Tests\Fixtures\OptOutResourcePages;

use Blemli\SoftRequired\Tests\Fixtures\OptOutResource;
use Filament\Resources\Pages\ListRecords;

class ListOptOuts extends ListRecords
{
    protected static string $resource = OptOutResource::class;
}
