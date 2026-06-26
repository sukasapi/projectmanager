<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Klien extends Model
{
    use HasFactory;

    protected $table = 'kf_klien';

    protected $fillable = [
        'name',
        'contact_person',
        'email',
        'phone',
        'notes',
    ];

    /** @return HasMany<Proyek, $this> */
    public function proyek(): HasMany
    {
        return $this->hasMany(Proyek::class, 'client_id');
    }
}
