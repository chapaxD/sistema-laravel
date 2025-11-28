-- Script para crear datos de prueba
-- Ejecutar en pgAdmin o psql

-- 1. Crear rol Cliente si no existe
INSERT INTO rol (nombre, descripcion) 
VALUES ('Cliente', 'Cliente del sistema')
ON CONFLICT (nombre) DO NOTHING;

-- 2. Crear un usuario cliente de prueba
INSERT INTO usuario (nombre, apellido, email, password_hash, telefono, estado, id_rol)
VALUES (
    'Carlos',
    'Pérez',
    'carlos@cliente.com',
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/lewKyNiLK7/Ug1Liq',
    '70123456',
    'ACTIVO',
    (SELECT id_rol FROM rol WHERE nombre = 'Cliente' LIMIT 1)
)
ON CONFLICT (email) DO NOTHING;

-- 3. Crear una categoría de productos
INSERT INTO categoria (nombre, descripcion)
VALUES ('Repuestos', 'Repuestos y piezas')
ON CONFLICT (nombre) DO NOTHING;

-- 4. Crear algunos productos de prueba
INSERT INTO producto (nombre, descripcion, precio_unit, estado, id_categoria)
VALUES 
    ('Tornillo M8', 'Tornillo métrico 8mm', 2.50, 'DISPONIBLE', (SELECT id_categoria FROM categoria WHERE nombre = 'Repuestos' LIMIT 1)),
    ('Cable UTP Cat6', 'Cable de red categoría 6', 15.00, 'DISPONIBLE', (SELECT id_categoria FROM categoria WHERE nombre = 'Repuestos' LIMIT 1)),
    ('Conector RJ45', 'Conector para cable UTP', 1.50, 'DISPONIBLE', (SELECT id_categoria FROM categoria WHERE nombre = 'Repuestos' LIMIT 1))
ON CONFLICT DO NOTHING;

-- 5. Crear algunos servicios de prueba
INSERT INTO servicio (nombre, descripcion, tipo, subtipo)
VALUES 
    ('Instalación de Red', 'Instalación de red de datos', 'Instalación', 'Redes'),
    ('Mantenimiento Preventivo', 'Mantenimiento preventivo de equipos', 'Mantenimiento', 'General'),
    ('Reparación de PC', 'Reparación de computadoras', 'Reparación', 'Hardware')
ON CONFLICT DO NOTHING;

-- Verificar datos creados
SELECT 'USUARIOS' as tabla, COUNT(*) as total FROM usuario WHERE id_rol = (SELECT id_rol FROM rol WHERE nombre = 'Cliente')
UNION ALL
SELECT 'PRODUCTOS', COUNT(*) FROM producto
UNION ALL
SELECT 'SERVICIOS', COUNT(*) FROM servicio;
