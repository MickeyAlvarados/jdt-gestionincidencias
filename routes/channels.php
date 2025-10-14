<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    // Verificar que el chat existe
    $chat = \App\Models\Chat::find($chatId);

    if (!$chat) {
        return false;
    }

    // Verificar que el usuario sea el propietario del chat
    return $chat->user_id === $user->id;
});
