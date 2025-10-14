<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BdConocimientoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Poblar con soluciones comunes para que la IA aprenda
     */
    public function run(): void
    {
        $conocimientos = [
            [
                'id' => 1,
                'id_incidencia' => null,
                'descripcion_problema' => 'No puedo imprimir, la impresora no responde',
                'fecha_incidencia' => now(),
                'comentario_resolucion' => "Sigue estos pasos para solucionar el problema:\n\n1. Verifica que la impresora esté encendida y conectada correctamente\n2. Revisa que haya papel en la bandeja\n3. Ve a 'Configuración' > 'Dispositivos' > 'Impresoras y escáneres'\n4. Haz clic derecho en tu impresora y selecciona 'Establecer como predeterminada'\n5. Si el problema persiste, elimina la impresora y agrégala nuevamente\n6. Reinicia el servicio de cola de impresión: abre 'Servicios', busca 'Cola de impresión' y reinícialo",
                'empleado_resolutor' => 'Sistema',
            ],
            [
                'id' => 2,
                'id_incidencia' => null,
                'descripcion_problema' => 'Mi computadora está muy lenta',
                'fecha_incidencia' => now(),
                'comentario_resolucion' => "Para mejorar el rendimiento de tu computadora:\n\n1. Abre el Administrador de tareas (Ctrl + Shift + Esc)\n2. Revisa qué programas están consumiendo más recursos\n3. Cierra las aplicaciones que no estés usando\n4. Limpia archivos temporales: busca 'Liberador de espacio en disco'\n5. Desactiva programas de inicio innecesarios en 'Configuración' > 'Aplicaciones' > 'Inicio'\n6. Asegúrate de tener al menos 10% de espacio libre en el disco\n7. Considera reiniciar la computadora si lleva varios días encendida",
                'empleado_resolutor' => 'Sistema',
            ],
            [
                'id' => 3,
                'id_incidencia' => null,
                'descripcion_problema' => 'No tengo conexión a internet',
                'fecha_incidencia' => now(),
                'comentario_resolucion' => "Para resolver problemas de conexión a internet:\n\n1. Verifica que el cable de red esté conectado correctamente (si usas cable)\n2. Si usas WiFi, verifica que estés conectado a la red correcta\n3. Reinicia tu router/modem: desconéctalo 30 segundos y vuelve a conectarlo\n4. Ejecuta el solucionador de problemas de red de Windows:\n   - Clic derecho en el ícono de red\n   - Selecciona 'Solucionar problemas'\n5. Reinicia tu computadora\n6. Si el problema persiste, verifica con otros dispositivos si tienen internet",
                'empleado_resolutor' => 'Sistema',
            ],
            [
                'id' => 4,
                'id_incidencia' => null,
                'descripcion_problema' => 'Olvidé mi contraseña de Windows',
                'fecha_incidencia' => now(),
                'comentario_resolucion' => "Para recuperar tu contraseña:\n\n1. Si usas cuenta Microsoft:\n   - Ve a https://account.live.com/password/reset\n   - Sigue las instrucciones para restablecer tu contraseña\n   - Usa tu correo o teléfono de recuperación\n\n2. Si usas cuenta local:\n   - Necesitarás ayuda del administrador del sistema\n   - Contacta al departamento de IT para que restablezcan tu contraseña\n\n3. Prevención futura:\n   - Configura preguntas de seguridad\n   - Vincula un correo de recuperación\n   - Considera usar un gestor de contraseñas",
                'empleado_resolutor' => 'Sistema',
            ],
            [
                'id' => 5,
                'id_incidencia' => null,
                'descripcion_problema' => 'No puedo abrir un archivo de Excel',
                'fecha_incidencia' => now(),
                'comentario_resolucion' => "Para solucionar problemas con archivos de Excel:\n\n1. Verifica que tengas Microsoft Excel instalado\n2. Intenta abrir Excel primero y luego abre el archivo desde 'Archivo' > 'Abrir'\n3. Si el archivo está dañado:\n   - Abre Excel\n   - Ve a 'Archivo' > 'Abrir'\n   - Selecciona el archivo\n   - Haz clic en la flecha junto a 'Abrir'\n   - Selecciona 'Abrir y reparar'\n4. Si el archivo está en formato antiguo (.xls), guárdalo como .xlsx\n5. Verifica que el archivo no esté bloqueado por otro usuario\n6. Asegúrate de tener permisos para acceder al archivo",
                'empleado_resolutor' => 'Sistema',
            ],
        ];

        foreach ($conocimientos as $conocimiento) {
            DB::table('bd_conocimientos')->updateOrInsert(
                ['id' => $conocimiento['id']],
                $conocimiento
            );
        }

        $this->command->info('Base de conocimientos poblada con ' . count($conocimientos) . ' soluciones.');
    }
}
