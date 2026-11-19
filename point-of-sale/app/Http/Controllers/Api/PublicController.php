<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PublicController extends Controller
{
    public function menu(): JsonResponse
    {
        $payload = Cache::remember('public.menu', 60, fn() => [
            'categories' => Category::active()
                ->with(['products' => fn($q) => $q->active()
                    ->where('show_on_web', true)
                    ->with('ingredients')
                    ->orderBy('sort_order')->orderBy('name')])
                ->orderBy('sort_order')->orderBy('name')
                ->get()
                ->filter(fn($c) => $c->products->isNotEmpty())
                ->map(fn($c) => [
                    'id' => $c->slug ?: Str::slug($c->name),
                    'label' => $c->name,
                    'title' => $c->name,
                    'subtitle' => (string) $c->subtitle,
                    'items' => $c->products->map(fn($p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'desc' => (string) $p->description,
                        'price' => (int) $p->price,
                        'image' => $p->image_path
                            ? Storage::disk(config('filesystems.product_disk'))->url($p->image_path)
                            : null,
                        'available' => $p->availablePortions() !== 0,
                    ])->values()->all(),
                ])->values()->all(),
            'tax' => [
                'enabled' => Setting::get('tax_enabled', '0') === '1',
                'rate' => (float) Setting::get('tax_rate', 0),
            ],
        ]);

        return response()->json($payload)->header('Cache-Control', 'public, max-age=30');
    }

    public function site(): JsonResponse
    {
        $lines = fn(string $key) => array_values(array_filter(
            preg_split('/\r?\n/', (string) Setting::get($key, ''))
        ));

        return response()->json([
            'name' => Setting::get('cafe_name'),
            'tagline' => Setting::get('cafe_tagline'),
            'address' => $lines('cafe_address'),
            'hours' => $lines('cafe_hours'),
            'phone' => Setting::get('cafe_phone'),
            'whatsapp' => Setting::get('cafe_whatsapp'),
            'maps_url' => Setting::get('cafe_maps_url'),
            'instagram' => Setting::get('cafe_instagram'),
        ]);
    }
}
