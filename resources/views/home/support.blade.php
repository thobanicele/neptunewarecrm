@extends('layouts.frontend.main')

@section('title', 'Support')

@section('content')
    <x-home.page title="Support" subtitle="Contact us and we’ll help you get sorted.">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row g-4">
            <div class="col-lg-7">
                <form id="supportForm" method="POST" action="{{ route('support.send') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Your name</label>
                        <input class="form-control @error('name') is-invalid @enderror" name="name"
                            value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input class="form-control @error('email') is-invalid @enderror" type="email" name="email"
                            value="{{ old('email') }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input class="form-control @error('subject') is-invalid @enderror" name="subject"
                            value="{{ old('subject') }}" required>
                        @error('subject')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea class="form-control @error('message') is-invalid @enderror" name="message" rows="6" required>{{ old('message') }}</textarea>
                        @error('message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    {{-- Turnstile --}}
                    <div class="mb-3">
                        <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}"></div>

                        @error('cf-turnstile-response')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <button class="btn btn-primary">Send message</button>
                </form>
            </div>

            <div class="col-lg-5">
                <div class="p-3 rounded border bg-light">
                    <div class="fw-semibold mb-1">Support contacts</div>
                    <div class="text-muted small mb-3">Mon–Fri, 08:00–17:00 (SAST)</div>

                    <div class="mb-2">
                        <div class="text-muted small">Phone</div>
                        <div class="fw-semibold">+27 73 685 8061</div>
                    </div>

                    <div class="mb-2">
                        <div class="text-muted small">Email</div>
                        <div class="fw-semibold">support@neptuneware.com</div>
                    </div>

                    <hr>

                    <div class="text-muted small">
                        Include your workspace URL (/t/yourworkspace) if possible.
                    </div>
                </div>
            </div>
        </div>
    </x-home.page>
@endsection
@push('scripts')
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <script>
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (!form.matches('#supportForm')) return;

            const btn = form.querySelector('button[type="submit"]');
            if (!btn) return;

            btn.disabled = true;
            btn.dataset.originalText = btn.innerHTML;
            btn.innerHTML = 'Sending...';
        }, true);
    </script>
@endpush
