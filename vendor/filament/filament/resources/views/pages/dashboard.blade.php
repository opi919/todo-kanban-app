<x-filament-panels::page class="fi-dashboard-page">
    <span>welcome <strong>{{ auth()->user()->name }}</strong>, this is your
        <strong>{{ auth()->user()->role }}</strong> dashboard.</span>
    @if (method_exists($this, 'filtersForm'))
        {{ $this->filtersForm }}
    @endif

    <x-filament-widgets::widgets :columns="$this->getColumns()" :data="[...property_exists($this, 'filters') ? ['filters' => $this->filters] : [], ...$this->getWidgetData()]" :widgets="$this->getVisibleWidgets()" />
</x-filament-panels::page>
