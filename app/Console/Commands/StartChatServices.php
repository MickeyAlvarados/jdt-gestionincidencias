<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class StartChatServices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inicia todos los servicios necesarios para el chat con IA (Reverb y Queue Worker)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('===========================================');
        $this->info('  Sistema de Chat con IA - Inicio de Servicios');
        $this->info('===========================================');
        $this->newLine();

        // Verificar configuración
        if (!env('REVERB_APP_KEY')) {
            $this->error('❌ Error: REVERB_APP_KEY no está configurado en el archivo .env');
            $this->newLine();
            $this->info('Ejecuta: php generate-reverb-credentials.php');
            $this->info('Y copia las credenciales generadas en tu archivo .env');
            return 1;
        }

        if (!env('DEEPSEEK_API_KEY') || env('DEEPSEEK_API_KEY') === 'your_deepseek_api_key_here') {
            $this->warn('⚠️  Advertencia: DEEPSEEK_API_KEY no está configurado correctamente');
            $this->info('El chat funcionará pero la IA no podrá responder.');
            $this->newLine();
        }

        $this->info('✅ Configuración verificada');
        $this->newLine();

        $this->info('Para iniciar el sistema de chat, necesitas ejecutar los siguientes comandos en terminales separadas:');
        $this->newLine();

        $this->comment('Terminal 1 - Servidor Laravel:');
        $this->line('  php artisan serve');
        $this->newLine();

        $this->comment('Terminal 2 - Servidor WebSocket (Reverb):');
        $this->line('  php artisan reverb:start');
        $this->newLine();

        $this->comment('Terminal 3 - Worker de Colas:');
        $this->line('  php artisan queue:work');
        $this->newLine();

        $this->comment('Terminal 4 - Vite (Desarrollo):');
        $this->line('  npm run dev');
        $this->newLine();

        $this->info('===========================================');
        $this->info('Una vez iniciados todos los servicios, el chat estará disponible en:');
        $this->line('  http://127.0.0.1:8000/chat');
        $this->info('===========================================');

        return 0;
    }
}
