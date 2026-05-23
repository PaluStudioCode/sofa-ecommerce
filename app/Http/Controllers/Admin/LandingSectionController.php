<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LandingSectionRequest;
use App\Models\LandingSection;
use App\Support\MediaUrl;
use App\Support\Navigation\DashboardNavigation;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LandingSectionController extends Controller
{
    public function index(): Response
    {
        $sections = LandingSection::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (LandingSection $section) => $this->payload($section));

        return Inertia::render('Admin/LandingSections/Index', [
            'navigationGroups' => DashboardNavigation::forUser(request()->user()),
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Landing Page', 'href' => route('admin.landing-sections.index')],
            ],
            'sections' => $sections,
            'sectionKeys' => $this->sectionKeys(),
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('admin.landing-sections.index');
    }

    public function store(LandingSectionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('landing', 'public');
        }

        unset($data['image']);

        LandingSection::create($data);

        return redirect()->route('admin.landing-sections.index')->with('success', 'Section landing page disimpan.');
    }

    public function edit(LandingSection $landingSection): RedirectResponse
    {
        return redirect()->route('admin.landing-sections.index');
    }

    public function update(LandingSectionRequest $request, LandingSection $landingSection): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            if ($landingSection->image_path) {
                MediaUrl::deleteLocal($landingSection->image_path);
            }

            $data['image_path'] = $request->file('image')->store('landing', 'public');
        }

        unset($data['image']);

        $landingSection->update($data);

        return redirect()->route('admin.landing-sections.index')->with('success', 'Section landing page diperbarui.');
    }

    public function destroy(LandingSection $landingSection): RedirectResponse
    {
        if ($landingSection->image_path) {
            MediaUrl::deleteLocal($landingSection->image_path);
        }

        $landingSection->delete();

        return redirect()->route('admin.landing-sections.index')->with('success', 'Section landing page dihapus.');
    }

    private function payload(LandingSection $section): array
    {
        return [
            'id' => $section->id,
            'section_key' => $section->section_key,
            'title' => $section->title,
            'subtitle' => $section->subtitle,
            'content' => $section->content,
            'image_path' => $section->image_path,
            'image_url' => MediaUrl::fromPath($section->image_path),
            'button_label' => $section->button_label,
            'button_url' => $section->button_url,
            'sort_order' => $section->sort_order,
            'is_active' => $section->is_active,
        ];
    }

    private function sectionKeys(): array
    {
        return [
            ['value' => 'hero', 'label' => 'Hero'],
            ['value' => 'value', 'label' => 'Keunggulan'],
            ['value' => 'promo', 'label' => 'Promo'],
            ['value' => 'featured_products', 'label' => 'Produk Unggulan'],
            ['value' => 'shopping_flow', 'label' => 'Alur Belanja'],
        ];
    }
}
