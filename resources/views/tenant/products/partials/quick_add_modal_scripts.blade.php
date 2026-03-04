<script>
    (function() {
        const modalEl = document.getElementById('nwQuickProductModal');
        if (!modalEl) return;

        const errBox = document.getElementById('nwQpErrors');
        const nameEl = document.getElementById('nwQpName');
        const skuEl = document.getElementById('nwQpSku');
        const rateEl = document.getElementById('nwQpUnitRate');
        const unitEl = document.getElementById('nwQpUnit');
        const taxEl = document.getElementById('nwQpTaxTypeId');
        const brandEl = document.getElementById('nwQpBrandId');
        const categoryEl = document.getElementById('nwQpCategoryId');
        const descEl = document.getElementById('nwQpDescription');

        let activeRow = null;
        let afterCreate = null;

        function clearErrors() {
            errBox.innerHTML = '';
            errBox.classList.add('d-none');
        }

        function showErrors(html) {
            errBox.innerHTML = html;
            errBox.classList.remove('d-none');
        }

        function defaultTaxFromPage() {
            const dt = document.getElementById('defaultTaxType');
            return dt ? (dt.value || '') : '';
        }

        window.NWQuickProduct = {
            open: function(opts = {}) {
                clearErrors();
                activeRow = opts.row || null;
                afterCreate = typeof opts.afterCreate === 'function' ? opts.afterCreate : null;

                nameEl.value = opts.name || '';
                skuEl.value = (opts.sku || '').toUpperCase();
                rateEl.value = (opts.unit_rate ?? 0);
                unitEl.value = opts.unit || '';
                descEl.value = opts.description || '';
                taxEl.value = opts.tax_type_id || defaultTaxFromPage();

                // ✅ prefill brand/category if provided
                if (brandEl) brandEl.value = opts.brand_id || '';
                if (categoryEl) categoryEl.value = opts.category_id || '';

                bootstrap.Modal.getOrCreateInstance(modalEl).show();
                setTimeout(() => nameEl.focus(), 150);
            }
        };

        async function save() {
            clearErrors();

            const fd = new FormData();
            fd.append('sku', (skuEl.value || '').trim().toUpperCase());
            fd.append('name', (nameEl.value || '').trim());
            fd.append('unit_rate', (rateEl.value || '0'));
            fd.append('unit', (unitEl.value || '').trim());
            fd.append('currency', document.getElementById('nwQpCurrency')?.value || 'ZAR');
            fd.append('tax_type_id', taxEl.value || '');
            fd.append('brand_id', brandEl?.value || '');
            fd.append('category_id', categoryEl?.value || '');
            fd.append('description', (descEl.value || '').trim());
            fd.append('is_active', document.getElementById('nwQpIsActive')?.value || '1');

            const res = await fetch(@json(tenant_route('tenant.products.store')), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': @json(csrf_token()),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: fd
            });

            if (!res.ok) {
                let msg = 'Could not create product.';
                try {
                    const data = await res.json();
                    if (data?.errors) msg = Object.values(data.errors).flat().join('<br>');
                    else if (data?.message) msg = data.message;
                } catch (_) {}
                showErrors(msg);
                return;
            }

            const data = await res.json();
            const product = data.product || data.data || data;

            window.NW_PRODUCTS = window.NW_PRODUCTS || {};
            window.NW_PRODUCTS[String(product.id)] = product;
            window.NW_PRODUCTS[product.id] = product;

            if (afterCreate) afterCreate(product, activeRow);

            bootstrap.Modal.getInstance(modalEl)?.hide();
        }

        document.getElementById('nwQpSaveBtn')?.addEventListener('click', save);
    })();
</script>
