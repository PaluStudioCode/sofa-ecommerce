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
            'systemInfo' => $this->systemInfo(),
            'integrations' => $this->integrations(),
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

    private function systemInfo(): array
    {
        return [
            ['label' => 'Nama aplikasi', 'value' => config('app.name')],
            ['label' => 'Environment', 'value' => config('app.env')],
            ['label' => 'Mode debug', 'value' => config('app.debug') ? 'Aktif' : 'Nonaktif'],
            ['label' => 'URL aplikasi', 'value' => config('app.url')],
            ['label' => 'Timezone', 'value' => config('app.timezone')],
            ['label' => 'Cache', 'value' => config('cache.default')],
            ['label' => 'Queue', 'value' => config('queue.default')],
            ['label' => 'Session', 'value' => config('session.driver')],
        ];
    }

    private function integrations(): array
    {
        return [
            [
                'label' => 'Midtrans',
                'status' => filled(config('services.midtrans.server_key')) && filled(config('services.midtrans.client_key')) ? 'aktif' : 'perlu_konfigurasi',
                'description' => config('services.midtrans.is_production') ? 'Production' : 'Sandbox',
            ],
            [
                'label' => 'Fonnte WhatsApp',
                'status' => filled(config('services.fonnte.token')) ? 'aktif' : 'perlu_konfigurasi',
                'description' => config('services.fonnte.driver'),
            ],
            [
                'label' => 'Email',
                'status' => filled(config('mail.from.address')) ? 'aktif' : 'perlu_konfigurasi',
                'description' => config('mail.default'),
            ],
        ];
    }
}
