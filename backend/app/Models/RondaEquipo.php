<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RondaEquipo extends Model
{
    protected $fillable = [
        'ronda_area_id',
        'equipo_id',
        'equipo_nombre',
        'placa',
        'placa2',
        'no_encontrado',
        'revision_fisica',
        'apto_para_uso',
        'observaciones',
        'ubicacion',
    ];

    public function area()
    {
        return $this->belongsTo(RondaArea::class, 'ronda_area_id');
    }

    public function pruebas()
    {
        return $this->hasMany(RondaPrueba::class);
    }
}