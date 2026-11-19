<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CafeTable extends Model
{
    protected $fillable = ['name', 'area', 'capacity', 'is_active', 'is_occupied', 'occupied_at', 'occupied_by'];

    protected $casts = [
        'is_active'   => 'boolean',
        'is_occupied' => 'boolean',
        'occupied_at' => 'datetime',
    ];

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
    
    public function markOccupied(?int $userId = null): void
    {
        if ($this->is_occupied) {
            return;
        }

        $this->update([
            'is_occupied' => true,
            'occupied_at' => now(),
            'occupied_by' => $userId ?? auth()->id(),
        ]);
    }

    public function markAvailable(): void
    {
        $this->update(['is_occupied' => false, 'occupied_at' => null, 'occupied_by' => null]);
    }
}
