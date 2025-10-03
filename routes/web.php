<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\AulaController;
use App\Http\Controllers\GradoController;
use App\Http\Controllers\SeccionController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\CursoController;
use App\Http\Controllers\MotivoInasistenciaController;
use App\Http\Controllers\TipoAsistenciaController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\LicenciaPermisoController;
use App\Http\Controllers\QrController;
use App\Http\Controllers\ReporteAsistenciaController;
use App\Http\Controllers\DocenteAsistenciaController;
use App\Http\Controllers\DocenteDashboardController;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

// Ruta pública para registro de asistencia (sin login requerido)
Route::get('/attendance', function () {
    return Inertia::render('PublicAttendance');
})->name('attendance.public');

// Nueva ruta para manejar enlaces QR con token
Route::get('/attendance/{token}', function ($token) {
    $qrToken = \App\Models\QrToken::where('token', $token)
        ->with(['horario.docente', 'horario.curso', 'horario.seccion', 'horario.aula', 'docente'])
        ->first();

    $info = null;
    $tipoToken = null;

    if ($qrToken) {
        if ($qrToken->docente) {
            // Token generado por docente
            $tipoToken = 'docente';
            $info = [
                'docente' => [
                    'nombres' => $qrToken->docente->nombres,
                    'apellidos' => $qrToken->docente->apellidos,
                    'codigo_docente' => $qrToken->docente->codigo_docente,
                ],
                'tipo' => $qrToken->qr_data['tipo'] ?? 'entrada',
            ];
        } elseif ($qrToken->horario) {
            // Token generado por horario (legacy)
            $tipoToken = 'horario';
            $info = [
                'docente' => $qrToken->horario->docente ? [
                    'nombres' => $qrToken->horario->docente->nombres,
                    'apellidos' => $qrToken->horario->docente->apellidos,
                ] : null,
                'curso' => $qrToken->horario->curso ? [
                    'nombre_curso' => $qrToken->horario->curso->nombre_curso,
                ] : null,
            ];
        }
    }

    return Inertia::render('PublicAttendance', [
        'token' => $token,
        'tipo_token' => $tipoToken,
        'info' => $info
    ]);
})->name('attendance.token');
Route::get('/kit', function () {
    return Inertia::render('Kit/Index');
})->middleware(['auth'])->name('kit.index');
Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');
Route::get('qr-scanner', function () {
    return Inertia::render('QrScanner');
})->middleware(['auth'])->name('qr-scanner');
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

    // Rutas para dashboard docente (DEBEN IR ANTES del resource route)
    Route::prefix('docentes')->name('docentes.')->middleware(['auth'])->group(function () {
        Route::get('/dashboard', [DocenteDashboardController::class, 'index'])->name('dashboard.index')->middleware(['role:DOCENTE', 'docente.permission:docentes.dashboard.index']);
        Route::post('/dashboard/generate-qr', [DocenteDashboardController::class, 'generateQr'])->name('dashboard.generate-qr')->middleware(['role:DOCENTE', 'docente.permission:docentes.dashboard.generate-qr']);
        Route::get('/dashboard/curso/{cursoId}', [DocenteDashboardController::class, 'showCurso'])->name('dashboard.curso.show')->middleware(['role:DOCENTE', 'docente.permission:docentes.dashboard.index']);
    });

    Route::prefix('docentes')->controller(DocenteController::class)->name('docentes.')->group(function () {
        Route::get('/init-docentes', 'initDocentes')->name('init-docentes');
        Route::get('/list', 'list')->name('list');
        Route::get('/all', 'all')->name('all');
    });
    Route::resource('docentes', DocenteController::class)->only('index', 'store', 'show', 'destroy');

    Route::prefix('aulas')->controller(AulaController::class)->name('aulas.')->group(function () {
        Route::get('/init-aulas', 'initAulas')->name('init-aulas');
        Route::get('/list', 'list')->name('list');
    });
    Route::resource('aulas', AulaController::class)->only('index', 'store', 'show', 'destroy');

    Route::prefix('grados')->controller(GradoController::class)->name('grados.')->group(function () {
        Route::get('/init-grados', 'initGrados')->name('init-grados');
        Route::get('/list', 'list')->name('list');
    });
    Route::resource('grados', GradoController::class)->only('index', 'store', 'show', 'destroy');

    Route::prefix('secciones')->controller(SeccionController::class)->name('secciones.')->group(function () {
        Route::get('/init-secciones', 'initSecciones')->name('init-secciones');
        Route::get('/list', 'list')->name('list');
    });
    Route::resource('secciones', SeccionController::class)->only('index', 'store', 'show', 'destroy');

    Route::prefix('areas')->controller(AreaController::class)->name('areas.')->group(function () {
        Route::get('/list', 'list')->name('list');
    });
    Route::resource('areas', AreaController::class)->only('index', 'store', 'show', 'destroy');

    Route::prefix('cursos')->controller(CursoController::class)->name('cursos.')->group(function () {
        Route::get('/init-cursos', 'initCursos')->name('init-cursos');
        Route::get('/list', 'list')->name('list');
    });
    Route::resource('cursos', CursoController::class)->only('index', 'store', 'show', 'destroy');

    Route::prefix('motivo-inasistencias')->controller(MotivoInasistenciaController::class)->name('motivo-inasistencias.')->group(function () {
        Route::get('/list', 'list')->name('list');
    });
    Route::resource('motivo-inasistencias', MotivoInasistenciaController::class)->only('index', 'store', 'show', 'destroy');

    Route::prefix('tipo-asistencias')->controller(TipoAsistenciaController::class)->name('tipo-asistencias.')->group(function () {
        Route::get('/list', 'list')->name('list');
    });
    Route::resource('tipo-asistencias', TipoAsistenciaController::class)->only('index', 'store', 'show', 'destroy');

    Route::prefix('horarios')->controller(HorarioController::class)->name('horarios.')->middleware(['auth', 'permission:horarios.index'])->group(function () {
        Route::get('/init-horarios', 'initHorarios')->name('init-horarios');
        Route::get('/list', 'list')->name('list');
        Route::post('/generate-qr', 'generateQr')->name('generate-qr');
    });
    Route::resource('horarios', HorarioController::class)->only('index', 'store', 'show', 'destroy')->middleware(['auth', 'permission:horarios.index']);

    Route::prefix('licencias-permisos')->controller(LicenciaPermisoController::class)->name('licencias-permisos.')->middleware(['auth', 'permission:licencias-permisos.index'])->group(function () {
        Route::get('/init-licencias', 'initLicencias')->name('init-licencias');
        Route::get('/list', 'list')->name('list');
    });
    Route::resource('licencias-permisos', LicenciaPermisoController::class)->only('index', 'store', 'show', 'destroy')->middleware(['auth', 'permission:licencias-permisos.index']);

    // Rutas para códigos QR
    Route::prefix('qr')->controller(QrController::class)->name('qr.')->group(function () {
        Route::post('/generate', 'generate')->name('generate');
        Route::post('/generate-for-docente', 'generateForDocente')->name('generate-for-docente');
        Route::post('/validate', 'validate')->name('validate');
        Route::post('/confirm', 'confirm')->name('confirm');
        Route::get('/token/{token}', 'show')->name('show');
        // Nueva ruta pública para docentes (sin middleware auth)
        Route::post('/attendance', 'markAttendance')->name('attendance')->withoutMiddleware(['auth']);
        Route::post('/attendance-docente', 'markAttendanceForDocente')->name('attendance-docente')->withoutMiddleware(['auth']);
    });

    // Rutas para reportes de asistencia
    Route::prefix('reportes')->controller(ReporteAsistenciaController::class)->name('reportes.')->middleware(['auth', 'permission:reportes.asistencia.index'])->group(function () {
        Route::get('/asistencia', 'index')->name('asistencia.index');
        Route::get('/asistencia/estadisticas', 'estadisticasGenerales')->name('asistencia.estadisticas');
        Route::get('/asistencia/docente/{id}', 'asistenciasDocente')->name('asistencia.docente');
        Route::get('/asistencia/por-fecha', 'asistenciasPorFecha')->name('asistencia.por-fecha');
        Route::get('/asistencia/tokens-activos', 'tokensActivos')->name('asistencia.tokens-activos');
        Route::get('/asistencia/tiempo-real', 'asistenciasTiempoReal')->name('asistencia.tiempo-real');
        Route::get('/asistencia/pdf', 'generarPdf')->name('asistencia.pdf');
    });

    // Rutas para módulo docente de asistencias
    Route::prefix('docente')->name('docente.')->middleware('auth')->group(function () {
        Route::prefix('asistencia')->controller(DocenteAsistenciaController::class)->name('asistencia.')->group(function () {
            Route::get('/', 'index')->name('index')->middleware('permission:docente.asistencia.index');
            Route::get('/scanner', function () {
                return Inertia::render('Docente/Asistencia/Scanner');
            })->name('scanner')->middleware('permission:docente.asistencia.scanner');
            Route::post('/generar-qr', 'generarQr')->name('generar-qr')->middleware('permission:docente.asistencia.generar-qr');
            Route::post('/marcar', 'marcarAsistencia')->name('marcar')->middleware('permission:docente.asistencia.marcar');
            Route::get('/mis-asistencias', 'misAsistencias')->name('mis-asistencias')->middleware('permission:docente.asistencia.index');
        });
    });

});
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

