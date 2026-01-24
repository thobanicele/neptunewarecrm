<script>
    (function() {
        const CSRF = @json(csrf_token());
        const STAGE_URL = @json(tenant_route('tenant.leads.stage', ['contact' => '__ID__']));
        const QUALIFY_URL = @json(tenant_route('tenant.leads.qualify', ['contact' => '__ID__']));

        // Toast (optional if you already have one)
        function toast(msg) {
            // super simple fallback
            console.log(msg);
        }

        // -------- MODAL wiring (list + kanban)
        const qualifyModal = document.getElementById('qualifyModal');
        const qualifyForm = document.getElementById('qualifyForm');
        const leadName = document.getElementById('leadName');

        if (qualifyModal && qualifyForm && leadName) {
            qualifyModal.addEventListener('show.bs.modal', function(event) {
                const btn = event.relatedTarget;
                const id = btn?.getAttribute('data-id');
                const name = btn?.getAttribute('data-name') || '';
                if (!id) return;

                leadName.textContent = name;
                qualifyForm.action = QUALIFY_URL.replace('__ID__', id);
            });
        }

        const companyMode = document.getElementById('companyMode');
        const createWrap = document.getElementById('companyCreateWrap');
        const attachWrap = document.getElementById('companyAttachWrap');
        if (companyMode && createWrap && attachWrap) {
            companyMode.addEventListener('change', function() {
                const attach = companyMode.value === 'attach';
                attachWrap.classList.toggle('d-none', !attach);
                createWrap.classList.toggle('d-none', attach);
            });
        }

        const createDeal = document.getElementById('createDeal');
        const dealWrap = document.getElementById('dealWrap');
        if (createDeal && dealWrap) {
            createDeal.addEventListener('change', () => dealWrap.classList.toggle('d-none', !createDeal.checked));
        }

        // -------- KANBAN drag/drop (only if board exists)
        if (typeof Sortable === 'undefined') {
            // if you load Sortable globally in layout, you're good
            return;
        }

        function setStageCount(stage, delta) {
            const el = document.querySelector(`.stage-count[data-stage="${stage}"]`);
            if (!el) return;
            const v = parseInt(el.textContent || "0", 10);
            el.textContent = Math.max(0, v + delta);
        }

        async function updateStage(contactId, stage) {
            const url = STAGE_URL.replace('__ID__', contactId);

            const res = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    lead_stage: stage
                })
            });

            const json = await res.json().catch(() => ({}));
            if (!res.ok || json.ok === false) {
                throw new Error(json.message || 'Failed to update stage');
            }
            return json;
        }

        document.querySelectorAll('.lead-column').forEach(col => {
            new Sortable(col, {
                group: 'leads',
                animation: 150,
                ghostClass: 'bg-light',
                onEnd: async function(evt) {
                    const card = evt.item;
                    const id = card?.getAttribute('data-id');
                    const toStage = evt.to?.getAttribute('data-stage');
                    const fromStage = evt.from?.getAttribute('data-stage');

                    if (!id || !toStage || !fromStage || toStage === fromStage) return;

                    // optimistic counter update
                    setStageCount(fromStage, -1);
                    setStageCount(toStage, +1);
                    toast('Saving…');

                    try {
                        await updateStage(id, toStage);
                        toast('Stage updated ✅');
                    } catch (e) {
                        // revert: move card back
                        evt.from.prepend(card);
                        setStageCount(fromStage, +1);
                        setStageCount(toStage, -1);
                        alert(e.message || 'Failed — reverted');
                    }
                }
            });
        });

    })();
</script>
