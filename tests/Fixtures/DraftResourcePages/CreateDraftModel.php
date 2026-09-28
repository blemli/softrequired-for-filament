<?php

namespace Blemli\SoftRequired\Tests\Fixtures\DraftResourcePages;

use Blemli\SoftRequired\Tests\Fixtures\DraftResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDraftModel extends CreateRecord
{
    protected static string $resource = DraftResource::class;
}
