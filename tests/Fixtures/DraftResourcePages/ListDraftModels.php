<?php

namespace Blemli\SoftRequired\Tests\Fixtures\DraftResourcePages;

use Blemli\SoftRequired\Tests\Fixtures\DraftResource;
use Filament\Resources\Pages\ListRecords;

class ListDraftModels extends ListRecords
{
    protected static string $resource = DraftResource::class;
}
