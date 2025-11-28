# Solución de Errores PHP en Laravel

## Problemas Identificados

Estás experimentando dos tipos de errores:

1. **Advertencias de extensiones Oracle** (oci8_19 y pdo_oci)
2. **Error "Could not open input file: artisan"**

---

## Solución 1: Deshabilitar Extensiones Oracle

Las extensiones `oci8_19` y `pdo_oci` son para bases de datos Oracle. Como tu proyecto usa PostgreSQL, no las necesitas.

### Pasos para deshabilitarlas:

1. **Abrir el archivo php.ini de WAMP**
   - Ruta: `c:/wamp64/bin/php/php8.3.0/php.ini`
   - Puedes abrirlo desde el icono de WAMP en la bandeja del sistema:
     - Click derecho en el icono de WAMP
     - PHP → php.ini

2. **Buscar y comentar las siguientes líneas**
   
   Busca estas líneas en el archivo:
   ```ini
   extension=oci8_19
   extension=pdo_oci
   ```
   
   Y cámbialas a (agregando `;` al inicio):
   ```ini
   ;extension=oci8_19
   ;extension=pdo_oci
   ```

3. **Guardar el archivo y reiniciar WAMP**
   - Guarda el archivo php.ini
   - Reinicia todos los servicios de WAMP (Apache y MySQL/MariaDB)

---

## Solución 2: Error "Could not open input file: artisan"

Este error ocurre cuando intentas ejecutar comandos de Laravel desde un directorio incorrecto.

### Causa
Estás ejecutando comandos PHP desde fuera del directorio del proyecto Laravel.

### Solución
Siempre asegúrate de estar en el directorio correcto antes de ejecutar comandos de Laravel:

```powershell
# Navega al directorio del proyecto
cd c:\Users\ROGER-PC\Desktop\grupo008sa\sistema_laravel

# Ahora puedes ejecutar comandos de artisan
php artisan migrate
php artisan serve
# etc.
```

### Verificación Rápida
Para verificar que estás en el directorio correcto, ejecuta:
```powershell
dir artisan
```

Si ves el archivo `artisan`, estás en el lugar correcto.

---

## Solución 3: Error "Failed opening required vendor/autoload.php"

Este error ocurre cuando las dependencias de Composer no están completamente instaladas o el archivo autoload está corrupto.

### Causa
El archivo `vendor/autoload.php` no existe o está incompleto, generalmente porque `composer install` no se completó correctamente.

### Solución
Regenera el archivo autoload ejecutando:

```powershell
# Asegúrate de estar en el directorio del proyecto
cd c:\Users\ROGER-PC\Desktop\grupo008sa\sistema_laravel

# Regenera el autoload de Composer
composer dump-autoload
```

Si el problema persiste, reinstala todas las dependencias:

```powershell
# Elimina el directorio vendor (opcional, solo si es necesario)
Remove-Item -Recurse -Force vendor

# Reinstala todas las dependencias
composer install
```

### Verificación
Después de ejecutar el comando, verifica que Laravel funcione:

```powershell
php artisan --version
```

Deberías ver algo como: `Laravel Framework 12.39.0` ✅

---

## Comandos Útiles de Laravel

Una vez solucionados los errores, estos son los comandos más comunes:

```powershell
# Iniciar el servidor de desarrollo
php artisan serve

# Ejecutar migraciones
php artisan migrate

# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Ver rutas disponibles
php artisan route:list

# Crear un controlador
php artisan make:controller NombreController

# Crear un modelo
php artisan make:model NombreModelo -m
```

---

## Verificación Final

Después de aplicar las soluciones, verifica que todo funcione:

```powershell
# 1. Navega al directorio del proyecto
cd c:\Users\ROGER-PC\Desktop\grupo008sa\sistema_laravel

# 2. Verifica la versión de PHP (no debería mostrar advertencias)
php -v

# 3. Verifica que artisan funcione
php artisan --version

# 4. Lista las rutas de tu aplicación
php artisan route:list
```

Si ya no ves las advertencias de Oracle y los comandos de artisan funcionan correctamente, ¡todo está resuelto! ✅
