<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Support\Navigation\DashboardNavigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SystemSettingController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/SystemSettings/Index', [
            'navigationGroups' => DashboardNavigation::forUser($request->user()),
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Pengaturan Sistem', 'href' => route('admin.system-settings.index')],
            ],
            'storeContact' => SystemSetting::storeContact(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'address' => ['required', 'string', 'max:500'],
            'email' => ['required', 'email', 'max:120'],
            'whatsapp' => ['required', 'string', 'max:30'],
            'hours' => ['required', 'string', 'max:120'],
        ]);

        SystemSetting::updateStoreContact($data);

        return back()->with('success', 'Pengaturan sistem diperbarui.');
    }
}
