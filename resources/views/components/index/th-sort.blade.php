@props([
    'label',
    'key',
    'sort' => request('sort'),
    'dir' => request('dir', 'desc'),

    // ✅ optional: per-column default when switching to a NEW sort key
    // example: defaultDir="asc" for name columns
    'defaultDir' => 'desc',
])

@php
    $is = (string) $sort === (string) $key;

    // If currently sorted by this key, toggle.
    // If switching to a NEW key, use defaultDir.
    $nextDir = $is
        ? (strtolower((string) $dir) === 'asc'
            ? 'desc'
            : 'asc')
        : (strtolower((string) $defaultDir) === 'asc'
            ? 'asc'
            : 'desc');

    $query = array_merge(request()->query(), [
        'sort' => $key,
        'dir' => $nextDir,
        'page' => 1,
    ]);

    $href = url()->current() . (count($query) ? '?' . http_build_query($query) : '');

    $ariaSort = $is ? (strtolower((string) $dir) === 'asc' ? 'ascending' : 'descending') : 'none';
@endphp

<th {{ $attributes->merge(['aria-sort' => $ariaSort]) }}>
    <a class="text-decoration-none text-dark d-inline-flex align-items-center gap-1" href="{{ $href }}">
        <span>{{ $label }}</span>

        @if ($is)
            <span class="text-muted small">
                {!! strtolower((string) $dir) === 'asc' ? '&#9650;' : '&#9660;' !!}
            </span>
        @else
            <span class="text-muted small">&#8645;</span>
        @endif
    </a>
</th>
