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
    @if(isset($user))<small class="form-text text-muted">Dejar en blanco para mantener la contraseña actual.</small>@endif
</div>
<div class="form-group">
    <label>Rol</label>
    <select name="role" class="form-control" required>
        <option value="cajero" {{ old('role', $user?->role) == 'cajero' ? 'selected' : '' }}>Empleado / Cajero</option>
        <option value="admin" {{ old('role', $user?->role) == 'admin' ? 'selected' : '' }}>Administrador</option>
    </select>
</div>
<div class="form-group form-check">
    <input type="hidden" name="active" value="0">
    <input type="checkbox" name="active" value="1" class="form-check-input" id="active" {{ old('active', $user?->active ?? 1) ? 'checked' : '' }}>
    <label class="form-check-label" for="active">Activo</label>
</div>
