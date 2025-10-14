<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMensaje extends Model
{
    protected $table = 'chat_mensajes';

    protected $fillable = [
        'id',
        'id_chat',
        'emisor',
        'contenido_mensaje',
        'fecha_envio',
    ];

    protected $casts = [
        'fecha_envio' => 'datetime',
    ];

    public $timestamps = false;

    // public $incrementing = false;

    protected $keyType = 'int';

    protected $primaryKey = 'id';

    public function chat()
    {
        return $this->belongsTo(Chat::class, 'id_chat');
    }

    /**
     * Relación con el usuario emisor del mensaje
     */
    public function emisorRelacion()
    {
        return $this->belongsTo(User::class, 'emisor');
    }

    /**
     * Verificar si el mensaje es de la IA
     */
    public function esDeIA(): bool
    {
        $usuarioIA = User::where('email', 'ia@support.local')->first();
        return $usuarioIA && $this->emisor == $usuarioIA->id;
    }
}