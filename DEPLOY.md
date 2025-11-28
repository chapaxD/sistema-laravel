# Guía de Despliegue: Sistema Laravel + Inertia + Vue (Usando Cockpit)

Esta guía está adaptada para servidores Linux gestionados a través de **Cockpit**.

## 1. Acceso a Cockpit

1.  Ingresa a tu panel de Cockpit (usualmente `https://tu-ip:9090`).
2.  Inicia sesión con tu usuario `root` o un usuario con permisos `sudo`.
3.  Ve a la pestaña **Terminal** en el menú lateral izquierdo. **Todos los comandos siguientes se ejecutarán desde ahí.**

## 2. Requisitos del Servidor

Desde la **Terminal** de Cockpit, instala los componentes necesarios:

```bash
# Actualizar lista de paquetes
sudo apt update

# Instalar PHP 8.2 y extensiones
sudo apt install -y php8.2-fpm php8.2-bcmath php8.2-ctype php8.2-fileinfo php8.2-mbstring php8.2-pdo php8.2-pgsql php8.2-tokenizer php8.2-xml php8.2-curl php8.2-zip unzip

# Instalar Nginx y PostgreSQL
sudo apt install -y nginx postgresql postgresql-contrib

# Instalar Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Instalar Node.js 18+
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
```

## 3. Preparación del Proyecto

1.  **Clonar el repositorio:**
    ```bash
    cd /var/www
    sudo git clone <url-de-tu-repo> sistema_laravel
    cd sistema_laravel
    ```

2.  **Permisos:**
    ```bash
    sudo chown -R www-data:www-data /var/www/sistema_laravel
    sudo chmod -R 775 storage bootstrap/cache
    # Añadir tu usuario al grupo www-data para poder editar archivos
    sudo usermod -a -G www-data $USER
    ```

3.  **Instalar Dependencias:**
    ```bash
    # Dependencias PHP
    composer install --optimize-autoloader --no-dev

    # Dependencias JS
    npm install
    ```

## 4. Configuración del Entorno

1.  **Archivo .env:**
    ```bash
    cp .env.example .env
    nano .env
    ```
    Edita las variables principales (usa `Ctrl+O` para guardar y `Ctrl+X` para salir):
    - `APP_ENV=production`
    - `APP_DEBUG=false`
    - `APP_URL=https://mail.tecnoweb.org.bo`
    - `DB_CONNECTION=pgsql`
    - `DB_HOST=127.0.0.1`
    - `DB_DATABASE=sistema_laravel`
    - `DB_USERNAME=usuario_db`
    - `DB_PASSWORD=tu_password`

2.  **Generar Key:**
    ```bash
    php artisan key:generate
    ```

## 5. Base de Datos (PostgreSQL)

1.  **Crear Base de Datos y Usuario:**
    ```bash
    sudo -u postgres psql
    ```
    Dentro de la consola de Postgres:
    ```sql
    CREATE DATABASE sistema_laravel;
    CREATE USER usuario_db WITH PASSWORD 'tu_password';
    GRANT ALL PRIVILEGES ON DATABASE sistema_laravel TO usuario_db;
    \q
    ```

2.  **Ejecutar Migraciones:**
    ```bash
    php artisan migrate --force
    ```

## 6. Compilación Frontend

```bash
npm run build
```

## 7. Configuración de Nginx

1.  **Crear configuración:**
    ```bash
    sudo nano /etc/nginx/sites-available/sistema_laravel
    ```
    Pega el siguiente contenido (ajusta `server_name`):

    ```nginx
    server {
        listen 80;
        server_name mail.tecnoweb.org.bo;
        root /var/www/sistema_laravel/public;

        add_header X-Frame-Options "SAMEORIGIN";
        add_header X-Content-Type-Options "nosniff";

        index index.php;

        charset utf-8;

        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }

        location = /favicon.ico { access_log off; log_not_found off; }
        location = /robots.txt  { access_log off; log_not_found off; }

        error_page 404 /index.php;

        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
            fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
            include fastcgi_params;
        }

        location ~ /\.(?!well-known).* {
            deny all;
        }
    }
    ```

2.  **Activar sitio:**
    ```bash
    sudo ln -s /etc/nginx/sites-available/sistema_laravel /etc/nginx/sites-enabled/
    sudo nginx -t
    sudo systemctl restart nginx
    ```

## 8. Gestión desde Cockpit

- **Reiniciar Servicios:** Si necesitas reiniciar Nginx o PHP, puedes ir a la pestaña **Services** en Cockpit, buscar `nginx` o `php8.2-fpm` y usar el botón de reinicio.
- **Ver Logs:** Si tienes errores 500, ve a la pestaña **Logs** en Cockpit y filtra por "Critical" o busca errores relacionados con `php` o `nginx`.
- **Almacenamiento:** En la pestaña **Storage** puedes monitorear el espacio en disco.

## 9. SSL (HTTPS)

Desde la terminal de Cockpit:
```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d mail.tecnoweb.org.bo
```

## 10. Despliegue en Hosting Compartido (cPanel/Plesk)

Si tu servidor es compartido y no tienes acceso a terminal o `sudo`, sigue estos pasos:

1.  **Verificar Requisitos:**
    - Sube el archivo `server_check.php` (incluido en este proyecto) a tu carpeta `public_html`.
    - Visita `https://mail.tecnoweb.org.bo/server_check.php` para ver qué tienes instalado.

2.  **Si NO tienes Node.js/NPM en el servidor:**
    - Debes compilar el frontend en TU computadora local:
      ```bash
      npm run build
      ```
    - Esto generará la carpeta `public/build`. Esa carpeta es la que debes subir.

3.  **Si NO tienes Composer en el servidor:**
    - Ejecuta en tu computadora local:
      ```bash
      composer install --optimize-autoloader --no-dev
      ```
    - Sube toda la carpeta `vendor` al servidor.

4.  **Estructura de Archivos en Hosting Compartido:**
    - Sube todo el contenido de tu proyecto a una carpeta privada (ej. `/home/usuario/sistema_laravel`).
    - Mueve el contenido de `public/` a tu carpeta pública (ej. `/home/usuario/public_html`).
    - Edita `public_html/index.php` para apuntar a las rutas correctas:
      ```php
      require __DIR__.'/../sistema_laravel/vendor/autoload.php';
      $app = require_once __DIR__.'/../sistema_laravel/bootstrap/app.php';
      ```
