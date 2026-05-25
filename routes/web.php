<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\ShipmentController;
use App\Http\Controllers\Admin\ShippingAreaController;
use App\Http\Controllers\Admin\SystemSettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerAddressController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MapReverseGeocodeController;
use App\Http\Controllers\MidtransCallbackController;
use App\Http\Controllers\PaymentAttemptController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/products/{product:slug}', [CatalogController::class, 'show'])->name('products.show');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified', 'permission:view_dashboard'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])
    ->get('/maps/reverse-geocode', MapReverseGeocodeController::class)
    ->name('maps.reverse-geocode');

Route::middleware(['auth', 'verified', 'role:customer'])->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/cart/{cartItem}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{cartItem}', [CartController::class, 'destroy'])->name('cart.destroy');

    Route::get('/address', [CustomerAddressController::class, 'edit'])->name('address.edit');
    Route::post('/address', [CustomerAddressController::class, 'update'])->name('address.update');

    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout/location', [CheckoutController::class, 'resolveLocation'])->name('checkout.location');
    Route::post('/checkout/quote', [CheckoutController::class, 'quoteRequest'])->name('checkout.quote');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/orders', [CustomerOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [CustomerOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/payments', [PaymentAttemptController::class, 'store'])->name('payments.store');
});

Route::post('/midtrans/callback', MidtransCallbackController::class)->name('midtrans.callback');

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
        Route::put('product-images/reorder', [ProductImageController::class, 'reorder'])
            ->middleware('permission:manage_products')
            ->name('product-images.reorder');
        Route::resource('product-images', ProductImageController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middleware('permission:manage_products');
        Route::resource('users', UserController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middleware('permission:manage_users');
        Route::get('roles', [RoleController::class, 'index'])
            ->middleware('permission:manage_users')
            ->name('roles.index');
        Route::get('system-settings', [SystemSettingController::class, 'index'])
            ->middleware('permission:manage_system_settings')
            ->name('system-settings.index');
        Route::put('system-settings', [SystemSettingController::class, 'update'])
            ->middleware('permission:manage_system_settings')
            ->name('system-settings.update');
        Route::resource('vouchers', VoucherController::class)
            ->except(['show'])
            ->middleware('permission:manage_vouchers');
        Route::resource('orders', AdminOrderController::class)
            ->only(['index', 'show'])
            ->middleware('permission:manage_orders');
        Route::resource('payments', AdminPaymentController::class)
            ->only(['index', 'show'])
            ->middleware('permission:manage_payments');
        Route::resource('shipping-areas', ShippingAreaController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['shipping-areas' => 'shippingArea'])
            ->middleware('permission:manage_shipping_areas');
        Route::redirect('shipments', '/dashboard/orders')
            ->middleware('permission:manage_orders')
            ->name('shipments.index');
        Route::put('shipments/{order}', [ShipmentController::class, 'update'])
            ->middleware('permission:manage_orders')
            ->name('shipments.update');
    });

require __DIR__.'/auth.php';
