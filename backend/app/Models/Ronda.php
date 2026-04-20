<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ronda extends Model
{
    protected $fillable = [
        'fecha',
        'estado',
        'firma_responsable_nombre',
        'firma_responsable_imagen',
        'firma_jefe_nombre',
        'firma_jefe_imagen',
        'firma_lider_nombre',
        'firma_lider_imagen',
        'fotos',
    ];

    protected $casts = [
        'fotos' => 'array',
    ];

    public function areas()
    {
        return $this->hasMany(RondaArea::class);
    }
}