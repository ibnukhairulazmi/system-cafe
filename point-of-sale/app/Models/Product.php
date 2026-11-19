<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id', 'sku', 'name', 'description', 'price', 'is_active',
        'image_path',
        'show_on_web',
        'sort_order'
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'show_on_web' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'product_ingredients')
            ->withPivot('quantity');
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function availablePortions(): ?int
    {
        $max = null;
        foreach ($this->ingredients as $ingredient) {
            $need = (float) $ingredient->pivot->quantity;
            if ($need <= 0) {
                continue;
            }
            $can = (int) floor((float) $ingredient->stock / $need + 1e-9);
            $max = $max === null ? $can : min($max, $can);
        }

        return $max;
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn() => $this->image_path
            ? Storage::disk(config('filesystems.product_disk'))->url($this->image_path)
            : null);
    }
}
