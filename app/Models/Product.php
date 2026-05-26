<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'primary_image_id' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function primaryImage(): BelongsTo
    {
        return $this->belongsTo(ProductImage::class, 'primary_image_id');
    }

    public function orderItems(): HasManyThrough
    {
        return $this->hasManyThrough(
            OrderItem::class,
            ProductVariant::class,
            'product_id',
            'product_variant_id'
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }

    public function scopeVisibleForCustomers(Builder $query): Builder
    {
        return $query
            ->active()
            ->whereHas('category', fn (Builder $category) => $category->where('is_active', true))
            ->whereHas('primaryImage.variant', fn (Builder $variant) => $variant
                ->where('status', 'aktif')
                ->whereColumn('stock', '>', 'reserved_stock'))
            ->whereHas('variants', fn (Builder $variant) => $variant
                ->where('status', 'aktif')
                ->whereColumn('stock', '>', 'reserved_stock'));
    }

    public function publishBlockers(): array
    {
        $blockers = [];

        $categoryIsActive = $this->category_id
            && Category::query()->whereKey($this->category_id)->where('is_active', true)->exists();

        if (! $categoryIsActive) {
            $blockers[] = 'Kategori belum aktif.';
        }

        $hasReadyVariant = $this->exists && $this->variants()
            ->where('status', 'aktif')
            ->whereColumn('stock', '>', 'reserved_stock')
            ->exists();

        if (! $hasReadyVariant) {
            $blockers[] = 'Minimal 1 varian aktif harus punya stok tersedia.';
        }

        $hasThumbnail = $this->exists
            && $this->primary_image_id
            && $this->primaryImage()
                ->whereHas('variant', fn (Builder $variant) => $variant
                    ->where('product_id', $this->id)
                    ->where('status', 'aktif')
                    ->whereColumn('stock', '>', 'reserved_stock'))
                ->exists();

        if (! $hasThumbnail) {
            $blockers[] = 'Thumbnail produk harus dipilih dari varian aktif yang stoknya tersedia.';
        }

        return $blockers;
    }

    public function isPublishReady(): bool
    {
        return $this->publishBlockers() === [];
    }
}
