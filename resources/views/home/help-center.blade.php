@extends('layouts.frontend.main')

@section('title', 'Help Center')

@section('content')
    <section class="py-5 nw-section-soft">
        <div class="container" style="max-width: 980px;">

            <div class="text-center mb-4">
                <h1 class="h1 fw-bold mb-2">Help Center</h1>
                <p class="text-muted mb-0">Quick answers, guides, and common troubleshooting.</p>
            </div>

            <style>
                /* Clean FAQ accordion */
                .nw-faq .accordion-button {
                    background: transparent;
                    box-shadow: none !important;
                    padding: 14px 0;
                    font-weight: 600;
                    color: #111;
                }

                .nw-faq .accordion-button:focus {
                    box-shadow: none !important;
                }

                .nw-faq .accordion-button::after {
                    display: none;
                    /* remove default caret */
                }

                .nw-faq .accordion-body {
                    padding: 0 0 14px 0;
                }

                .nw-faq-divider {
                    border-bottom: 1px solid rgba(0, 0, 0, .08);
                }

                .nw-faq-icon {
                    width: 28px;
                    height: 28px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    border: 1px solid rgba(0, 0, 0, .12);
                    border-radius: 999px;
                    font-weight: 700;
                    line-height: 1;
                    color: #111;
                    flex: 0 0 auto;
                }
            </style>

            <div class="row g-4">
                {{-- Left: Quick links --}}
                <div class="col-lg-4">
                    <div class="card shadow-sm nw-card">
                        <div class="card-body">
                            <div class="fw-bold mb-2">Quick links</div>
                            <div class="text-muted small mb-3">Start here if you’re new.</div>

                            <div class="d-grid gap-2">
                                <a href="#getting-started" class="btn btn-outline-secondary">Getting started</a>
                                <a href="#faq" class="btn btn-outline-secondary">Frequently asked questions</a>
                                <a href="#troubleshooting" class="btn btn-outline-secondary">Troubleshooting</a>
                            </div>

                            <hr>

                            <div class="fw-bold mb-2">Need help?</div>
                            <div class="text-muted small mb-3">If you can’t find an answer, contact us.</div>
                            <a href="{{ route('support') }}" class="btn btn-primary w-100">Contact Support</a>

                            <div class="mt-3 small text-muted">
                                Tip: include your workspace URL (<code>/t/yourworkspace</code>) and screenshots.
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right: Content --}}
                <div class="col-lg-8">

                    {{-- Getting Started --}}
                    <div class="card shadow-sm nw-card mb-3" id="getting-started">
                        <div class="card-body">
                            <div class="fw-bold mb-2">Getting started</div>
                            <div class="text-muted">
                                Create a workspace, invite your team, and start capturing leads. Your workspace URL will be
                                <code>/t/yourworkspace</code>.
                            </div>
                        </div>
                    </div>

                    {{-- FAQ Accordion --}}
                    <div class="card shadow-sm nw-card mb-3" id="faq">
                        <div class="card-body">
                            <div class="fw-bold mb-3">Frequently asked questions</div>

                            @php
                                $faqs = [
                                    [
                                        'q' => 'How do I create a workspace?',
                                        'a' =>
                                            'After registering, you’ll be guided through onboarding to create your workspace. Your workspace URL will be /t/yourworkspace.',
                                    ],
                                    [
                                        'q' => 'How do I invite users and set roles?',
                                        'a' =>
                                            'Go to Settings → Users, invite team members by email, then assign roles like tenant_admin, sales, finance, or viewer.',
                                    ],
                                    [
                                        'q' => 'Why can’t a user see a menu item or page?',
                                        'a' =>
                                            'It’s usually permissions or plan features. Confirm the user role, the tenant plan features (exports/statements/branding), and that the user is active and verified.',
                                    ],
                                    [
                                        'q' => 'What’s the sales flow in NeptuneWare?',
                                        'a' =>
                                            'Typical flow: Leads → Contacts → Companies → Deals → Quotes → Sales Orders → Invoices → Payments/Credits.',
                                    ],
                                    [
                                        'q' => 'How do I qualify a lead?',
                                        'a' =>
                                            'Open the lead and choose Qualify. NeptuneWare converts it into a contact, optionally attaches/creates a company, and you can create a deal.',
                                    ],
                                    [
                                        'q' => 'How do PDFs work (quotes, invoices, statements)?',
                                        'a' =>
                                            'PDFs are generated from document data and your workspace branding. Logos are optimized to keep PDFs fast and reliable.',
                                    ],
                                    [
                                        'q' => 'How do I set my company logo/branding?',
                                        'a' =>
                                            'Go to Settings → Branding and upload your logo. Recommended: transparent PNG/WebP around 512×512.',
                                    ],
                                    [
                                        'q' => 'How do payments and allocations work?',
                                        'a' =>
                                            'Payments can be allocated to invoices. Credit notes can also be applied. Balances and invoice statuses update automatically.',
                                    ],
                                    [
                                        'q' => 'I’m not receiving verification or password reset emails.',
                                        'a' =>
                                            'Check spam/junk first. Confirm the email is correct. If needed, contact support with your email and workspace URL.',
                                    ],
                                    [
                                        'q' => 'Something looks wrong after an update (missing menus/data).',
                                        'a' =>
                                            'Log out/in first. If it persists, we may need to clear caches or resync permissions—contact support with details.',
                                    ],
                                ];
                            @endphp

                            <div class="accordion nw-faq" id="helpAccordion">
                                @foreach ($faqs as $i => $f)
                                    <div class="accordion-item border-0">
                                        <h2 class="accordion-header" id="h{{ $i }}">
                                            <button class="accordion-button collapsed d-flex align-items-center"
                                                type="button" data-bs-toggle="collapse"
                                                data-bs-target="#c{{ $i }}" aria-expanded="false"
                                                aria-controls="c{{ $i }}">
                                                <span class="me-3">{{ $f['q'] }}</span>
                                                <span class="ms-auto nw-faq-icon" aria-hidden="true">+</span>
                                            </button>
                                        </h2>

                                        <div id="c{{ $i }}" class="accordion-collapse collapse"
                                            aria-labelledby="h{{ $i }}" data-bs-parent="#helpAccordion">
                                            <div class="accordion-body pt-0">
                                                <div class="text-muted">{{ $f['a'] }}</div>
                                            </div>
                                        </div>

                                        @if (!$loop->last)
                                            <div class="nw-faq-divider"></div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                        </div>
                    </div>

                    {{-- Troubleshooting --}}
                    <div class="card shadow-sm nw-card mb-3" id="troubleshooting">
                        <div class="card-body">
                            <div class="fw-bold mb-2">Troubleshooting</div>
                            <ul class="mb-0 text-muted">
                                <li>Not seeing a menu item? Check your role and plan features.</li>
                                <li>PDF errors? Try re-uploading a smaller logo (we optimize it automatically).</li>
                                <li>Email issues? Check spam and confirm SMTP settings.</li>
                            </ul>
                        </div>
                    </div>

                    {{-- Closing callout --}}
                    <div class="card shadow-sm nw-card">
                        <div class="card-body d-flex flex-wrap gap-2 justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold">Still stuck?</div>
                                <div class="text-muted small">We’re happy to help — send us a message.</div>
                            </div>
                            <a href="{{ route('support') }}" class="btn btn-primary">Contact Support</a>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('shown.bs.collapse', function(e) {
            const btn = document.querySelector('[data-bs-target="#' + e.target.id + '"]');
            if (!btn) return;
            const icon = btn.querySelector('.nw-faq-icon');
            if (icon) icon.textContent = '–';
        });

        document.addEventListener('hidden.bs.collapse', function(e) {
            const btn = document.querySelector('[data-bs-target="#' + e.target.id + '"]');
            if (!btn) return;
            const icon = btn.querySelector('.nw-faq-icon');
            if (icon) icon.textContent = '+';
        });
    </script>
@endpush
