<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public static function storeContact(): array
    {
        $defaults = config('app.store_contact', []);
        $settings = self::valuesFor([
            'store.name',
            'store.address',
            'store.email',
            'store.whatsapp',
            'store.hours',
        ]);

        return [
            'name' => $settings['store.name'] ?? $defaults['name'] ?? 'SofaStore',
            'address' => $settings['store.address'] ?? $defaults['address'] ?? '',
            'email' => $settings['store.email'] ?? $defaults['email'] ?? '',
            'whatsapp' => $settings['store.whatsapp'] ?? $defaults['whatsapp'] ?? '',
            'hours' => $settings['store.hours'] ?? $defaults['hours'] ?? '',
        ];
    }

    public static function updateStoreContact(array $data): void
    {
        $map = [
            'store.name' => $data['name'] ?? '',
            'store.address' => $data['address'] ?? '',
            'store.email' => $data['email'] ?? '',
            'store.whatsapp' => $data['whatsapp'] ?? '',
            'store.hours' => $data['hours'] ?? '',
        ];

        foreach ($map as $key => $value) {
            self::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    public static function valuesFor(array $keys): array
    {
        if (! Schema::hasTable('system_settings')) {
            return [];
        }

        return self::query()
            ->whereIn('key', $keys)
            ->pluck('value', 'key')
            ->all();
    }
}
