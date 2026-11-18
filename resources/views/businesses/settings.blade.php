@extends('adminlte::page')
@section('title', 'Datos del negocio')
@section('content_header')
    <h1>Datos de {{ $business->name }}</h1>
@stop
@section('content')
    @include('shared.alerts')
    <form method="POST" action="{{ route('business.settings.update') }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="card">
            <div class="card-body">
                <div class="form-group"><label for="name">Nombre comercial</label><input id="name" name="name" class="form-control" value="{{ old('name', $business->name) }}" required></div>
                <div class="form-group"><label for="owner_name">Responsable</label><input id="owner_name" name="owner_name" class="form-control" value="{{ old('owner_name', $business->owner_name) }}" required></div>
                <div class="form-row">
                    <div class="form-group col-md-6"><label for="email">Correo</label><input id="email" name="email" type="email" class="form-control" value="{{ old('email', $business->email) }}"></div>
                    <div class="form-group col-md-6"><label for="phone">Teléfono</label><input id="phone" name="phone" class="form-control" value="{{ old('phone', $business->phone) }}"></div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6"><label for="address">Dirección</label><input id="address" name="address" class="form-control" value="{{ old('address', $business->address) }}"></div>
                    <div class="form-group col-md-6"><label for="ruc">RUC (si corresponde)</label><input id="ruc" name="ruc" class="form-control" value="{{ old('ruc', $business->ruc) }}"></div>
                </div>
                <div class="form-group"><label for="description">Descripción</label><textarea id="description" name="description" class="form-control" rows="3">{{ old('description', $business->description) }}</textarea></div>
                <div class="form-group">
                    <label for="logo">Logotipo</label>
                    <div class="custom-file">
                        <input id="logo" type="file" name="logo" class="custom-file-input" accept="image/*" aria-describedby="logo-help">
                        <label class="custom-file-label" for="logo">Seleccionar imagen</label>
                    </div>
                    <small id="logo-help" class="form-text text-muted">Formatos de imagen habituales, máximo 2 MB. Al guardar, el logotipo aparecerá en el menú lateral.</small>
                    <div class="mt-3">
                    @if($business->logo)
                        <img id="logo-preview" src="{{ Storage::disk('public')->url($business->logo) }}" alt="Logotipo de {{ $business->name }}" class="img-thumbnail" style="max-width: 100%; max-height: 160px; object-fit: contain">
                    @else
                        <img id="logo-preview" class="img-thumbnail d-none" alt="Vista previa del logotipo" style="max-width: 100%; max-height: 160px; object-fit: contain">
                    @endif
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="form-actions"><button type="submit" class="btn btn-primary">Guardar datos</button></div>
            </div>
        </div>
    </form>
@stop
@section('js')
    <script>
        (() => {
            const input = document.getElementById('logo');
            const label = input.nextElementSibling;
            const preview = document.getElementById('logo-preview');
            let previewUrl;

            input.addEventListener('change', () => {
                const file = input.files[0];
                if (!file) return;

                label.textContent = file.name;
                if (previewUrl) URL.revokeObjectURL(previewUrl);
                previewUrl = URL.createObjectURL(file);
                preview.src = previewUrl;
                preview.classList.remove('d-none');
                preview.alt = `Vista previa del logotipo: ${file.name}`;
            });
        })();
    </script>
@stop
