<?php

namespace Blemli\SoftRequired\Tests\Fixtures;

use Blemli\SoftRequired\Concerns\Completable;
use Illuminate\Database\Eloquent\Model;

class OverrideModel extends Model
{
    use Completable;

    protected $table = 'test_models';

    protected $guarded = [];

    protected array $completable = ['email'];
}
