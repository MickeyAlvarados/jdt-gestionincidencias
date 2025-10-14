<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\ChatController;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('/kit', function () {
    return Inertia::render('Kit/Index');
})->middleware(['auth'])->name('kit.index');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');
Route::middleware('auth')->group(function () {
    Route::prefix('users')->controller(UserController::class)->name('users.')->group(function () {
        Route::get('/all', 'all')->name('all');
        Route::get('/list', 'list')->name('list');
    });
    Route::resource('users', UserController::class)->only('index', 'store', 'show', 'destroy');

    Route::prefix('roles')->controller(RolController::class)->name('roles.')->group(function () {
        Route::get('/getRoles', 'getRoles')->name('getRoles');
        Route::get('/list', 'list')->name('list');
    });
    Route::resource('roles', RolController::class)->only('index', 'store', 'show', 'destroy');

    Route::prefix('modulos')->controller(ModuloController::class)->name('modulos.')->group(function () {
        Route::get('/getModulosPadres', 'getModulosPadres')->name('getModulosPadres');
        Route::get('/list', 'list')->name('list');
    });
    Route::resource('modulos', ModuloController::class)->only('index', 'store', 'show', 'destroy');

    Route::prefix('permissions')->controller(PermisoController::class)->name('permissions.')->group(function () {
        Route::get('/getModulosPadres', 'getModulosPadres')->name('getModulosPadres');
        Route::get('/list', 'list')->name('list');
        Route::get('/listRole', 'listRole')->name('listRole');
        Route::post('/save', 'save')->name('save');
    });
    Route::resource('permissions', PermisoController::class)->only('index', 'store', 'show', 'destroy');

    // Rutas para chat de soporte con IA
    Route::prefix('chat')->name('chat.')->middleware('auth')->group(function () {
        Route::get('/', function () {
            return Inertia::render('Chat/Index');
        })->name('index');
        Route::post('/crear-sesion', [ChatController::class, 'crearSesion'])->name('crear-sesion');
        Route::post('/{chatId}/mensaje', [ChatController::class, 'enviarMensaje'])->name('mensaje');
        Route::get('/{chatId}/mensajes', [ChatController::class, 'obtenerMensajes'])->name('mensajes');
        Route::post('/{chatId}/confirmar-resolucion', [ChatController::class, 'confirmarResolucion'])->name('confirmar-resolucion');
    });

});
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

