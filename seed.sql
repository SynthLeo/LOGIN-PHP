-- seed.sql — Datos de prueba
-- Contraseña del admin: Admin2026!
-- Generada con: password_hash('Admin2026!', PASSWORD_BCRYPT)

INSERT INTO USUARIOS (email, password_hash, rol, activo)
VALUES (
    'admin@deporte.mx',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    1
);


INSERT INTO USUARIOS (email, password_hash, rol, activo)
VALUES
    -- Admin
    ('admin@depo.mx',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- hash de "123456"
     'admin',
     1);