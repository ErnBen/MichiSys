<script>
    window.initializeProductSelectors = root => {
        const selectors = root.matches?.('[data-product-selector]')
            ? [root, ...root.querySelectorAll('[data-product-selector]')]
            : root.querySelectorAll('[data-product-selector]');

        selectors.forEach(selector => {
            if (selector.dataset.initialized) return;
            selector.dataset.initialized = 'true';
            const query = selector.querySelector('[data-product-query]');
            const status = selector.querySelector('[data-product-status]');
            const results = selector.querySelector('[data-product-results]');
            const selected = selector.querySelector('[data-product-selected]');
            const empty = selector.querySelector('[data-product-empty]');
            const selectedId = selector.querySelector('[data-product-selected-id]');
            let timer;
            let activeRequest;

            query.addEventListener('input', () => {
                window.clearTimeout(timer);
                timer = window.setTimeout(async () => {
                    activeRequest?.abort();
                    const term = query.value.trim();
                    results.replaceChildren();
                    if (term.length < 2) {
                        status.textContent = 'Escribe al menos 2 caracteres para buscar.';
                        return;
                    }

                    activeRequest = new AbortController();
                    status.textContent = 'Buscando productos…';
                    const params = new URLSearchParams({search: term});
                    if (selector.dataset.kind) params.set('kind', selector.dataset.kind);
                    if (selector.dataset.active !== undefined) params.set('active', selector.dataset.active);
                    if (selector.dataset.withoutRecipe) params.set('without_recipe', selector.dataset.withoutRecipe);
                    if (selector.dataset.exceptRecipeId) params.set('except_recipe_id', selector.dataset.exceptRecipeId);
                    try {
                        const response = await fetch(`${selector.dataset.searchUrl}?${params}`, {
                            headers: {'Accept': 'application/json'},
                            signal: activeRequest.signal
                        });
                        if (!response.ok) throw new Error('No se pudo completar la búsqueda de productos.');
                        const payload = await response.json();
                        payload.data.forEach(product => {
                            const button = document.createElement('button');
                            button.type = 'button';
                            button.className = 'list-group-item list-group-item-action';
                            button.dataset.productId = product.id;
                            button.textContent = selector.dataset.resultLabel
                                ? product[selector.dataset.resultLabel]
                                : `${product.name}${product.sku ? ` · ${product.sku}` : ''} · ${product.category || 'Sin categoría'} · Stock: ${product.stock} ${product.unit_label}`;
                            results.append(button);
                        });
                        status.textContent = payload.data.length
                            ? `${payload.data.length} resultado(s). Se muestran hasta 20.`
                            : 'No se encontraron productos activos.';
                    } catch (error) {
                        if (error.name === 'AbortError') return;
                        status.textContent = error.message || 'Ocurrió un error al buscar productos.';
                    }
                }, 250);
            });

            results.addEventListener('click', event => {
                const button = event.target.closest('[data-product-id]');
                if (!button) return;
                const productId = button.dataset.productId;
                if (selector.dataset.mode === 'single') {
                    selectedId.value = productId;
                    selected.textContent = button.textContent;
                    results.replaceChildren();
                    status.textContent = 'Producto seleccionado.';
                    return;
                }

                if (selected.querySelector(`[data-selected-product="${productId}"]`)) {
                    status.textContent = 'Ese producto ya está en la selección.';
                    return;
                }

                const row = document.createElement('div');
                row.className = selector.dataset.mode === 'combo'
                    ? 'form-group product-selection-row'
                    : 'form-check product-selection-row';
                row.dataset.selectedProduct = productId;
                const check = document.createElement('input');
                check.type = 'checkbox';
                check.name = 'products[]';
                check.value = productId;
                check.id = `${selector.dataset.mode}-product-${productId}`;
                check.className = 'form-check-input';
                check.checked = true;
                const label = document.createElement('label');
                label.className = 'form-check-label';
                label.htmlFor = check.id;
                label.textContent = button.textContent;
                row.append(check, label);

                if (selector.dataset.mode === 'combo') {
                    const quantity = document.createElement('input');
                    quantity.type = 'number';
                    quantity.min = '1';
                    quantity.value = '1';
                    quantity.name = `quantities[${productId}]`;
                    quantity.className = 'form-control mt-1';
                    quantity.setAttribute('aria-label', `Cantidad de ${button.textContent}`);
                    row.append(quantity);
                }

                empty?.remove();
                selected.append(row);
                status.textContent = 'Producto agregado a la selección.';
            });
        });
    };
    window.initializeProductSelectors(document);
</script>
