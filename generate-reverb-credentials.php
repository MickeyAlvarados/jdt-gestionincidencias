#!/usr/bin/env php
<?php

/**
 * Script para generar credenciales de Laravel Reverb
 * 
 * Uso: php generate-reverb-credentials.php
 */

echo "===========================================\n";
echo "  Generador de Credenciales Laravel Reverb\n";
echo "===========================================\n\n";

echo "Copia estas líneas en tu archivo .env:\n\n";

echo "# Broadcasting\n";
echo "BROADCAST_CONNECTION=reverb\n\n";

echo "# Laravel Reverb (WebSockets)\n";
echo "REVERB_APP_ID=" . rand(100000, 999999) . "\n";
echo "REVERB_APP_KEY=" . bin2hex(random_bytes(16)) . "\n";
echo "REVERB_APP_SECRET=" . bin2hex(random_bytes(32)) . "\n";
echo "REVERB_HOST=\"localhost\"\n";
echo "REVERB_PORT=8080\n";
echo "REVERB_SCHEME=http\n\n";

echo "# Variables para el frontend (Vite)\n";
echo "VITE_REVERB_APP_KEY=\"\${REVERB_APP_KEY}\"\n";
echo "VITE_REVERB_HOST=\"\${REVERB_HOST}\"\n";
echo "VITE_REVERB_PORT=\"\${REVERB_PORT}\"\n";
echo "VITE_REVERB_SCHEME=\"\${REVERB_SCHEME}\"\n\n";

echo "===========================================\n";
echo "Después de actualizar el .env, ejecuta:\n";
echo "  1. php artisan config:clear\n";
echo "  2. php artisan reverb:start\n";
echo "  3. npm run dev (en otra terminal)\n";
echo "===========================================\n";
