<?php

namespace Blemli\SoftRequired\Tests\Fixtures;

use Blemli\SoftRequired\Concerns\Completable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Completeness that depends on the record: a draft may stay incomplete.
 */
class DraftModel extends Model
{
    use Completable;

    protected $table = 'draft_models';

    protected $guarded = [];

    public function isCompletionRequired(): bool
    {
        return $this->status !== 'draft';
    }

    public function scopeCompletionRequired(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->whereNull('status')->orWhere('status', '!=', 'draft');
        });
    }
}
