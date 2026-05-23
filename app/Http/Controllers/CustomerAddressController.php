<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerAddressController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('Address/Edit', [
            'address' => $this->addressPayload($request->user()),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'formatted_address' => ['required', 'string', 'max:2000'],
            'city' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ]);

        $location = [
            'shipping_address' => trim((string) $data['formatted_address']),
            'shipping_city' => filled($data['city'] ?? null) ? trim((string) $data['city']) : null,
            'shipping_district' => filled($data['district'] ?? null) ? trim((string) $data['district']) : null,
            'shipping_postal_code' => filled($data['postal_code'] ?? null) ? trim((string) $data['postal_code']) : null,
            'shipping_latitude' => (float) $data['latitude'],
            'shipping_longitude' => (float) $data['longitude'],
        ];

        $request->user()->update($location);
        $request->session()->put('checkout.location', $this->addressPayload($request->user()->fresh()));

        return redirect()
            ->route('address.edit')
            ->with('success', 'Alamat pengiriman diperbarui.');
    }

    private function addressPayload(?User $user): ?array
    {
        if (! $user?->shipping_address || $user->shipping_latitude === null || $user->shipping_longitude === null) {
            return null;
        }

        return [
            'formatted_address' => $user->shipping_address,
            'city' => $user->shipping_city,
            'district' => $user->shipping_district,
            'postal_code' => $user->shipping_postal_code,
            'latitude' => (float) $user->shipping_latitude,
            'longitude' => (float) $user->shipping_longitude,
        ];
    }
}
