<div class="form-group">
    <label>Nombre</label>
    <input type="text" name="name" value="{{ old('name', $user?->name) }}" class="form-control" required>
</div>
<div class="form-group">
    <label>Email</label>
    <input type="email" name="email" value="{{ old('email', $user?->email) }}" class="form-control" required>
</div>
<div class="form-group">
    <label>Contraseña</label>
    <input type="password" name="password" class="form-control" @if(!isset($user)) required @endif>
    @if(isset($user))
        <small class="form-text text-muted">Deja en blanco para conservar la contraseña. Si escribes una nueva, el cambio se aplica al guardar.</small>
    @else
        <small class="form-text text-muted">El cajero iniciará sesión con este correo y la contraseña que asignes.</small>
    @endif
</div>
<div class="form-group form-check">
    <input type="hidden" name="active" value="0">
    <input type="checkbox" name="active" value="1" class="form-check-input" id="active" {{ old('active', $user?->active ?? 1) ? 'checked' : '' }}>
    <label class="form-check-label" for="active">Activo</label>
</div>
