<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class BusinessAdminController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $status = in_array($request->input('status'), ['active', 'inactive'], true)
            ? $request->input('status')
            : '';
        $businesses = Business::query()
            ->withCount('users')
            ->when($status !== '', fn ($query) => $query->where('active', $status === 'active'))
            ->when($search !== '', fn ($query) => $query->where(fn ($query) =>
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('owner_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
            ))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();
        $statistics = [
            'businesses' => Business::count(),
            'active' => Business::where('active', true)->count(),
            'inactive' => Business::where('active', false)->count(),
            'owners' => User::whereIn('role', ['dueno', 'admin'])->count(),
            'users' => User::where('role', '!=', 'superadmin')->count(),
        ];
        $selectedBusiness = $request->session()->get('managed_business_id')
            ? Business::find($request->session()->get('managed_business_id'))
            : null;

        return view('superadmin.businesses.index', compact('businesses', 'search', 'status', 'statistics', 'selectedBusiness'));
    }

    public function create()
    {
        return view('superadmin.businesses.form', ['business' => null]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'owner_email' => mb_strtolower(trim((string) $request->input('owner_email'))),
        ]);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:businesses,email',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'ruc' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:5000',
            'owner_email' => 'required|email|max:255|unique:users,email',
            'owner_password' => 'required|string|min:12|max:255',
            'logo' => 'nullable|image|max:2048',
        ]);
        $logoPath = $request->file('logo')?->store('businesses', 'public');

        try {
            $business = DB::transaction(function () use ($data, $logoPath): Business {
                $business = Business::create([
                    'name' => $data['name'],
                    'owner_name' => $data['owner_name'],
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'address' => $data['address'] ?? null,
                    'ruc' => $data['ruc'] ?? null,
                    'description' => $data['description'] ?? null,
                    'logo' => $logoPath,
                    'active' => true,
                ]);
                $owner = $business->users()->create([
                    'name' => $data['owner_name'],
                    'email' => $data['owner_email'],
                    'password' => Hash::make($data['owner_password']),
                    'role' => 'dueno',
                    'active' => true,
                ]);
                $owner->forceFill(['email_verified_at' => now()])->save();

                return $business;
            });
        } catch (Throwable $exception) {
            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }

            throw $exception;
        }

        return Redirect::route('superadmin.businesses.index')
            ->with('success', "Negocio {$business->name} y cuenta de dueño creados correctamente.");
    }

    public function edit(Business $business)
    {
        $owner = $business->users()->whereIn('role', ['dueno', 'admin'])->first();

        return view('superadmin.businesses.form', compact('business', 'owner'));
    }

    public function update(Request $request, Business $business)
    {
        $request->merge([
            'owner_email' => mb_strtolower(trim((string) $request->input('owner_email'))),
        ]);
        $owner = $business->users()->whereIn('role', ['dueno', 'admin'])->first();
        $data = $request->validate($this->businessProfileRules($business) + [
            'active' => 'required|boolean',
            'logo' => 'nullable|image|max:2048',
            'owner_email' => [
                $owner ? 'nullable' : 'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($owner?->id),
            ],
            'owner_password' => [$owner ? 'nullable' : 'required', 'string', 'min:12', 'max:255'],
        ]);

        $previousLogo = $business->logo;
        $business->fill($data);
        $this->storeLogo($request, $business);
        DB::transaction(function () use ($business, $owner, $data): void {
            $business->save();
            $ownerData = ['name' => $data['owner_name']];

            if (! empty($data['owner_email'])) {
                $ownerData['email'] = $data['owner_email'];
            }

            if (! empty($data['owner_password'])) {
                $ownerData['password'] = Hash::make($data['owner_password']);
            }

            if ($owner) {
                $owner->fill($ownerData)
                    ->forceFill(['email_verified_at' => $owner->email_verified_at ?? now()])
                    ->save();
            } else {
                $ownerData['password'] = Hash::make($data['owner_password']);
                $ownerData['role'] = 'dueno';
                $ownerData['active'] = true;
                $owner = $business->users()->create($ownerData);
                $owner->forceFill(['email_verified_at' => now()])->save();
            }
        });
        $this->removePreviousLogo($previousLogo, $business->logo);

        return Redirect::route('superadmin.businesses.index')->with('success', 'Negocio actualizado correctamente.');
    }

    public function toggle(Request $request, Business $business)
    {
        $data = $request->validate(['active' => 'required|boolean']);
        $business->update(['active' => $data['active']]);

        return Redirect::route('superadmin.businesses.index')
            ->with('success', $business->active ? 'Negocio activado.' : 'Negocio desactivado.');
    }

    public function select(Request $request, Business $business)
    {
        $request->session()->put('managed_business_id', $business->id);

        return Redirect::route('dashboard')->with('success', "Administrando explícitamente: {$business->name}.");
    }

    public function users(Business $business)
    {
        $users = $business->users()->orderBy('role')->orderBy('name')->paginate(20);

        return view('superadmin.businesses.users', compact('business', 'users'));
    }

    public function toggleUser(Request $request, Business $business, User $user)
    {
        abort_unless((int) $user->business_id === (int) $business->id && $user->role !== 'superadmin', 404);
        $data = $request->validate(['active' => 'required|boolean']);
        $user->update(['active' => $data['active']]);

        return Redirect::route('superadmin.businesses.users', $business)
            ->with('success', 'Estado de usuario actualizado correctamente.');
    }

    public function editSettings(Request $request)
    {
        $business = Business::findOrFail($request->user()->business_id);

        return view('businesses.settings', compact('business'));
    }

    public function updateSettings(Request $request)
    {
        $business = Business::findOrFail($request->user()->business_id);
        $data = $request->validate($this->businessProfileRules($business) + [
            'logo' => 'nullable|image|max:2048',
        ]);
        $previousLogo = $business->logo;
        $business->fill($data);
        $this->storeLogo($request, $business);
        $business->save();
        $this->removePreviousLogo($previousLogo, $business->logo);

        return Redirect::route('business.settings.edit')->with('success', 'Datos del negocio actualizados.');
    }

    private function storeLogo(Request $request, Business $business): void
    {
        if (! $request->hasFile('logo')) {
            return;
        }

        $business->logo = $request->file('logo')->store('businesses', 'public');
    }

    private function removePreviousLogo(?string $previousLogo, ?string $currentLogo): void
    {
        if ($previousLogo && $previousLogo !== $currentLogo) {
            Storage::disk('public')->delete($previousLogo);
        }
    }

    private function businessProfileRules(Business $business): array
    {
        return [
            'name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('businesses', 'email')->ignore($business->id)],
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'ruc' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:5000',
        ];
    }
}
