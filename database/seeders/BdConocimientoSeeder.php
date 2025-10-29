<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BdConocimientoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Poblar con soluciones comunes para que la IA aprenda del flujo conversacional
     */
    public function run(): void
    {
        $conocimientos = [
            // Problema 1: Impresora no responde
            [
                'id' => 1,
                'id_incidencia' => null,
                'descripcion_problema' => 'No puedo imprimir, la impresora no responde',
                'fecha_incidencia' => now(),
                'comentario_resolucion' => json_encode([
                    [
                        'rol' => 'usuario',
                        'contenido' => 'No puedo imprimir, la impresora no responde'
                    ],
                    [
                        'rol' => 'ia',
                        'contenido' => 'Entiendo tu problema. Voy a ayudarte a solucionarlo. Primero, ¿puedes verificar si la impresora está encendida y tiene las luces indicadoras activas?'
                    ],
                    [
                        'rol' => 'usuario',
                        'contenido' => 'Sí, está encendida y tiene una luz verde'
                    ],
                    [
                        'rol' => 'ia',
                        'contenido' => 'Perfecto. Ahora sigue estos pasos: 1. Ve a "Configuración" > "Dispositivos" > "Impresoras y escáneres". 2. Localiza tu impresora en la lista. 3. Haz clic derecho sobre ella y selecciona "Establecer como predeterminada". ¿Puedes hacer eso?'
                    ],
                    [
                        'rol' => 'usuario',
                        'contenido' => 'Ya la establecí como predeterminada pero sigue sin funcionar'
                    ],
                    [
                        'rol' => 'ia',
                        'contenido' => 'Entendido. Ahora vamos a reiniciar el servicio de cola de impresión: 1. Presiona Windows + R. 2. Escribe "services.msc" y presiona Enter. 3. Busca "Cola de impresión" en la lista. 4. Haz clic derecho y selecciona "Reiniciar". 5. Luego intenta imprimir una página de prueba.'
                    ],
                    [
                        'rol' => 'usuario',
                        'contenido' => 'Funcionó! Ya imprime correctamente'
                    ],
                    [
                        'rol' => 'ia',
                        'contenido' => 'Excelente! El problema estaba en el servicio de cola de impresión que necesitaba reiniciarse. Si vuelves a tener este problema, ya sabes cómo solucionarlo. ¿Hay algo más en lo que pueda ayudarte?'
                    ],
                    [
                        'rol' => 'usuario',
                        'contenido' => 'No, eso es todo. Gracias!'
                    ]
                ]),
                'empleado_resolutor' => 'Agente IA',
            ],

            // Problema 2: Computadora lenta
            [
                'id' => 2,
                'id_incidencia' => null,
                'descripcion_problema' => 'Mi computadora está muy lenta',
                'fecha_incidencia' => now(),
                'comentario_resolucion' => json_encode([
                    [
                        'rol' => 'usuario',
                        'contenido' => 'Mi computadora está muy lenta, tarda mucho en abrir programas'
                    ],
                    [
                        'rol' => 'ia',
                        'contenido' => 'Vamos a revisar qué está causando la lentitud. Primero, presiona Ctrl + Shift + Esc para abrir el Administrador de tareas. ¿Qué porcentaje de CPU y memoria RAM estás viendo?'
                    ],
                    [
                        'rol' => 'usuario',
                        'contenido' => 'CPU está al 80% y la memoria al 95%'
                    ],
                    [
                        'rol' => 'ia',
                        'contenido' => 'Esos valores son muy altos. En la pestaña "Procesos", ¿cuáles son los 3 programas que más recursos están consumiendo?'
                    ],
                    [
                        'rol' => 'usuario',
                        'contenido' => 'Chrome con muchas pestañas, Microsoft Teams y un programa que dice "Windows Update"'
                    ],
                    [
                        'rol' => 'ia',
                        'contenido' => 'Perfecto, veo el problema. Te recomiendo: 1. Cierra algunas pestañas de Chrome que no estés usando. 2. Si no estás en una reunión, puedes cerrar Teams temporalmente. 3. Windows Update está instalando actualizaciones, déjalo terminar. Después de hacer esto, ¿cómo está el rendimiento?'
                    ],
                    [
                        'rol' => 'usuario',
                        'contenido' => 'Cerré pestañas y Teams, ahora la memoria bajó a 60% y va más rápido'
                    ],
                    [
                        'rol' => 'ia',
                        'contenido' => 'Excelente! Para prevenir esto en el futuro: cierra aplicaciones que no uses, y en Configuración > Aplicaciones > Inicio, desactiva programas que no necesites al arrancar Windows. También te recomiendo reiniciar tu PC al menos una vez a la semana.'
                    ]
                ]),
                'empleado_resolutor' => 'Agente IA',
            ],

            // Problema 3: Sin conexión a internet
            [
                'id' => 3,
                'id_incidencia' => null,
                'descripcion_problema' => 'No tengo conexión a internet',
                'fecha_incidencia' => now(),
                'comentario_resolucion' => json_encode([
                    [
                        'rol' => 'usuario',
                        'contenido' => 'No tengo conexión a internet, no puedo acceder a nada'
                    ],
                    [
                        'rol' => 'ia',
                        'contenido' => 'Te voy a ayudar a recuperar la conexión. ¿Estás conectado por cable de red o por WiFi?'
                    ],
                    [
                        'rol' => 'usuario',
                        'contenido' => 'Por WiFi'
                    ],
                    [
                        'rol' => 'ia',
                        'contenido' => 'Bien. Haz clic en el ícono de WiFi en la barra de tareas. ¿Ves tu red WiFi en la lista? ¿Dice "Conectado" o muestra algún otro mensaje?'
                    ],
                    [
                        'rol' => 'usuario',
                        'contenido' => 'Dice "Conectado, sin acceso a internet"'
                    ],
                    [
                        'rol' => 'ia',
                        'contenido' => 'Entiendo. El problema es con el router. Haz esto: 1. Desconecta el router de la corriente eléctrica. 2. Espera 30 segundos. 3. Vuelve a conectarlo. 4. Espera 2 minutos a que inicie completamente. ¿Puedes hacerlo?'
                    ],
                    [
                        'rol' => 'usuario',
                        'contenido' => 'Ya reinicié el router y esperé, pero sigue sin internet'
                    ],
                    [
                        'rol' => 'ia',
                        'contenido' => 'Vamos a ejecutar el solucionador de problemas de Windows: 1. Haz clic derecho en el ícono de red. 2. Selecciona "Solucionar problemas". 3. Espera a que termine el diagnóstico. ¿Qué mensaje te muestra?'
                    ],
                    [
                        'rol' => 'usuario',
                        'contenido' => 'Dice que restableció el adaptador de red y ahora funciona!'
                    ],
                    [
                        'rol' => 'ia',
                        'contenido' => 'Perfecto! El problema era con el adaptador de red de tu computadora. Si vuelve a pasar, ya sabes usar el solucionador de problemas o reiniciar el router.'
                    ]
                ]),
                'empleado_resolutor' => 'Agente IA',
            ],

            // Problema 4: Contraseña olvidada
            [
                'id' => 4,
                'id_incidencia' => null,
                'descripcion_problema' => 'Olvidé mi contraseña de Windows',
                'fecha_incidencia' => now(),
                'comentario_resolucion' => json_encode([
                    [
                        'rol' => 'usuario',
                        'contenido' => 'Olvidé mi contraseña de Windows y no puedo entrar'
                    ],
                    [
                        'rol' => 'ia',
                        'contenido' => 'Te voy a ayudar con eso. ¿Tu cuenta es una cuenta Microsoft (con correo electrónico) o una cuenta local (solo nombre de usuario)?'
                    ],
                    [
                        'rol' => 'usuario',
                        'contenido' => 'Es una cuenta Microsoft, uso mi correo del trabajo'
                    ],
                    [
                        'rol' => 'ia',
                        'contenido' => 'Perfecto, eso se puede recuperar fácilmente. Desde otro dispositivo o celular: 1. Ve a https://account.live.com/password/reset. 2. Ingresa tu correo electrónico. 3. Elige cómo quieres recibir el código de seguridad (correo alternativo o teléfono). 4. Ingresa el código que recibas. 5. Crea una nueva contraseña. ¿Tienes acceso a otro dispositivo para hacer esto?'
                    ],
                    [
                        'rol' => 'usuario',
                        'contenido' => 'Sí, lo estoy haciendo desde mi celular. Ya me llegó el código'
                    ],
                    [
                        'rol' => 'ia',
                        'contenido' => 'Genial! Ingresa el código y crea una nueva contraseña segura. Te recomiendo usar al menos 8 caracteres con mayúsculas, minúsculas, números y símbolos. Una vez que cambies la contraseña, espera 5 minutos y podrás usarla en tu computadora.'
                    ],
                    [
                        'rol' => 'usuario',
                        'contenido' => 'Listo, ya cambié la contraseña y pude entrar a Windows. Gracias!'
                    ],
                    [
                        'rol' => 'ia',
                        'contenido' => 'Excelente! Te recomiendo anotar tu contraseña en un lugar seguro o usar un gestor de contraseñas. También puedes configurar un PIN en Windows para que sea más fácil entrar.'
                    ]
                ]),
                'empleado_resolutor' => 'Agente IA',
            ],

            // Problema 5: No puede abrir archivo Excel
            [
                'id' => 5,
                'id_incidencia' => null,
                'descripcion_problema' => 'No puedo abrir un archivo de Excel',
                'fecha_incidencia' => now(),
                'comentario_resolucion' => json_encode([
                    [
                        'rol' => 'usuario',
                        'contenido' => 'No puedo abrir un archivo de Excel importante, me sale un error'
                    ],
                    [
                        'rol' => 'ia',
                        'contenido' => 'Voy a ayudarte a resolverlo. ¿Qué mensaje de error específico te muestra cuando intentas abrirlo?'
                    ],
                    [
                        'rol' => 'usuario',
                        'contenido' => 'Dice que el archivo está dañado o corrupto'
                    ],
                    [
                        'rol' => 'ia',
                        'contenido' => 'Entiendo. Vamos a intentar repararlo usando la función integrada de Excel. Sigue estos pasos: 1. Abre Excel (sin el archivo). 2. Ve a "Archivo" > "Abrir". 3. Navega hasta donde está tu archivo pero NO lo abras todavía. 4. Selecciona el archivo. 5. Haz clic en la pequeña flecha al lado del botón "Abrir". 6. Selecciona "Abrir y reparar". ¿Puedes hacer eso?'
                    ],
                    [
                        'rol' => 'usuario',
                        'contenido' => 'Sí, estoy en el paso de "Abrir y reparar", me da dos opciones: Reparar o Extraer datos'
                    ],
                    [
                        'rol' => 'ia',
                        'contenido' => 'Primero intenta con "Reparar". Esa opción intentará recuperar tanto los datos como el formato. ¿Qué sucede?'
                    ],
                    [
                        'rol' => 'usuario',
                        'contenido' => 'Funcionó! Se abrió el archivo con todos mis datos'
                    ],
                    [
                        'rol' => 'ia',
                        'contenido' => 'Excelente! Ahora guarda inmediatamente el archivo con un nombre nuevo (por ejemplo, agrega "_recuperado" al final). Esto creará una copia limpia y funcional. Te recomiendo hacer copias de seguridad regulares de archivos importantes para evitar perder información.'
                    ],
                    [
                        'rol' => 'usuario',
                        'contenido' => 'Ya lo guardé con otro nombre. Muchas gracias!'
                    ]
                ]),
                'empleado_resolutor' => 'Agente IA',
            ],

            // Problema 6: Pantalla azul (BSOD)
            [
                'id' => 6,
                'id_incidencia' => null,
                'descripcion_problema' => 'Mi computadora se reinicia con pantalla azul',
                'fecha_incidencia' => now(),
                'comentario_resolucion' => json_encode([
                    [
                        'rol' => 'usuario',
                        'contenido' => 'Mi computadora se reinicia sola y aparece una pantalla azul'
                    ],
                    [
                        'rol' => 'ia',
                        'contenido' => 'Las pantallas azules pueden tener varias causas. ¿Con qué frecuencia te sucede esto? ¿Diario, semanal, o fue solo una vez?'
                    ],
                    [
                        'rol' => 'usuario',
                        'contenido' => 'Ya pasó como 3 veces esta semana'
                    ],
                    [
                        'rol' => 'ia',
                        'contenido' => 'Es frecuente entonces. ¿Recuerdas qué estabas haciendo cuando apareció la pantalla azul? ¿Usabas algún programa específico?'
                    ],
                    [
                        'rol' => 'usuario',
                        'contenido' => 'Dos veces fue cuando estaba usando Photoshop'
                    ],
                    [
                        'rol' => 'ia',
                        'contenido' => 'Esa información es muy útil. Puede ser un problema de controladores gráficos. Vamos a actualizarlos: 1. Presiona Windows + X. 2. Selecciona "Administrador de dispositivos". 3. Expande "Adaptadores de pantalla". 4. Haz clic derecho en tu tarjeta gráfica. 5. Selecciona "Actualizar controlador". 6. Elige "Buscar automáticamente". ¿Puedes hacerlo?'
                    ],
                    [
                        'rol' => 'usuario',
                        'contenido' => 'Dice que ya tengo los controladores más recientes'
                    ],
                    [
                        'rol' => 'ia',
                        'contenido' => 'Entendido. Dado que es específico con Photoshop y frecuente, te recomiendo contactar al soporte técnico. Puede ser un problema de hardware (memoria RAM o tarjeta gráfica) que requiere revisión física. Mientras tanto, guarda tu trabajo frecuentemente y considera usar programas alternativos para tareas críticas.'
                    ],
                    [
                        'rol' => 'usuario',
                        'contenido' => 'Ok, voy a contactar a soporte. Gracias por la ayuda'
                    ]
                ]),
                'empleado_resolutor' => 'Agente IA',
            ],
        ];

        foreach ($conocimientos as $conocimiento) {
            DB::table('bd_conocimientos')->updateOrInsert(
                ['id' => $conocimiento['id']],
                $conocimiento
            );
        }

        $this->command->info('Base de conocimientos poblada con ' . count($conocimientos) . ' conversaciones de soluciones.');
    }
}
