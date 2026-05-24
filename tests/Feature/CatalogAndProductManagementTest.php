<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CatalogAndProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_catalog_with_active_products_only(): void
    {
        $active = Product::factory()->create(['name' => 'Sofa Aktif', 'status' => 'aktif']);
        $activeVariant = ProductVariant::factory()->for($active)->create(['price' => 3000000, 'stock' => 5, 'reserved_stock' => 1, 'status' => 'aktif']);
        ProductImage::factory()->for($activeVariant, 'variant')->create(['is_primary' => true]);

        $inactive = Product::factory()->inactive()->create(['name' => 'Sofa Nonaktif']);
        ProductVariant::factory()->for($inactive)->create(['status' => 'aktif']);

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Index')
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Sofa Aktif')
                ->where('products.data.0.available', true)
            );
    }

    public function test_catalog_keyword_category_and_price_filters_work(): void
    {
        $corner = Category::factory()->create(['name' => 'Corner']);
        $minimalis = Category::factory()->create(['name' => 'Minimalis']);

        $match = Product::factory()->for($corner)->create(['name' => 'Sofa Aurora Corner']);
        ProductVariant::factory()->for($match)->create(['price' => 4500000, 'status' => 'aktif']);

        $wrongCategory = Product::factory()->for($minimalis)->create(['name' => 'Sofa Aurora Minimalis']);
        ProductVariant::factory()->for($wrongCategory)->create(['price' => 4500000, 'status' => 'aktif']);

        $wrongPrice = Product::factory()->for($corner)->create(['name' => 'Sofa Aurora Mahal']);
        ProductVariant::factory()->for($wrongPrice)->create(['price' => 9000000, 'status' => 'aktif']);

        $this->get(route('catalog.index', [
            'keyword' => 'Aurora',
            'category' => $corner->id,
            'min_price' => 4000000,
            'max_price' => 5000000,
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Sofa Aurora Corner')
            );
    }

    public function test_guest_can_view_active_product_detail_with_variant_stock_and_images(): void
    {
        $product = Product::factory()->create(['status' => 'aktif']);
        $variant = ProductVariant::factory()->for($product)->create([
            'variant_name' => 'Premium',
            'stock' => 6,
            'reserved_stock' => 2,
            'status' => 'aktif',
        ]);
        ProductImage::factory()->for($variant, 'variant')->create(['file_path' => 'products/detail.jpg', 'is_primary' => true]);

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Show')
                ->where('product.name', $product->name)
                ->has('product.variants', 1)
                ->has('product.variants.0.images', 1)
                ->where('product.variants.0.available_stock', 4)
                ->where('product.variants.0.can_add_to_cart', true)
            );

        $inactive = Product::factory()->inactive()->create();
        $this->get(route('products.show', $inactive->slug))->assertNotFound();
    }

    public function test_admin_can_create_update_products_categories_and_variants(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Sofa Keluarga',
                'slug' => 'sofa-keluarga',
                'description' => 'Kategori keluarga.',
                'is_active' => true,
            ])
            ->assertRedirect();

        $category = Category::where('slug', 'sofa-keluarga')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), [
                'category_id' => $category->id,
                'name' => 'Sofa Nusa',
                'slug' => 'sofa-nusa',
                'description' => 'Sofa nyaman.',
                'status' => 'aktif',
                'is_featured' => true,
            ])
            ->assertRedirect();

        $product = Product::where('slug', 'sofa-nusa')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.variants.store'), [
                'product_id' => $product->id,
                'sku' => 'SN-001',
                'variant_name' => 'Dua Dudukan',
                'size' => '2 seater',
                'material' => 'Linen',
                'color' => 'Abu',
                'price' => 3500000,
                'stock' => 8,
                'reserved_stock' => 1,
                'status' => 'aktif',
            ])
            ->assertRedirect();

        $variant = ProductVariant::where('sku', 'SN-001')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.variants.update', $variant), [
                'product_id' => $product->id,
                'sku' => 'SN-001',
                'variant_name' => 'Tiga Dudukan',
                'size' => '3 seater',
                'material' => 'Linen',
                'color' => 'Abu',
                'price' => 4200000,
                'stock' => 9,
                'reserved_stock' => 2,
                'status' => 'aktif',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'variant_name' => 'Tiga Dudukan',
            'reserved_stock' => 0,
        ]);
    }

    public function test_product_image_upload_validation_and_primary_image_rule(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create();

        $this->actingAs($admin)
            ->post(route('admin.product-images.store'), [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'image' => UploadedFile::fake()->image('primary.jpg'),
                'alt_text' => 'Primary',
                'sort_order' => 0,
                'is_primary' => true,
            ])
            ->assertRedirect();

        $first = ProductImage::firstOrFail();
        Storage::disk('public')->assertExists($first->file_path);

        $this->actingAs($admin)
            ->post(route('admin.product-images.store'), [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'image' => UploadedFile::fake()->image('second.jpg'),
                'alt_text' => 'Second',
                'sort_order' => 1,
                'is_primary' => true,
            ])
            ->assertRedirect();

        $this->assertFalse($first->fresh()->is_primary);
        $this->assertTrue(ProductImage::latest('id')->firstOrFail()->is_primary);

        $this->actingAs($admin)
            ->post(route('admin.product-images.store'), [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'image' => UploadedFile::fake()->create('not-image.pdf', 12, 'application/pdf'),
                'sort_order' => 2,
            ])
            ->assertSessionHasErrors('image');
    }

    public function test_admin_can_upload_multiple_product_images_at_once(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create();

        $this->actingAs($admin)
            ->post(route('admin.product-images.store'), [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'images' => [
                    UploadedFile::fake()->image('front.jpg'),
                    UploadedFile::fake()->image('side.jpg'),
                    UploadedFile::fake()->image('detail.jpg'),
                ],
                'alt_text' => 'Batch sofa',
                'sort_order' => 4,
                'is_primary' => true,
            ])
            ->assertRedirect();

        $images = ProductImage::where('product_variant_id', $variant->id)->orderBy('sort_order')->get();

        $this->assertCount(3, $images);
        $this->assertSame([4, 5, 6], $images->pluck('sort_order')->all());
        $this->assertSame([true, false, false], $images->pluck('is_primary')->all());
        $this->assertTrue($images->every(fn (ProductImage $image) => $image->product_variant_id === $variant->id));

        $images->each(fn (ProductImage $image) => Storage::disk('public')->assertExists($image->file_path));
    }

    public function test_admin_product_management_is_forbidden_for_customer(): void
    {
        $this->get(route('admin.products.index'))->assertRedirect('/login');

        $this->actingAs(User::factory()->create())
            ->get(route('admin.products.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.products.index'))
            ->assertOk();
    }

    public function test_used_master_data_is_not_removed_when_it_has_order_history(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create();
        $variant = ProductVariant::factory()->for($product)->create();

        OrderItem::factory()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_name' => $variant->variant_name,
            'variant_sku' => $variant->sku,
            'product_price' => $variant->price,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->actingAs($admin)
            ->delete(route('admin.variants.destroy', $variant))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'deleted_at' => null]);
    }
}
