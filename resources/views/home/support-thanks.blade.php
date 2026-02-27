@extends('layouts.frontend.main')

@section('title', 'Support')

@section('content')
    <section class="py-5 nw-section-soft">
        <div class="container" style="max-width: 920px;">
            <div class="card shadow-sm nw-card">
                <div class="card-body p-4 text-center">
                    <div class="mb-2">
                        <i data-feather="check-circle" style="width:56px;height:56px;"></i>
                    </div>
                    <h1 class="h3 fw-bold mb-2">Message sent</h1>
                    <p class="text-muted mb-4">
                        Thanks, we’ve received your support request. We’ll reply as soon as possible.
                    </p>

                    <div class="d-flex gap-2 justify-content-center flex-wrap">
                        <a href="{{ url('/') }}" class="btn btn-outline-secondary">Back home</a>
                        <a href="{{ route('support') }}" class="btn btn-primary">Send another message</a>
                    </div>

                    <div class="mt-3 small text-muted">
                        Tip: If you don’t hear back, check your spam folder or email us at <b>support@neptuneware.com</b>.
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
