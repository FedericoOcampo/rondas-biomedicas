<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RondaPrueba extends Model
{
    protected $fillable = [
        'ronda_equipo_id',
        'prueba_id',
        'prueba_label',
        'valor',
    ];

    public function equipo()
    {
        return $this->belongsTo(RondaEquipo::class, 'ronda_equipo_id');
    }
}