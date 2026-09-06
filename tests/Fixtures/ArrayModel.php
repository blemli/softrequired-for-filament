<?php

namespace Blemli\SoftRequired\Tests\Fixtures;

use Blemli\SoftRequired\Concerns\Completable;
use Illuminate\Database\Eloquent\Model;

class ArrayModel extends Model
{
    use Completable;

    protected $table = 'test_models';

    protected $guarded = [];

    protected array $completable = ['tags'];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
        ];
    }
}
