<?php

namespace Blemli\SoftRequired\Tests\Fixtures\DraftResourcePages;

use Blemli\SoftRequired\Tests\Fixtures\DraftResource;
use Filament\Resources\Pages\EditRecord;

class EditDraftModel extends EditRecord
{
    protected static string $resource = DraftResource::class;
}
