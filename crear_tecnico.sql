-- Crear rol Técnico si no existe
INSERT INTO rol (nombre, descripcion) 
VALUES ('Técnico', 'Personal técnico')
ON CONFLICT (nombre) DO NOTHING;

-- Crear usuario técnico
INSERT INTO usuario (nombre, apellido, email, password_hash, telefono, estado, id_rol)
VALUES (
    'Pedro',
    'Técnico',
    'pedro@tecnico.com',
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/lewKyNiLK7/Ug1Liq', -- admin1234
    '70555555',
    'ACTIVO',
    (SELECT id_rol FROM rol WHERE nombre = 'Técnico' LIMIT 1)
)
ON CONFLICT (email) DO NOTHING;
