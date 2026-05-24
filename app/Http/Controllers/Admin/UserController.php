<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Navigation\DashboardNavigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
        ]);

        $users = User::query()
            ->withCount('orders')
            ->where('role', 'customer')
            ->when($filters['keyword'] ?? null, function ($query, string $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%")
                        ->orWhere('phone', 'like', "%{$keyword}%");
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (User $user) => $this->payload($user));

        return Inertia::render('Admin/Users/Index', [
            'navigationGroups' => DashboardNavigation::forUser($request->user()),
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Pengguna', 'href' => route('admin.users.index')],
            ],
            'users' => $users,
            'filters' => [
                'keyword' => $filters['keyword'] ?? '',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return back()->with('error', 'Pengguna customer dibuat lewat halaman registrasi.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if (! $user->isCustomer()) {
            return back()->with('error', 'Halaman ini hanya untuk mengelola pengguna customer.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $user->update($data);

        return back()->with('success', 'Pengguna diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if (! $user->isCustomer()) {
            return back()->with('error', 'Halaman ini hanya untuk mengelola pengguna customer.');
        }

        $user->delete();

        return back()->with('success', 'Pengguna dinonaktifkan.');
    }

    private function payload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'orders_count' => $user->orders_count,
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }
}
