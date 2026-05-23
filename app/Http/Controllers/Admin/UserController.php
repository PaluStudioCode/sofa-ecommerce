<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Navigation\DashboardNavigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(['customer', 'admin'])],
        ]);

        $users = User::query()
            ->withCount('orders')
            ->when($filters['keyword'] ?? null, function ($query, string $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%")
                        ->orWhere('phone', 'like', "%{$keyword}%");
                });
            })
            ->when($filters['role'] ?? null, fn ($query, string $role) => $query->where('role', $role))
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
                'role' => $filters['role'] ?? '',
            ],
            'roles' => $this->roleOptions(true),
            'internalRoles' => $this->roleOptions(false),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(['admin'])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
        ]);

        return back()->with('success', 'Pengguna internal dibuat.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(['customer', 'admin'])],
        ]);

        if ($user->is($request->user()) && $data['role'] !== 'admin') {
            return back()->with('error', 'Admin tidak dapat menurunkan role akun sendiri.');
        }

        $user->update($data);

        return back()->with('success', 'Pengguna diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Akun sendiri tidak dapat dinonaktifkan.');
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

    private function roleOptions(bool $withAll): array
    {
        $roles = collect([
            ['value' => 'customer', 'label' => 'Customer'],
            ['value' => 'admin', 'label' => 'Admin'],
        ]);

        if (! $withAll) {
            return $roles->where('value', 'admin')->values()->all();
        }

        return $roles->prepend(['value' => '', 'label' => 'Semua role'])->values()->all();
    }
}
