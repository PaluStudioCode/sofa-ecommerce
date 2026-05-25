<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerAddressController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user()->load('defaultAddress');

        return Inertia::render('Address/Edit', [
            'address' => $this->addressPayload($user->defaultAddress, $user),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'detail' => ['required', 'string', 'max:1000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'formatted_address' => ['required', 'string', 'max:2000'],
            'city' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ]);

        $payload = [
            'recipient_name' => trim((string) $data['recipient_name']),
            'phone' => trim((string) $data['phone']),
            'detail' => trim((string) $data['detail']),
            'formatted_address' => trim((string) $data['formatted_address']),
            'city' => filled($data['city'] ?? null) ? trim((string) $data['city']) : null,
            'district' => filled($data['district'] ?? null) ? trim((string) $data['district']) : null,
            'postal_code' => filled($data['postal_code'] ?? null) ? trim((string) $data['postal_code']) : null,
            'latitude' => (float) $data['latitude'],
            'longitude' => (float) $data['longitude'],
            'is_default' => true,
        ];

        $user = $request->user();
        $address = $user->defaultAddress()->first();

        if ($address && ! $address->orders()->exists()) {
            $address->update($payload);
        } else {
            $address = $user->addresses()->create($payload);
        }

        $user->addresses()
            ->whereKeyNot($address->id)
            ->update(['is_default' => false]);

        $user->forceFill(['phone' => $payload['phone']])->save();
        $request->session()->put('checkout.location', $this->addressPayload($address->fresh(), $user->fresh()));

        return redirect()
            ->route('address.edit')
            ->with('success', 'Alamat pengiriman diperbarui.');
    }

    private function addressPayload(?UserAddress $address, ?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'id' => $address?->id,
            'recipient_name' => $address?->recipient_name ?: $user->name,
            'phone' => $address?->phone ?: $user->phone,
            'detail' => $address?->detail,
            'formatted_address' => $address?->formatted_address,
            'city' => $address?->city,
            'district' => $address?->district,
            'postal_code' => $address?->postal_code,
            'latitude' => $address?->latitude === null ? null : (float) $address->latitude,
            'longitude' => $address?->longitude === null ? null : (float) $address->longitude,
        ];
    }
}
