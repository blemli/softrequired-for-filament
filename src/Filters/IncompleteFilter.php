<?php

namespace Blemli\SoftRequired\Filters;

use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class IncompleteFilter extends Filter
{
    public static function getDefaultName(): ?string
    {
        return 'incomplete';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(fn (): string => __('softrequired-for-filament::softrequired.filter.label'));

        $this->query(fn (Builder $query): Builder => $query->incomplete());
    }
}
