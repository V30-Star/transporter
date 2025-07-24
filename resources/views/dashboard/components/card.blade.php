@props(['stat', 'title', 'icon', 'trend' => null])

<div class="bg-white p-6 rounded-lg shadow">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500">{{ $title }}</p>
            <p class="text-2xl font-semibold text-gray-900">{{ $stat }}</p>
        </div>
        <div class="p-3 rounded-full bg-blue-50 text-blue-600">
            <x-dynamic-component :component="'heroicon-o-' . $icon" class="w-6 h-6" />
        </div>
    </div>

    @if ($trend)
        <div class="mt-4 flex items-center text-sm {{ $trend === 'up' ? 'text-green-600' : 'text-red-600' }}">
            @if ($trend === 'up')
                <x-heroicon-o-arrow-trending-up class="w-4 h-4 mr-1" />
            @else
                <x-heroicon-o-arrow-trending-down class="w-4 h-4 mr-1" />
            @endif
            <span>12% from last month</span>
        </div>
    @endif
</div>
