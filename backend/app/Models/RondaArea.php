<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RondaArea extends Model
{
    protected $fillable = [
        'ronda_id',
        'area_id',
        'area_nombre',
        'no_realizada',
    ];

    public function ronda()
    {
        return $this->belongsTo(Ronda::class);
    }

    public function equipos()
    {
        return $this->hasMany(RondaEquipo::class);
    }
}