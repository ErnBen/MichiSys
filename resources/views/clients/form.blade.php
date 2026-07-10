<div class="form-group">
    <label>Nombre</label>
    <input type="text" name="name" value="{{ old('name', $client?->name) }}" class="form-control" required>
</div>
<div class="form-group">
    <label>Email</label>
    <input type="email" name="email" value="{{ old('email', $client?->email) }}" class="form-control">
</div>
<div class="form-group">
    <label>Teléfono</label>
    <input type="text" name="phone" value="{{ old('phone', $client?->phone) }}" class="form-control">
</div>
<div class="form-group">
    <label>Dirección</label>
    <textarea name="address" class="form-control" rows="3">{{ old('address', $client?->address) }}</textarea>
</div>
