@props([
    'name',
    'label' => null,
    'value' => null, // selected value(s)
    'options' => [], // for static mode: [value => label]
    'placeholder' => 'Select...',
    'required' => false,
    'multiple' => false,
    'allowClear' => true,

    // CONFIG PATTERN MODE (recommended)
    'resource' => null, // e.g. "companies" -> uses tenant.api.select2.search/show routes

    // AJAX mode (optional overrides)
    'ajaxUrl' => null, // if set => AJAX search mode
    'ajaxShowUrl' => null, // optional show endpoint: .../__ID__
    'minInput' => 2,
    'delay' => 250,
    'perPage' => 10, // optional, controller supports per_page
])

@php
    $id = $attributes->get('id') ?? str_replace(['[', ']'], '_', $name) . '_' . uniqid();
    $errorKey = str_replace(['[', ']'], ['.', ''], $name); // shipping[country] -> shipping.country
    $hasError = $errors->has($errorKey);
    $val = old($errorKey, $value);

    // If resource is provided, auto-build URLs (config pattern)
    $resolvedAjaxUrl = $ajaxUrl;
    $resolvedAjaxShowUrl = $ajaxShowUrl;

    if ($resource) {
        // search endpoint
        $resolvedAjaxUrl = tenant_route('tenant.api.select2.search', ['resource' => $resource]);

        // show endpoint (we keep __ID__ placeholder and JS replaces it)
        $resolvedAjaxShowUrl = tenant_route('tenant.api.select2.show', ['resource' => $resource, 'id' => '__ID__']);
    }
@endphp

<div class="mb-3">
    @if ($label)
        <label for="{{ $id }}" class="form-label">
            {{ $label }} @if ($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif

    <select id="{{ $id }}" name="{{ $name }}{{ $multiple ? '[]' : '' }}"
        class="form-select js-select2 {{ $hasError ? 'is-invalid' : '' }}" data-placeholder="{{ $placeholder }}"
        data-allow-clear="{{ $allowClear ? '1' : '0' }}" data-ajax-url="{{ $resolvedAjaxUrl }}"
        data-ajax-show-url="{{ $resolvedAjaxShowUrl }}" data-min-input="{{ $minInput }}"
        data-delay="{{ $delay }}" data-per-page="{{ $perPage }}"
        @if ($required) required @endif @if ($multiple) multiple @endif
        {{ $attributes->except(['id']) }}>
        {{-- For single selects, keep a blank option so placeholder works --}}
        @if (!$multiple)
            <option value=""></option>
        @endif

        {{-- Static options mode --}}
        @if (!$resolvedAjaxUrl)
            @foreach ($options as $optVal => $optLabel)
                <option value="{{ $optVal }}"
                    @if ($multiple) @selected(collect($val)->map(fn($x) => (string) $x)->contains((string) $optVal))
                    @else
                        @selected((string) $val === (string) $optVal) @endif>
                    {{ $optLabel }}</option>
            @endforeach
        @endif

        {{-- AJAX mode: render selected IDs so form posts correctly.
             JS will replace labels using ajax-show-url. --}}
        @if ($resolvedAjaxUrl && !empty($val))
            @if ($multiple)
                @foreach ((array) $val as $v)
                    <option value="{{ $v }}" selected>{{ $v }}</option>
                @endforeach
            @else
                <option value="{{ $val }}" selected>{{ $val }}</option>
            @endif
        @endif
    </select>

    @if ($hasError)
        <div class="invalid-feedback d-block">
            {{ $errors->first($errorKey) }}
        </div>
    @endif
</div>

@once
    @push('scripts')
        <script>
            (function() {
                async function fetchJson(url) {
                    const res = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (!res.ok) return null;
                    return await res.json();
                }

                async function prefillSelectedFromShowUrl($el, ajaxShowUrl) {
                    const isMultiple = $el.prop('multiple');
                    let current = $el.val();
                    if (!current) return;

                    const ids = isMultiple ?
                        (Array.isArray(current) ? current : [current]) : [current];

                    if (!ajaxShowUrl) return;

                    const results = [];
                    for (const id of ids) {
                        if (!id) continue;
                        const url = String(ajaxShowUrl).replace('__ID__', encodeURIComponent(id));
                        const item = await fetchJson(url);
                        if (item && item.id != null) results.push(item);
                    }

                    if (!results.length) return;

                    results.forEach(item => {
                        const id = String(item.id);

                        let opt = null;
                        $el.find('option').each(function() {
                            if (String(this.value) === id) {
                                opt = this;
                                return false;
                            }
                        });

                        if (!opt) {
                            $el.append(new Option(item.text, item.id, true, true));
                        } else {
                            opt.text = item.text;
                            opt.selected = true;
                        }
                    });
                }

                function initSelect2(el) {
                    if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
                        return;
                    }

                    const $el = window.jQuery(el);
                    if ($el.data('select2')) return;

                    const placeholder = $el.data('placeholder') || 'Select...';
                    const allowClear = String($el.data('allow-clear')) === '1';
                    const ajaxUrl = $el.data('ajax-url') || null;
                    const ajaxShowUrl = $el.data('ajax-show-url') || null;
                    const minInput = parseInt($el.data('min-input') || '2', 10);
                    const delay = parseInt($el.data('delay') || '250', 10);
                    const perPage = parseInt($el.data('per-page') || '10', 10);

                    const config = {
                        width: '100%',
                        placeholder,
                        allowClear
                    };

                    if (ajaxUrl) {
                        config.ajax = {
                            url: ajaxUrl,
                            dataType: 'json',
                            delay,
                            data: function(params) {
                                return {
                                    q: params.term || '',
                                    page: params.page || 1,
                                    per_page: perPage
                                };
                            },
                            processResults: function(data, params) {
                                params.page = params.page || 1;

                                if (Array.isArray(data)) {
                                    return {
                                        results: data
                                    };
                                }

                                return {
                                    results: data.results || [],
                                    pagination: data.pagination || {
                                        more: false
                                    }
                                };
                            },
                            cache: true
                        };
                        config.minimumInputLength = minInput;
                    }

                    $el.select2(config);

                    if (ajaxUrl && ajaxShowUrl && $el.val()) {
                        prefillSelectedFromShowUrl($el, ajaxShowUrl)
                            .finally(() => $el.trigger('change'));
                    }
                }

                function boot(container) {
                    (container || document).querySelectorAll('.js-select2').forEach(el => initSelect2(el));
                }

                document.addEventListener('DOMContentLoaded', function() {
                    boot(document);
                });

                window.initSelect2 = function(container) {
                    boot(container || document);
                };
            })
            ();
        </script>
    @endpush
@endonce
