<?php

namespace Blemli\SoftRequired\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

// Deliberately not Completable.
class PlainModel extends Model
{
    protected $table = 'plain_models';

    protected $guarded = [];
}
