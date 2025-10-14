<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $table = 'chat';

    protected $fillable = [
        'id',
        'user_id',
        'fecha_chat',
        'estado_resolucion',
        'intento_actual',
        'solucion_propuesta_id',
    ];

    protected $casts = [
        'fecha_chat' => 'datetime',
    ];

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    public function mensajes()
    {
        return $this->hasMany(ChatMensaje::class, 'id_chat');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
