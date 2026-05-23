<?php

namespace Tests\Feature;

use App\Models\LandingSection;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_landing_page_with_active_sections_featured_products_and_voucher(): void
    {
        LandingSection::factory()->create([
            'section_key' => 'hero',
            'title' => 'Sofa utama',
            'is_active' => true,
            'sort_order' => 0,
        ]);
        LandingSection::factory()->create([
            'section_key' => 'value',
            'title' => 'Nonaktif',
            'is_active' => false,
            'sort_order' => 1,
        ]);

        $featured = Product::factory()->featured()->create(['status' => 'aktif']);
        ProductVariant::factory()->for($featured)->create(['status' => 'aktif', 'price' => 3500000, 'stock' => 4, 'reserved_stock' => 0]);
        ProductImage::factory()->for($featured)->create(['file_path' => 'products/featured.jpg', 'is_primary' => true]);

        Product::factory()->featured()->inactive()->create();

        Voucher::factory()->create([
            'code' => 'SOFAHEMAT',
            'status' => 'aktif',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
            'quota' => 10,
            'used_count' => 0,
        ]);
        Voucher::factory()->expired()->create(['code' => 'EXPIRED']);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->has('sections', 1)
                ->where('sections.0.title', 'Sofa utama')
                ->has('featuredProducts', 1)
                ->where('featuredProducts.0.name', $featured->name)
                ->where('activeVoucher.code', 'SOFAHEMAT')
            );
    }

    public function test_admin_can_create_update_and_delete_landing_section_with_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.landing-sections.store'), [
                'section_key' => 'value',
                'title' => 'Bahan berkualitas',
                'subtitle' => 'Rangka kokoh dan kain nyaman.',
                'content' => 'Dipilih untuk kebutuhan rumah harian.',
                'button_label' => 'Lihat',
                'button_url' => '/catalog',
                'sort_order' => 1,
                'is_active' => true,
                'image' => UploadedFile::fake()->image('landing.jpg'),
            ])
            ->assertRedirect(route('admin.landing-sections.index'));

        $section = LandingSection::firstOrFail();
        Storage::disk('public')->assertExists($section->image_path);

        $this->actingAs($admin)
            ->post(route('admin.landing-sections.update', $section), [
                '_method' => 'put',
                'section_key' => 'hero',
                'title' => 'Hero baru',
                'subtitle' => null,
                'content' => null,
                'button_label' => 'Katalog',
                'button_url' => '/catalog',
                'sort_order' => 0,
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.landing-sections.index'));

        $this->assertDatabaseHas('landing_sections', [
            'id' => $section->id,
            'section_key' => 'hero',
            'title' => 'Hero baru',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.landing-sections.destroy', $section))
            ->assertRedirect(route('admin.landing-sections.index'));

        $this->assertDatabaseMissing('landing_sections', ['id' => $section->id]);
    }

    public function test_landing_content_management_is_admin_only(): void
    {
        $this->get(route('admin.landing-sections.index'))->assertRedirect('/login');

        $this->actingAs(User::factory()->create())
            ->get(route('admin.landing-sections.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.landing-sections.index'))
            ->assertOk();
    }

    public function test_landing_section_rejects_non_image_upload(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.landing-sections.store'), [
                'section_key' => 'hero',
                'title' => 'Hero',
                'sort_order' => 0,
                'is_active' => true,
                'image' => UploadedFile::fake()->create('not-image.pdf', 12, 'application/pdf'),
            ])
            ->assertSessionHasErrors('image');
    }
}
