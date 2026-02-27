@extends('layouts.frontend.main')

@section('title', 'Privacy Policy')

@section('content')
    <section class="py-5 nw-section-soft">
        <div class="container" style="max-width: 980px;">

            <div class="text-center mb-4">
                <h1 class="h1 fw-bold mb-2">Privacy Policy</h1>
                <p class="text-muted mb-0">Last updated: {{ now()->format('d M Y') }}</p>
            </div>

            <div class="card shadow-sm nw-card">
                <div class="card-body p-4">

                    <p class="text-muted">
                        This Privacy Policy explains how <strong>NeptuneWare CRM</strong> (“NeptuneWare”, “we”, “us”, “our”)
                        collects, uses, discloses, and protects personal information when you use our website and services
                        (the “Service”).
                    </p>

                    <hr>

                    <h2 class="h5 fw-bold">1) Who we are</h2>
                    <p>
                        NeptuneWare CRM is a multi-tenant customer relationship management platform operated by
                        <strong>NeptuneWare Pty LTD</strong>.
                    </p>
                    <p>
                        The entity responsible for your data depends on your relationship with the Service:
                    </p>
                    <ul>
                        <li>
                            <strong>Workspace Owners (Customers):</strong> If your company created a NeptuneWare workspace,
                            your company is the <strong>Data Controller</strong> for the customer data you upload.
                        </li>
                        <li>
                            <strong>NeptuneWare Pty LTD:</strong> NeptuneWare acts as a <strong>Data Processor</strong> for
                            customer
                            content in your workspace and as a <strong>Controller</strong> for account, billing, and website
                            usage data we collect directly.
                        </li>
                    </ul>

                    <h2 class="h5 fw-bold mt-4">2) Information we collect</h2>
                    <p>We collect information in three main categories:</p>

                    <h3 class="h6 fw-bold mt-3">A. Account & workspace information</h3>
                    <ul>
                        <li>Name, email address, password (stored as a secure hash)</li>
                        <li>Workspace name and workspace URL (tenant subdomain)</li>
                        <li>User roles, permissions, and workspace membership</li>
                    </ul>

                    <h3 class="h6 fw-bold mt-3">B. Customer content (uploaded/entered by you)</h3>
                    <ul>
                        <li>Contacts, companies, addresses, notes, deals, and activities</li>
                        <li>Quotes, invoices, sales orders, payments, credit notes, statements</li>
                        <li>Attachments and branding assets (e.g., logos) you upload</li>
                    </ul>
                    <p class="text-muted small mb-0">
                        You control what customer content is entered into your workspace. This data belongs to your
                        workspace and is isolated from other tenants.
                    </p>

                    <h3 class="h6 fw-bold mt-3">C. Usage & technical data</h3>
                    <ul>
                        <li>IP address, device/browser information, timestamps, and diagnostic logs</li>
                        <li>Pages visited, feature usage, and performance analytics (where enabled)</li>
                        <li>Security-related events (e.g., sign-in attempts, verification, abuse prevention)</li>
                    </ul>

                    <h2 class="h5 fw-bold mt-4">3) How we use information</h2>
                    <ul>
                        <li>Provide and operate the Service, including tenant isolation and feature access</li>
                        <li>Authenticate users, prevent abuse, and secure accounts</li>
                        <li>Process support requests and communicate with you</li>
                        <li>Send service emails (verification, password reset, billing notices)</li>
                        <li>Maintain, monitor, and improve the Service (debugging, reliability, performance)</li>
                        <li>Comply with legal obligations and enforce our terms</li>
                    </ul>

                    <h2 class="h5 fw-bold mt-4">4) Legal basis (where applicable)</h2>
                    <p>
                        Depending on the context, we process personal information based on:
                        <strong>contract performance</strong> (to deliver the Service), <strong>legitimate
                            interests</strong>
                        (security and improvement), <strong>consent</strong> (where required), and/or
                        <strong>legal obligations</strong>.
                    </p>

                    <h2 class="h5 fw-bold mt-4">5) Sharing and disclosure</h2>
                    <p>We do not sell your personal information. We may share data with:</p>
                    <ul>
                        <li>
                            <strong>Service providers</strong> that help us run NeptuneWare (hosting, storage, email
                            delivery,
                            analytics, payment processing). They are permitted to process data only to provide services to
                            us.
                        </li>
                        <li>
                            <strong>Workspace administrators</strong> within your tenant (who can view/manage tenant data
                            based
                            on roles and permissions).
                        </li>
                        <li>
                            <strong>Legal and compliance</strong> where required by law, to protect rights, safety, or
                            prevent fraud.
                        </li>
                    </ul>

                    <h2 class="h5 fw-bold mt-4">6) Data storage, hosting & international transfers</h2>
                    <p>
                        NeptuneWare may store data in data centers located in different regions depending on infrastructure
                        providers. If data is transferred across borders, we use appropriate safeguards where required.
                    </p>

                    <h2 class="h5 fw-bold mt-4">7) Security</h2>
                    <p>
                        We implement technical and organizational measures designed to protect data, including access
                        controls,
                        tenant isolation, encrypted transmission (HTTPS), and monitoring. No method of transmission or
                        storage
                        is 100% secure, but we work to protect your information.
                    </p>

                    <h2 class="h5 fw-bold mt-4">8) Data retention</h2>
                    <p>
                        We retain personal information only as long as needed to provide the Service, comply with legal
                        obligations,
                        resolve disputes, and enforce agreements. Workspace owners can delete customer content within their
                        tenant.
                    </p>

                    <h2 class="h5 fw-bold mt-4">9) Your rights</h2>
                    <p>
                        Depending on your location, you may have rights to access, correct, delete, or object to processing
                        of
                        your personal information. If you are a user in a tenant workspace, please contact your workspace
                        admin first,
                        as they control workspace customer data.
                    </p>

                    <h2 class="h5 fw-bold mt-4">10) Cookies & tracking</h2>
                    <p>
                        We use cookies and similar technologies for authentication, session management, and security.
                        We may also use limited analytics to understand usage and improve the Service.
                    </p>

                    <h2 class="h5 fw-bold mt-4">11) Turnstile / anti-abuse</h2>
                    <p>
                        We use Cloudflare Turnstile to prevent spam and automated abuse on certain forms.
                        Turnstile may collect and process device and interaction signals to determine whether a request is
                        legitimate.
                    </p>

                    <h2 class="h5 fw-bold mt-4">12) Children’s privacy</h2>
                    <p>
                        NeptuneWare is not intended for children and we do not knowingly collect personal information from
                        children.
                    </p>

                    <h2 class="h5 fw-bold mt-4">13) Changes to this policy</h2>
                    <p>
                        We may update this Privacy Policy from time to time. When we do, we will update the “Last updated”
                        date above.
                        Material changes may be communicated through the Service.
                    </p>

                    <h2 class="h5 fw-bold mt-4">14) Contact</h2>
                    <p class="mb-0">
                        If you have questions about this Privacy Policy or how we handle personal information, contact us
                        at:
                        <br>
                        <strong>Email:</strong> privacy@neptuneware.com<br>
                        <strong>Phone:</strong> +27 73 685 8061<br>
                        <strong>Company:</strong> NeptuneWare Pty LTD
                    </p>

                </div>
            </div>

            <div class="text-center mt-3 small text-muted">
                Related: <a href="{{ route('terms') }}">Terms of Service</a> • <a href="{{ route('support') }}">Support</a>
            </div>

        </div>
    </section>
@endsection
