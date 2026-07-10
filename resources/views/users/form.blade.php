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
