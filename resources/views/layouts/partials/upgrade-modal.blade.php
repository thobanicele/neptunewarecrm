<div class="modal fade" id="upgradeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Unlock Premium</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <p class="mb-2" id="upgradeText">This feature is available on the Premium plan.</p>
                <div class="small text-muted">
                    <div class="fw-semibold mb-1">Premium includes:</div>
                    <ul class="mb-0">
                        <li>More users & pipelines</li>
                        <li>Export & advanced reporting</li>
                        <li>Custom branding</li>
                    </ul>
                </div>
            </div>

            <div class="modal-footer">
                <a id="upgradeCta" href="{{ tenant() ? tenant_route('tenant.billing.upgrade') : '#' }}"
                    class="btn btn-primary">Upgrade to Premium</a>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Not now</button>
            </div>
        </div>
    </div>
</div>
