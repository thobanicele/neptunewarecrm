@props([
  'title',
  'subtitle' => null,
])

<div class="container py-5" style="max-width: 980px;">
  <div class="mb-4">
    <h1 class="h3 mb-1">{{ $title }}</h1>
    @if($subtitle)
      <div class="text-muted">{{ $subtitle }}</div>
    @endif
  </div>

  <div class="card shadow-sm border-0">
    <div class="card-body p-4">
      {{ $slot }}
    </div>
  </div>
</div>