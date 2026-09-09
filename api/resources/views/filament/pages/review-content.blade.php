<x-filament-panels::page>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
            <x-filament::badge :color="$review['status'] === 'Published' ? 'success' : 'gray'">
                {{ $review['status'] }}
            </x-filament::badge>
            <x-filament::badge :color="$review['is_visible'] ? 'success' : 'gray'">
                {{ $review['is_visible'] ? 'Visible' : 'Hidden' }}
            </x-filament::badge>
            @if ($review['key'] !== null)
                <span class="text-sm text-gray-500 dark:text-gray-400">Key: {{ $review['key'] }}</span>
            @endif
            @if ($review['position'] !== null)
                <span class="text-sm text-gray-500 dark:text-gray-400">Position: {{ $review['position'] }}</span>
            @endif
            <span class="text-sm text-gray-500 dark:text-gray-400">Published at: {{ $review['published_at'] ?? 'Never' }}</span>
        </div>

        @if ($editUrl !== null)
            <x-filament::button tag="a" :href="$editUrl" color="gray">
                Back to edit
            </x-filament::button>
        @endif
    </div>

    @if (! empty($review['dates']))
        <x-filament::section heading="Dates">
            <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @foreach ($review['dates'] as $date)
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $date['label'] }}</dt>
                        <dd class="text-sm text-gray-950 dark:text-white">{{ $date['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-filament::section>
    @endif

    @if (! empty($review['bilingual']))
        <x-filament::section heading="Content (Spanish and English)">
            <div class="space-y-6">
                @foreach ($review['bilingual'] as $pair)
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $pair['label'] }}</p>
                        <div class="mt-1 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="rounded-lg border border-gray-200 p-3 dark:border-white/10">
                                <p class="text-xs uppercase text-gray-400">Spanish</p>
                                <p class="whitespace-pre-line text-sm text-gray-950 dark:text-white">{{ $pair['es'] ?? '—' }}</p>
                            </div>
                            <div class="rounded-lg border border-gray-200 p-3 dark:border-white/10">
                                <p class="text-xs uppercase text-gray-400">English</p>
                                <p class="whitespace-pre-line text-sm text-gray-950 dark:text-white">{{ $pair['en'] ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif

    @if (! empty($review['fields']))
        <x-filament::section heading="Fields">
            <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @foreach ($review['fields'] as $field)
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $field['label'] }}</dt>
                        <dd class="text-sm text-gray-950 dark:text-white">{{ $field['value'] ?? '—' }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-filament::section>
    @endif

    @foreach ($review['relationships'] as $groupLabel => $items)
        <x-filament::section :heading="$groupLabel">
            @if (empty($items))
                <p class="text-sm text-gray-500 dark:text-gray-400">None.</p>
            @else
                <ul class="space-y-2">
                    @foreach ($items as $item)
                        <li class="text-sm text-gray-950 dark:text-white">
                            @if (array_key_exists('es', $item))
                                <span class="font-medium">{{ $item['label'] }}:</span>
                                ES — {{ $item['es'] ?? '—' }} / EN — {{ $item['en'] ?? '—' }}
                            @else
                                <span class="font-medium">{{ $item['label'] }}:</span> {{ $item['value'] }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-filament::section>
    @endforeach

    @if (! empty($review['assets']))
        <x-filament::section heading="Owned assets">
            <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @foreach ($review['assets'] as $asset)
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-white/10">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $asset['label'] }}</dt>
                        <dd class="text-sm text-gray-950 dark:text-white">
                            {{ $asset['exists'] ? 'Present' : 'Absent' }}
                            @if ($asset['exists'])
                                — {{ $asset['mime'] ?? 'unknown type' }}, {{ $asset['size'] !== null ? number_format($asset['size']).' bytes' : 'unknown size' }}
                            @endif
                        </dd>
                        @if ($asset['alt_es'] !== null || $asset['alt_en'] !== null)
                            <dd class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Alt (ES): {{ $asset['alt_es'] ?? '—' }} / Alt (EN): {{ $asset['alt_en'] ?? '—' }}
                            </dd>
                        @endif
                    </div>
                @endforeach
            </dl>
        </x-filament::section>
    @endif

    @if (! empty($review['dependencies']))
        <x-filament::section heading="Site dependencies (informative only)">
            <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">
                These never block this record's own publication.
            </p>
            <ul class="space-y-1">
                @foreach ($review['dependencies'] as $dependency)
                    <li class="text-sm text-gray-950 dark:text-white">
                        <span class="font-medium">{{ $dependency['label'] }}:</span> {{ $dependency['summary'] }}
                    </li>
                @endforeach
            </ul>
        </x-filament::section>
    @endif

    <x-filament::section heading="Publication readiness">
        @if (empty($review['issues']))
            <x-filament::badge color="success">No blocking issues — this record is currently publishable.</x-filament::badge>
        @else
            <x-filament::badge color="danger">
                {{ count($review['issues']) }} blocking {{ \Illuminate\Support\Str::plural('issue', count($review['issues'])) }}
            </x-filament::badge>
            <ul class="mt-3 space-y-1">
                @foreach ($review['issues'] as $issue)
                    <li class="font-mono text-sm text-gray-950 dark:text-white">
                        [{{ $issue['code'] }}] {{ $issue['path'] }}: {{ $issue['message'] }}
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-panels::page>
