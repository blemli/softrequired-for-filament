<?php

namespace Blemli\SoftRequired\Widgets;

use Blemli\SoftRequired\SoftRequired;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;

/**
 * Single-resource incomplete count, rendered above the resource's list
 * table by the plugin's render hook (or used manually as a header widget).
 */
class ResourceIncompleteWidget extends StatsOverviewWidget
{
    /**
     * @var class-string|null
     */
    public ?string $resource = null;

    /**
     * @return array<string, string>
     */
    protected function getListeners(): array
    {
        return [
            ...parent::getListeners(),
            SoftRequired::COMPLETED_EVENT => '$refresh',
            'refresh' => '$refresh',
        ];
    }

    /**
     * The render hook only decides on a full page load; once the last
     * record is completed in place, the stat takes itself off the page.
     */
    public function render(): View
    {
        if ($this->getCachedStats() === []) {
            return view('softrequired-for-filament::empty');
        }

        return parent::render();
    }

    protected function getStats(): array
    {
        if ($this->resource === null) {
            return [];
        }

        $model = $this->resource::getModel();
        $count = $model::incomplete()->count();

        if ($count === 0) {
            return [];
        }

        $label = $this->resource::getPluralModelLabel();
        $url = app(SoftRequired::class)->filteredIndexUrl($this->resource);

        return [
            Stat::make($label, $count)
                ->color('warning')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->description(new HtmlString(
                    '<a href="' . e($url) . '">'
                    . e(__('softrequired-for-filament::softrequired.widget.stat_description', ['label' => $label]))
                    . '</a>'
                )),
        ];
    }
}
