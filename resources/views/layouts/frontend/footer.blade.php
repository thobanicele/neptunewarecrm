<div class="container-fluid">
    <div class="row text-muted align-items-center">
        <div class="col-12 col-md-6 text-center text-md-start">
            <p class="mb-0 small">
                <a class="text-muted fw-bold nw-footer-link" href="https://crm.neptuneware.com" target="_blank"
                    rel="noopener">
                    NeptuneWare CRM
                </a>
                <span class="mx-1">•</span>
                <span>a product of</span>
                <a class="text-muted fw-bold nw-footer-link ms-1" href="https://neptuneware.com" target="_blank"
                    rel="noopener">
                    NeptuneWare (Pty) Ltd
                </a>
                <span class="mx-1">•</span>
                <span>&copy; {{ now()->year }}. All rights reserved.</span>
            </p>
        </div>

        <div class="col-12 col-md-6 text-center text-md-end mt-2 mt-md-0">
            <ul class="list-inline mb-0 small">
                <li class="list-inline-item">
                    <a class="text-muted nw-footer-link" href="https://crm.neptuneware.com/support" target="_blank"
                        rel="noopener">Support</a>
                </li>
                <li class="list-inline-item">
                    <a class="text-muted nw-footer-link" href="https://crm.neptuneware.com/help-center" target="_blank"
                        rel="noopener">Help Center</a>
                </li>
                <li class="list-inline-item">
                    <a class="text-muted nw-footer-link" href="https://crm.neptuneware.com/privacy-policy"
                        target="_blank" rel="noopener">Privacy</a>
                </li>
                <li class="list-inline-item">
                    <a class="text-muted nw-footer-link" href="https://crm.neptuneware.com/terms-of-service"
                        target="_blank" rel="noopener">Terms</a>
                </li>

                {{-- Optional: version --}}
                @if (config('app.version'))
                    <li class="list-inline-item d-none d-md-inline">
                        <span class="text-muted">• v{{ config('app.version') }}</span>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</div>
