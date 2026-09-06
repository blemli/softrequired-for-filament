<?php

namespace Blemli\SoftRequired;

use Blemli\SoftRequired\Widgets\IncompleteRecordsWidget;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Concerns\EvaluatesClosures;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Livewire\Livewire;

class SoftRequiredPlugin implements Plugin
{
    use EvaluatesClosures;

    protected string | Closure | null $onIncompleteSave = null;

    protected bool | Closure | null $tableFilter = null;

    protected bool | Closure | null $widget = null;

    protected bool | Closure | null $resourceWidget = null;

    /**
     * @var array<class-string> | Closure | null
     */
    protected array | Closure | null $except = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function current(): ?static
    {
        return rescue(fn (): ?Plugin => Filament::getCurrentPanel()?->getPlugin('softrequired-for-filament'), report: false); // @phpstan-ignore return.type
    }

    public function getId(): string
    {
        return 'softrequired-for-filament';
    }

    public function register(Panel $panel): void
    {
        $panel->widgets([IncompleteRecordsWidget::class]);

        $panel->renderHook(
            PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE,
            function (): ?View {
                $manager = app(SoftRequired::class);
                $page = Livewire::current();

                if (! $page instanceof ListRecords || ! $manager->isResourceWidgetEnabled()) {
                    return null;
                }

                $resource = $page::getResource();
                $model = $resource::getModel();

                if (! $manager->usesCompletable($model)) {
                    return null;
                }

                if (rescue(fn (): array => $manager->fieldsFor($model), [], report: false) === []) {
                    return null;
                }

                if (! $model::incomplete()->exists()) {
                    return null;
                }

                return view('softrequired-for-filament::resource-widget', ['resource' => $resource]);
            },
        );
    }

    public function boot(Panel $panel): void
    {
        //
    }

    /**
     * 'notify' saves and warns afterwards, 'confirm' asks before saving,
     * 'none' stays silent.
     */
    public function onIncompleteSave(string | Closure $mode): static
    {
        $this->onIncompleteSave = $mode;

        return $this;
    }

    public function getOnIncompleteSave(): string
    {
        return $this->evaluate($this->onIncompleteSave)
            ?? config('softrequired-for-filament.on_incomplete_save', 'notify');
    }

    public function tableFilter(bool | Closure $enabled = true): static
    {
        $this->tableFilter = $enabled;

        return $this;
    }

    public function hasTableFilter(): bool
    {
        return $this->evaluate($this->tableFilter)
            ?? (bool) config('softrequired-for-filament.table_filter.enabled', true);
    }

    public function widget(bool | Closure $enabled = true): static
    {
        $this->widget = $enabled;

        return $this;
    }

    public function hasWidget(): bool
    {
        return $this->evaluate($this->widget)
            ?? (bool) config('softrequired-for-filament.widget.enabled', true);
    }

    public function resourceWidget(bool | Closure $enabled = true): static
    {
        $this->resourceWidget = $enabled;

        return $this;
    }

    public function hasResourceWidget(): bool
    {
        return $this->evaluate($this->resourceWidget)
            ?? (bool) config('softrequired-for-filament.resource_widget.enabled', true);
    }

    /**
     * Resource classes that must not get the automatic Incomplete filter.
     *
     * @param  array<class-string> | Closure  $resources
     */
    public function except(array | Closure $resources): static
    {
        $this->except = $resources;

        return $this;
    }

    /**
     * @return array<class-string>
     */
    public function getExcept(): array
    {
        return $this->evaluate($this->except) ?? [];
    }
}
