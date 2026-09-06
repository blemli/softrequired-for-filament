<?php

namespace Blemli\SoftRequired\Tests\Fixtures;

use Blemli\SoftRequired\Concerns\Completable;
use Illuminate\Database\Eloquent\Model;

// No Filament resource exists for this model — introspection must fail
// with a helpful exception.
class OrphanModel extends Model
{
    use Completable;

    protected $table = 'test_models';

    protected $guarded = [];
}
