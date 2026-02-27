@extends('layouts.frontend.main')

@section('title', 'Terms of Service')

@section('content')
    <section class="py-5 nw-section-soft">
        <div class="container" style="max-width: 980px;">

            <div class="text-center mb-4">
                <h1 class="h1 fw-bold mb-2">Terms of Service</h1>
                <p class="text-muted mb-0">Last updated: {{ now()->format('d M Y') }}</p>
            </div>

            <div class="card shadow-sm nw-card">
                <div class="card-body p-4">

                    <p class="text-muted">
                        These Terms of Service (“Terms”) govern your access to and use of NeptuneWare CRM (the “Service”),
                        operated by <strong>NeptuneWare Pty LTD</strong> (“NeptuneWare”, “we”, “us”, “our”).
                        By accessing or using the Service, you agree to these Terms.
                    </p>

                    <hr>

                    <h2 class="h5 fw-bold">1) Definitions</h2>
                    <ul>
                        <li><strong>“Account”</strong> means a user account created to access the Service.</li>
                        <li><strong>“Workspace”</strong> means a tenant-isolated environment under <code>/t/{tenant}</code>.
                        </li>
                        <li><strong>“Customer Content”</strong> means data you submit to the Service (contacts, deals,
                            quotes, invoices, files, etc.).</li>
                        <li><strong>“Subscription”</strong> means a paid plan or trial that enables additional features.
                        </li>
                    </ul>

                    <h2 class="h5 fw-bold mt-4">2) Eligibility & account security</h2>
                    <ul>
                        <li>You must provide accurate information when creating an account.</li>
                        <li>You are responsible for maintaining the confidentiality of your login credentials.</li>
                        <li>You are responsible for all activity that occurs under your account.</li>
                        <li>We may suspend accounts for abuse, fraud, or security reasons.</li>
                    </ul>

                    <h2 class="h5 fw-bold mt-4">3) Workspace ownership & roles</h2>
                    <ul>
                        <li>The person who creates a workspace is typically assigned the workspace owner role.</li>
                        <li>Workspace owners/admins control user access via roles and permissions.</li>
                        <li>Users within a workspace may be able to view, edit, export, or delete Customer Content depending
                            on their role.</li>
                    </ul>

                    <h2 class="h5 fw-bold mt-4">4) Customer Content</h2>
                    <ul>
                        <li>You retain ownership of Customer Content.</li>
                        <li>You grant NeptuneWare a limited license to host, process, transmit, and display Customer Content
                            solely to provide the Service.</li>
                        <li>You are responsible for the legality, accuracy, and appropriateness of Customer Content you
                            upload.</li>
                        <li>You must ensure you have the right to upload and process Customer Content (including personal
                            data).</li>
                    </ul>

                    <h2 class="h5 fw-bold mt-4">5) Acceptable use</h2>
                    <p>You agree not to:</p>
                    <ul>
                        <li>Use the Service for unlawful, fraudulent, or harmful activity.</li>
                        <li>Attempt to access another tenant’s data or bypass tenant isolation.</li>
                        <li>Probe, scan, or test vulnerabilities without permission.</li>
                        <li>Upload malware or attempt to disrupt Service performance.</li>
                        <li>Excessively automate requests, scrape, or overload the Service.</li>
                    </ul>

                    <h2 class="h5 fw-bold mt-4">6) Plans, trials, and billing</h2>
                    <ul>
                        <li>The Service may offer free plans, trials, and paid subscriptions with different limits and
                            features.</li>
                        <li>Paid subscriptions (if enabled) may renew automatically unless canceled before renewal.</li>
                        <li>Fees, plan limits, and included features are shown on our pricing page or in-app upgrade screen.
                        </li>
                        <li>Taxes may apply depending on your location.</li>
                    </ul>

                    <h2 class="h5 fw-bold mt-4">7) Emails & communications</h2>
                    <ul>
                        <li>We may send transactional emails (verification, password resets, billing notices, support
                            replies).</li>
                        <li>We may send product/service updates. Where required by law, you can opt out of non-essential
                            marketing emails.</li>
                        <li>We use anti-abuse measures (e.g., Cloudflare Turnstile) to prevent spam.</li>
                    </ul>

                    <h2 class="h5 fw-bold mt-4">8) Availability & changes</h2>
                    <ul>
                        <li>We aim to keep the Service available but do not guarantee uninterrupted operation.</li>
                        <li>We may change, suspend, or discontinue features as the Service evolves.</li>
                        <li>We may perform maintenance that temporarily affects availability.</li>
                    </ul>

                    <h2 class="h5 fw-bold mt-4">9) Third-party services</h2>
                    <p class="mb-0">
                        The Service may integrate with third-party providers (e.g., email delivery, storage, payment
                        processors).
                        Your use of third-party services may be subject to their own terms and policies.
                    </p>

                    <h2 class="h5 fw-bold mt-4">10) Intellectual property</h2>
                    <ul>
                        <li>The Service and its underlying software, design, and branding are owned by NeptuneWare Pty LTD
                            or its licensors.</li>
                        <li>You may not copy, modify, reverse engineer, or redistribute the Service except as permitted by
                            law.</li>
                    </ul>

                    <h2 class="h5 fw-bold mt-4">11) Termination</h2>
                    <ul>
                        <li>You may stop using the Service at any time.</li>
                        <li>We may suspend or terminate access if you violate these Terms or if required for security/legal
                            reasons.</li>
                        <li>Upon termination, your access to the Service may be removed. Customer Content retention/deletion
                            depends on your plan and settings.</li>
                    </ul>

                    <h2 class="h5 fw-bold mt-4">12) Disclaimers</h2>
                    <p>
                        The Service is provided on an “as is” and “as available” basis. To the maximum extent permitted by
                        law,
                        we disclaim all warranties, express or implied, including fitness for a particular purpose and
                        non-infringement.
                    </p>

                    <h2 class="h5 fw-bold mt-4">13) Limitation of liability</h2>
                    <p>
                        To the maximum extent permitted by law, NeptuneWare Pty LTD will not be liable for indirect,
                        incidental,
                        special, consequential, or punitive damages, or any loss of profits, revenue, data, or goodwill
                        arising from
                        your use of the Service.
                    </p>

                    <h2 class="h5 fw-bold mt-4">14) Indemnity</h2>
                    <p>
                        You agree to indemnify and hold harmless NeptuneWare Pty LTD from claims, damages, losses, and
                        expenses
                        arising from your Customer Content, your use of the Service, or your violation of these Terms.
                    </p>

                    <h2 class="h5 fw-bold mt-4">15) Governing law</h2>
                    <p>
                        These Terms are governed by the laws of <strong>South Africa</strong>, without regard to conflict of
                        law principles.
                    </p>

                    <h2 class="h5 fw-bold mt-4">16) Changes to these Terms</h2>
                    <p>
                        We may update these Terms from time to time. When we do, we will update the “Last updated” date
                        above.
                        Material changes may be communicated through the Service.
                    </p>

                    <h2 class="h5 fw-bold mt-4">17) Contact</h2>
                    <p class="mb-0">
                        For questions about these Terms, contact:
                        <br>
                        <strong>Email:</strong> support@neptuneware.com<br>
                        <strong>Phone:</strong> +27 73 685 8061<br>
                        <strong>Company:</strong> NeptuneWare Pty LTD
                    </p>

                </div>
            </div>

            <div class="text-center mt-3 small text-muted">
                Related: <a href="{{ route('privacy') }}">Privacy Policy</a> • <a href="{{ route('support') }}">Support</a>
            </div>

        </div>
    </section>
@endsection
