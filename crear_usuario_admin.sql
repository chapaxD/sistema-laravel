-- Crear usuario de prueba para el sistema Laravel
-- Email: jdiego@gmail.com
-- Password: admin1234

-- Primero, verificar/crear el rol de Administrador
INSERT INTO rol (nombre, descripcion) 
VALUES ('Administrador', 'Acceso completo al sistema')
ON CONFLICT DO NOTHING;

-- Crear usuario administrador
INSERT INTO usuario (nombre, apellido, email, password_hash, telefono, estado, id_rol)
VALUES (
    'Juan Diego',
    'Admin',
    'jdiego@gmail.com',
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/lewKyNiLK7/Ug1Liq',
    '70000000',
    'ACTIVO',
    (SELECT id_rol FROM rol WHERE nombre = 'Administrador' LIMIT 1)
);

-- Verificar que se creó correctamente
SELECT id_usuario, nombre, apellido, email, estado 
FROM usuario 
WHERE email = 'jdiego@gmail.com';
