<?php

namespace App\Providers;

use App\Services\Fonnte\FakeFonnteNotificationClient;
use App\Services\Fonnte\FonnteNotificationClient;
use App\Services\Fonnte\HttpFonnteNotificationClient;
use App\Services\GoogleMaps\FakeGoogleMapsClient;
use App\Services\GoogleMaps\GoogleMapsClient;
use App\Services\GoogleMaps\HttpGoogleMapsClient;
use App\Services\Midtrans\FakeMidtransPaymentGateway;
use App\Services\Midtrans\HttpMidtransPaymentGateway;
use App\Services\Midtrans\MidtransPaymentGateway;
use App\Support\Authorization\RolePermission;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MidtransPaymentGateway::class, function ($app) {
            return config('services.midtrans.driver') === 'fake'
                ? new FakeMidtransPaymentGateway()
                : new HttpMidtransPaymentGateway($app->make('Illuminate\Http\Client\Factory'));
        });

        $this->app->bind(GoogleMapsClient::class, function ($app) {
            return config('services.google_maps.driver') === 'fake'
                ? new FakeGoogleMapsClient()
                : new HttpGoogleMapsClient($app->make('Illuminate\Http\Client\Factory'));
        });

        $this->app->bind(FonnteNotificationClient::class, function ($app) {
            return config('services.fonnte.driver') === 'fake'
                ? new FakeFonnteNotificationClient()
                : new HttpFonnteNotificationClient($app->make('Illuminate\Http\Client\Factory'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach (RolePermission::permissions() as $permission) {
            Gate::define($permission, fn ($user) => $user->hasPermission($permission));
        }

        Vite::prefetch(concurrency: 3);
    }
}
