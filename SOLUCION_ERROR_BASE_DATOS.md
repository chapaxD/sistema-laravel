# Solución: Error de Conexión a Base de Datos PostgreSQL

## Error Actual

```
SQLSTATE[08006] [7] could not translate host name "mail.tecnoweb.org.bo" to address: Host name lookup failure
```

## Problema

Laravel está intentando conectarse a una base de datos PostgreSQL remota en `mail.tecnoweb.org.bo`, pero no puede resolver el nombre del host. Esto puede deberse a:

1. **Problemas de red o DNS** - No hay conexión al servidor remoto
2. **El servidor está fuera de línea**
3. **Necesitas trabajar con una base de datos local**

---

## Solución: Configurar Base de Datos Local

Tienes PostgreSQL 17.4 instalado localmente. Te recomiendo configurar una base de datos local para desarrollo.

### Opción 1: Usar Base de Datos Local (Recomendado para desarrollo)

#### Paso 1: Crear la base de datos local

```powershell
# Conectarse a PostgreSQL como superusuario
psql -U postgres

# Dentro de psql, ejecuta estos comandos:
CREATE DATABASE db_grupo08sa;
CREATE USER grupo08sa WITH PASSWORD 'grup008grup008*';
GRANT ALL PRIVILEGES ON DATABASE db_grupo08sa TO grupo08sa;

# En PostgreSQL 15+, también necesitas:
\c db_grupo08sa
GRANT ALL ON SCHEMA public TO grupo08sa;

# Salir de psql
\q
```

#### Paso 2: Actualizar el archivo `.env`

Abre el archivo `.env` y cambia la línea `DB_HOST`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=db_grupo08sa
DB_USERNAME=grupo08sa
DB_PASSWORD=grup008grup008*
```

**Cambio clave:** `DB_HOST=mail.tecnoweb.org.bo` → `DB_HOST=127.0.0.1`

#### Paso 3: Ejecutar las migraciones

```powershell
# Limpiar la configuración en caché
php artisan config:clear

# Ejecutar las migraciones
php artisan migrate
```

---

### Opción 2: Solucionar Conexión al Servidor Remoto

Si necesitas conectarte al servidor remoto `mail.tecnoweb.org.bo`, verifica:

#### 1. Conectividad de red

```powershell
# Verificar si puedes hacer ping al servidor
ping mail.tecnoweb.org.bo

# Verificar si el puerto PostgreSQL está abierto
Test-NetConnection -ComputerName mail.tecnoweb.org.bo -Port 5432
```

#### 2. Configuración de DNS

Si el ping falla, puede ser un problema de DNS. Intenta:

- Verificar tu conexión a Internet
- Usar una VPN si estás fuera de la red de la universidad
- Contactar al administrador del servidor

#### 3. Configuración del Firewall

El servidor remoto puede tener restricciones de firewall que solo permiten conexiones desde ciertas IPs.

---

## Verificación

Después de aplicar la solución, verifica la conexión:

```powershell
# Limpiar todas las cachés
php artisan config:clear
php artisan cache:clear

# Probar la conexión a la base de datos
php artisan migrate:status

# Si todo está bien, deberías ver las tablas de migraciones
```

---

## Comandos Útiles de PostgreSQL

```powershell
# Conectarse a la base de datos
psql -U grupo08sa -d db_grupo08sa

# Listar todas las bases de datos
psql -U postgres -c "\l"

# Listar todas las tablas en la base de datos
psql -U grupo08sa -d db_grupo08sa -c "\dt"

# Ver usuarios de PostgreSQL
psql -U postgres -c "\du"
```

---

## Notas Importantes

> [!IMPORTANT]
> Si decides usar la base de datos local, asegúrate de que el servicio de PostgreSQL esté ejecutándose en tu máquina.

> [!TIP]
> Para desarrollo local, es recomendable usar `127.0.0.1` en lugar de `localhost` para evitar problemas de resolución de nombres.

> [!WARNING]
> No subas el archivo `.env` a tu repositorio Git, ya que contiene credenciales sensibles. El archivo `.gitignore` ya debería estar configurado para ignorarlo.
