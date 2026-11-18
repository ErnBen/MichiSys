@if(session('success'))
<div class="alert alert-success alert-dismissible" role="status">
    <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar aviso">&times;</button>
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar aviso">&times;</button>
    {{ session('error') }}
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar aviso">&times;</button>
    <h5><i class="icon fas fa-ban"></i> Hubo algunos errores:</h5>
    <ul>
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

@once
    @push('js')
        <script>
            document.querySelectorAll('[data-confirm-payment]').forEach(form => {
                form.addEventListener('submit', event => {
                    const amount = Number(form.elements.amount.value || 0);
                    const formatted = new Intl.NumberFormat('es-PY', {maximumFractionDigits: 0}).format(amount);
                    if (!window.confirm(`¿Confirmas registrar un pago de Gs. ${formatted}?`)) {
                        event.preventDefault();
                        return;
                    }
                    const button = form.querySelector('button[type="submit"]');
                    if (button) {
                        button.disabled = true;
                        button.textContent = 'Registrando pago…';
                    }
                });
            });
            document.querySelectorAll('.table-mobile-cards').forEach(table => {
                const headings = [...table.querySelectorAll('thead th')].map(th => th.textContent.trim());
                table.querySelectorAll('tbody tr').forEach(row => {
                    [...row.cells].forEach((cell, index) => {
                        if (cell.colSpan === 1) cell.dataset.label = headings[index] || 'Acciones';
                    });
                });
                table.classList.add('mobile-labels-ready');
            });
            document.querySelectorAll('.content-header h1').forEach(heading => {
                heading.id ||= 'page-heading';
                heading.tabIndex = -1;
            });
            if (document.getElementById('page-heading')) {
                const skip = document.createElement('a');
                skip.href = '#page-heading';
                skip.className = 'michisys-skip-link';
                skip.textContent = 'Saltar al contenido';
                document.body.prepend(skip);
            }
            document.querySelectorAll('[data-widget="pushmenu"]').forEach(button => {
                button.setAttribute('aria-label', 'Abrir o cerrar menú de navegación');
            });
        </script>
    @endpush
@endonce