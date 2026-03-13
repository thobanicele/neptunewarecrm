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
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
    $hasError = $errors->has($errorKey);
    $val = old($errorKey, $value);

    $resolvedAjaxUrl = $ajaxUrl;
    $resolvedAjaxShowUrl = $ajaxShowUrl;

    if ($resource) {
        $resolvedAjaxUrl = tenant_route('tenant.api.select2.search', ['resource' => $resource]);
        $resolvedAjaxShowUrl = tenant_route('tenant.api.select2.show', ['resource' => $resource, 'id' => '__ID__']);
    }
@endphp

<div class="mb-3">
    @if ($label)
        <label for="{{ $id }}" class="form-label">
            {{ $label }}
            @if ($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif

    <select
        {{ $attributes->except(['id', 'class'])->merge([
            'class' =>
                'form-select js-select2' .
                ($hasError ? ' is-invalid' : '') .
                ($attributes->get('class') ? ' ' . $attributes->get('class') : ''),
        ]) }}
        id="{{ $id }}" name="{{ $name }}{{ $multiple ? '[]' : '' }}"
        data-placeholder="{{ $placeholder }}" data-allow-clear="{{ $allowClear ? '1' : '0' }}"
        data-ajax-url="{{ $resolvedAjaxUrl }}" data-ajax-show-url="{{ $resolvedAjaxShowUrl }}"
        data-min-input="{{ $minInput }}" data-delay="{{ $delay }}" data-per-page="{{ $perPage }}"
        @if ($required) required @endif @if ($multiple) multiple @endif>
        @if (!$multiple)
            <option value=""></option>
        @endif

        @if (!$resolvedAjaxUrl)
            @foreach ($options as $optVal => $optLabel)
                <option value="{{ $optVal }}"
                    @if ($multiple) @selected(collect($val)->map(fn($x) => (string) $x)->contains((string) $optVal))
                    @else
                        @selected((string) $val === (string) $optVal) @endif>
                    {{ $optLabel }}
                </option>
            @endforeach
        @endif

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
                    const current = $el.val();
                    if (!current || !ajaxShowUrl) return;

                    const ids = isMultiple ?
                        (Array.isArray(current) ? current : [current]) :
                        [current];

                    const results = [];

                    for (const id of ids) {
                        if (!id) continue;

                        const url = String(ajaxShowUrl).replace('__ID__', encodeURIComponent(id));
                        const item = await fetchJson(url);

                        if (item && item.id != null) {
                            results.push(item);
                        }
                    }

                    if (!results.length) return;

                    results.forEach(item => {
                        const id = String(item.id);
                        let found = null;

                        $el.find('option').each(function() {
                            if (String(this.value) === id) {
                                found = this;
                                return false;
                            }
                        });

                        if (!found) {
                            $el.append(new Option(item.text, item.id, true, true));
                        } else {
                            found.text = item.text;
                            found.selected = true;
                        }
                    });
                }

                function initOne(el) {
                    if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) return;

                    const $el = window.jQuery(el);

                    const placeholder = $el.attr('data-placeholder') || 'Select...';
                    const allowClear = String($el.attr('data-allow-clear')) === '1';
                    const ajaxUrl = $el.attr('data-ajax-url') || '';
                    const ajaxShowUrl = $el.attr('data-ajax-show-url') || '';
                    const minInput = parseInt($el.attr('data-min-input') || '2', 10);
                    const delay = parseInt($el.attr('data-delay') || '250', 10);
                    const perPage = parseInt($el.attr('data-per-page') || '10', 10);

                    if ($el.data('select2')) {
                        try {
                            $el.select2('destroy');
                        } catch (e) {}
                    }

                    const config = {
                        width: '100%',
                        placeholder,
                        allowClear
                    };

                    if (ajaxUrl !== '') {
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
                                        results: data,
                                        pagination: {
                                            more: false
                                        }
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

                    if (ajaxUrl !== '' && ajaxShowUrl !== '' && $el.val()) {
                        prefillSelectedFromShowUrl($el, ajaxShowUrl)
                            .finally(() => $el.trigger('change'));
                    }
                }

                function collectTargets(container) {
                    if (!container) return [];
                    if (container.matches && container.matches('.js-select2')) return [container];
                    return Array.from(container.querySelectorAll('.js-select2'));
                }

                function boot(container) {
                    collectTargets(container || document).forEach(initOne);
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
