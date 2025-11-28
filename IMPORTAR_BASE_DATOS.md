# Guía Rápida: Configurar Base de Datos Local

## ✅ Ya completaste
- [x] Cambiar `DB_HOST=127.0.0.1` en el archivo `.env`

---

## 📋 Pasos siguientes

### 1. Importar las tablas del sistema anterior

Abre **pgAdmin** o usa la línea de comandos:

**Opción A: Usando pgAdmin**
1. Abre pgAdmin
2. Conecta a tu servidor local (localhost)
3. Click derecho en la base de datos `db_grupo08sa`
4. Selecciona **Restore** o **Query Tool**
5. Ejecuta el archivo: `c:\Users\ROGER-PC\Desktop\grupo008sa\sistema_web\db.sql`

**Opción B: Línea de comandos**
```powershell
# Navega al directorio del sistema web
cd c:\Users\ROGER-PC\Desktop\grupo008sa\sistema_web

# Importa el archivo SQL
psql -U grupo08sa -d db_grupo08sa -f db.sql
# Cuando pida contraseña: grup008grup008*
```

---

### 2. Crear la tabla de sesiones de Laravel

Después de importar las tablas del sistema anterior, ejecuta este SQL:

**Opción A: Usando pgAdmin**
1. Abre Query Tool en la base de datos `db_grupo08sa`
2. Copia y pega este código:

```sql
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload TEXT NOT NULL,
    last_activity INTEGER NOT NULL
);

CREATE INDEX sessions_user_id_index ON sessions(user_id);
CREATE INDEX sessions_last_activity_index ON sessions(last_activity);
```

3. Click en **Execute** (F5)

**Opción B: Línea de comandos**
```powershell
# Desde el directorio del proyecto Laravel
cd c:\Users\ROGER-PC\Desktop\grupo008sa\sistema_laravel

# Ejecuta el script
psql -U grupo08sa -d db_grupo08sa -f create_sessions_table.sql
# Cuando pida contraseña: grup008grup008*
```

---

### 3. Limpiar cachés de Laravel

```powershell
cd c:\Users\ROGER-PC\Desktop\grupo008sa\sistema_laravel

php artisan config:clear
php artisan cache:clear
```

---

### 4. Verificar que funcione

Abre tu navegador en: **http://localhost:8000**

Deberías ver la página de login sin errores.

---

## 🔍 Verificación rápida

Para verificar que las tablas están correctamente importadas:

```sql
-- Ejecuta esto en pgAdmin o psql
SELECT table_name 
FROM information_schema.tables 
WHERE table_schema = 'public' 
ORDER BY table_name;
```

Deberías ver todas estas tablas:
- ✅ `usuario`
- ✅ `rol`
- ✅ `producto`
- ✅ `categoria`
- ✅ `servicio`
- ✅ `cotizacion`
- ✅ `detalle_cotizacion`
- ✅ `ordentrabajo`
- ✅ `venta`
- ✅ `detalle_venta`
- ✅ `recibo`
- ✅ `plan_pago`
- ✅ `cuota`
- ✅ **`sessions`** (nueva, de Laravel)

---

## ❓ Si tienes problemas

### Error: "relation sessions does not exist"
→ Ejecuta el paso 2 para crear la tabla de sesiones

### Error: "authentication failed"
→ Verifica que la contraseña sea correcta: `grup008grup008*`

### Error: "database does not exist"
→ Crea la base de datos primero:
```sql
CREATE DATABASE db_grupo08sa;
```

---

## 📝 Resumen

1. **Importar** `db.sql` del sistema anterior
2. **Crear** tabla `sessions` con el script
3. **Limpiar** cachés de Laravel
4. **Probar** en http://localhost:8000

¡Listo! Tu sistema Laravel estará usando la base de datos local con todos los datos del sistema anterior.
