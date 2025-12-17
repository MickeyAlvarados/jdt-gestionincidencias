erver {
    # Escucha en el puerto 80 para HTTP
    listen 80;
    listen [::]:80;

    # El dominio de tu sitio
    server_name gestionincidentes.jungledevperu.com;

    # La ruta raíz de tu proyecto CodeIgniter (la carpeta que contiene index.php)
    root /var/www/jungledev/jdt/jdt-gestionincidencias/public;

    # El orden en que Nginx buscará los archivos de índice
    index index.php index.html index.htm;

    # ---- LA REGLA CLAVE (EQUIVALENTE A .HTACCESS) ----
    # Intenta servir el archivo directamente, luego como un directorio,
    # y si falla, pasa la petición a index.php con los parámetros originales.
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # ---- CONFIGURACIÓN PARA PROCESAR PHP ----
    # Pasa los scripts PHP a FastCGI (PHP-FPM)
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;

        # ¡MUY IMPORTANTE!
        # Asegúrate de que esta ruta coincida con la versión de PHP que quieres usar.
        # Para PHP 7.1 (si lo tienes instalado):
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;

        # Otros parámetros necesarios
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # WebSocket Reverse Proxy para Laravel Reverb
    location /app {
          proxy_pass http://127.0.0.1:8080;
          proxy_http_version 1.1;
          proxy_set_header Upgrade $http_upgrade;
          proxy_set_header Connection "Upgrade";
          proxy_set_header Host $host;
          proxy_set_header X-Real-IP $remote_addr;
          proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
          proxy_set_header X-Forwarded-Proto $scheme;
          proxy_cache_bypass $http_upgrade;
          proxy_read_timeout 86400;
    }

    # Denegar el acceso a los archivos .htaccess, ya que Nginx no los usa
    location ~ /\.ht {
        deny all;
    }
}
