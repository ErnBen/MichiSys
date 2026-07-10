<div class="form-group">
    <label>Nombre</label>
    <input type="text" name="name" value="{{ old('name', $provider?->name) }}" class="form-control" required>
</div>
<div class="form-group">
    <label>Email</label>
    <input type="email" name="email" value="{{ old('email', $provider?->email) }}" class="form-control">
</div>
<div class="form-group">
    <label>Teléfono</label>
    <input type="text" name="phone" value="{{ old('phone', $provider?->phone) }}" class="form-control">
</div>
<div class="form-group">
    <label>Dirección</label>
    <textarea name="address" class="form-control" rows="3">{{ old('address', $provider?->address) }}</textarea>
</div>
