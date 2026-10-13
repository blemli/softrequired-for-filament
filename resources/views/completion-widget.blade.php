@php
    $entries = $this->getEntries();
@endphp

@if ($entries === [])
    {{-- Everything complete: the inbox takes itself off the page, whoever placed it. --}}
    <div class="hidden"></div>
@else
<x-filament-widgets::widget>
    <x-filament::section :heading="$this->getHeading()">
        {{-- Keyed by the set of tabs: when one vanishes, Alpine starts over on «all»
             instead of keeping a tab that no longer exists. --}}
        <div
            x-data="{ tab: 'all' }"
            wire:key="softrequired-inbox-{{ implode('-', array_column($entries, 'tab')) }}"
            class="flex flex-col gap-4"
        >
            @if (count($entries) > 1)
                <x-filament::tabs>
                    <x-filament::tabs.item
                        alpine-active="tab === 'all'"
                        x-on:click="tab = 'all'"
                    >
                        {{ __('softrequired-for-filament::softrequired.widget.all') }}
                    </x-filament::tabs.item>

                    @foreach ($entries as $entry)
                        <x-filament::tabs.item
                            alpine-active="tab === '{{ $entry['tab'] }}'"
                            x-on:click="tab = '{{ $entry['tab'] }}'"
                            :badge="$entry['count']"
                            badge-color="warning"
                        >
                            {{ $entry['label'] }}
                        </x-filament::tabs.item>
                    @endforeach
                </x-filament::tabs>
            @endif

            @foreach ($entries as $modelClass => $entry)
                <div x-show="tab === 'all' || tab === '{{ $entry['tab'] }}'">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <h4 class="fi-section-header-heading text-sm">{{ $entry['label'] }}</h4>
                            <x-filament::badge color="warning">{{ $entry['count'] }}</x-filament::badge>
                        </div>

                        @if ($entry['url'])
                            <x-filament::link :href="$entry['url']" size="sm" color="gray">
                                {{ __('softrequired-for-filament::softrequired.widget.show_all') }}
                            </x-filament::link>
                        @endif
                    </div>

                    <ul class="mt-2 flex flex-col divide-y divide-gray-200 dark:divide-white/10">
                        @foreach ($entry['records'] as $record)
                            <li class="flex flex-wrap items-center justify-between gap-3 py-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-medium">{{ $record['title'] }}</span>

                                    @foreach ($record['missing'] as $missingLabel)
                                        <x-filament::badge color="warning" size="sm">{{ $missingLabel }}</x-filament::badge>
                                    @endforeach
                                </div>

                                <div class="flex items-center gap-4">
                                    {{ ($this->completeAction)(['model' => $modelClass, 'key' => $record['key']]) }}

                                    @if ($record['editUrl'])
                                        <x-filament::link :href="$record['editUrl']" size="sm" color="gray">
                                            {{ __('softrequired-for-filament::softrequired.action.open') }}
                                        </x-filament::link>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    @if ($entry['more'] > 0)
                        <div class="mt-1">
                            @if ($entry['url'])
                                <x-filament::link :href="$entry['url']" size="sm" color="warning">
                                    {{ __('softrequired-for-filament::softrequired.widget.more', ['count' => $entry['more']]) }}
                                </x-filament::link>
                            @else
                                <span class="text-sm fi-color-gray">
                                    {{ __('softrequired-for-filament::softrequired.widget.more', ['count' => $entry['more']]) }}
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-widgets::widget>
@endif
