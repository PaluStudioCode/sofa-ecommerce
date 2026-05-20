<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\LandingSectionController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MidtransCallbackController;
use App\Http\Controllers\PaymentAttemptController;
use App\Http\Controllers\ProfileController;
use App\Support\Navigation\DashboardNavigation;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', HomeController::class)->name('home');
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/products/{product:slug}', [CatalogController::class, 'show'])->name('products.show');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard', [
        'navigationGroups' => DashboardNavigation::forUser(request()->user()),
        'breadcrumbs' => [
            ['label' => 'Dashboard', 'href' => route('dashboard')],
        ],
    ]);
})->middleware(['auth', 'verified', 'permission:view_dashboard'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'role:customer'])->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/cart/{cartItem}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{cartItem}', [CartController::class, 'destroy'])->name('cart.destroy');

    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout/location', [CheckoutController::class, 'resolveLocation'])->name('checkout.location');
    Route::post('/checkout/quote', [CheckoutController::class, 'quoteRequest'])->name('checkout.quote');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::post('/orders/{order}/payments', [PaymentAttemptController::class, 'store'])->name('payments.store');
});

Route::post('/midtrans/callback', MidtransCallbackController::class)->name('midtrans.callback');

Route::middleware(['auth', 'verified', 'permission:manage_landing_content'])
    ->prefix('dashboard')
    ->name('admin.')
    ->group(function () {
        Route::resource('landing-sections', LandingSectionController::class)->except(['show']);
    });

Route::middleware(['auth', 'verified'])
    ->prefix('dashboard')
    ->name('admin.')
    ->group(function () {
        Route::resource('products', AdminProductController::class)
            ->middleware('permission:manage_products');
        Route::resource('categories', CategoryController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middleware('permission:manage_categories');
        Route::resource('variants', ProductVariantController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middleware('permission:manage_product_variants');
        Route::put('product-images/{product_image}/primary', [ProductImageController::class, 'primary'])
            ->middleware('permission:manage_products')
            ->name('product-images.primary');
        Route::resource('product-images', ProductImageController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middleware('permission:manage_products');
    });

require __DIR__.'/auth.php';
