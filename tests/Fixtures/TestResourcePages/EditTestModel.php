<?php

namespace Blemli\SoftRequired\Tests\Fixtures\TestResourcePages;

use Blemli\SoftRequired\Tests\Fixtures\TestResource;
use Filament\Resources\Pages\EditRecord;

class EditTestModel extends EditRecord
{
    protected static string $resource = TestResource::class;
}
