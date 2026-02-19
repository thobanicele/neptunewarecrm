@props(['title', 'icon', 'tone' => 'primary'])

<div class="nw-tile">
    <div class="nw-tile__head">
        <span class="nw-icon bg-{{ $tone }}-subtle text-{{ $tone }}">
            <i class="fa-solid {{ $icon }}"></i>
        </span>
        <span>{{ $title }}</span>
    </div>

    <div class="nw-links">
        {{ $slot }}
    </div>
</div>
