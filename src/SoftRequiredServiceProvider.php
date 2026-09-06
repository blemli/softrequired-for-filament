<?php

namespace Blemli\SoftRequired;

use Blemli\SoftRequired\Commands\UninstallCommand;
use Blemli\SoftRequired\Livewire\SaveHook;
use Blemli\SoftRequired\Livewire\TableFilterHook;
use Blemli\SoftRequired\Testing\TestsSoftRequired;
use Closure;
use Filament\Forms\Components\Field;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SoftRequiredServiceProvider extends PackageServiceProvider
{
    public static string $name = 'softrequired-for-filament';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasCommands([UninstallCommand::class])
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command->publishConfigFile();
            });
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(SoftRequired::class);
    }

    public function packageBooted(): void
    {
        Livewire::componentHook(SaveHook::class);
        Livewire::componentHook(TableFilterHook::class);

        $this->registerFieldMacros();
        $this->registerTableMacros();

        Testable::mixin(new TestsSoftRequired);
    }

    /**
     * The third field state: ->softRequired() marks a field as required by
     * the business but unenforceable at entry time. Saving stays possible;
     * the field warns while empty (unless warn: false) and counts toward
     * the model's completeness.
     */
    protected function registerFieldMacros(): void
    {
        Field::macro('softRequired', function (bool | Closure $condition = true, bool | Closure $warn = true): Field {
            /** @var Field $this */
            $this->meta('softRequired', $condition);
            $this->meta('softRequiredWarn', $warn);

            $active = fn (Field $component): bool => $component->isSoftRequired()
                && $component->shouldWarnWhenSoftRequired()
                && blank($component->getState());

            $this->hint(fn (Field $component): ?string => $active($component)
                ? __('softrequired-for-filament::softrequired.field.hint')
                : null);
            $this->hintColor('warning');
            $this->hintIcon(fn (Field $component): ?Heroicon => $active($component)
                ? Heroicon::OutlinedExclamationTriangle
                : null);

            // Read the field's own live flag (macros are bound, so the
            // protected property is reachable) — isLive() would fall back
            // to the schema container, which does not exist at make() time.
            if (config('softrequired-for-filament.live', true) && $this->isLive === null) {
                $this->live(onBlur: true);
            }

            return $this;
        });

        Field::macro('isSoftRequired', function (): bool {
            /** @var Field $this */
            return (bool) $this->evaluate($this->getMeta('softRequired') ?? false);
        });

        Field::macro('shouldWarnWhenSoftRequired', function (): bool {
            /** @var Field $this */
            return (bool) $this->evaluate($this->getMeta('softRequiredWarn') ?? true);
        });
    }

    protected function registerTableMacros(): void
    {
        Table::macro('withoutIncompleteFilter', function (): Table {
            /** @var Table $this */
            app(SoftRequired::class)->disableFilterFor($this);

            return $this;
        });
    }
}
