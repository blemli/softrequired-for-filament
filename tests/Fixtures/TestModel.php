<?php

namespace Blemli\SoftRequired\Tests\Fixtures;

use Blemli\SoftRequired\Concerns\Completable;
use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    use Completable;

    protected $table = 'test_models';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'tags' => 'array',
            'meta' => 'array',
        ];
    }
}
