<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Authorization\RolePermission;
use App\Support\Navigation\DashboardNavigation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(Request $request): Response
    {
        $roles = collect(RolePermission::roles())
            ->map(fn (string $role) => [
                'role' => $role,
                'permissions' => RolePermission::forRole($role),
            ])
            ->values();

        return Inertia::render('Admin/Roles/Index', [
            'navigationGroups' => DashboardNavigation::forUser($request->user()),
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Role', 'href' => route('admin.roles.index')],
            ],
            'roles' => $roles,
        ]);
    }
}
