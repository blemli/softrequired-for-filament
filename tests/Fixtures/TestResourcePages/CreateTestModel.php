<?php

namespace Blemli\SoftRequired\Tests\Fixtures\TestResourcePages;

use Blemli\SoftRequired\Tests\Fixtures\TestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTestModel extends CreateRecord
{
    protected static string $resource = TestResource::class;
}
