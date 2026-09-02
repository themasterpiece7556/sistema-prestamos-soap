-- =====================================================================
-- SISTEMA DE PRÉSTAMOS SOAP
-- Base de datos: sistema_prestamos
-- Motor: MySQL 8.x / InnoDB
-- Descripción: préstamo de equipos tecnológicos (computadores,
--              proyectores, tablets, cámaras, etc.) a estudiantes
--              y docentes, consumido exclusivamente vía servicio SOAP.
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1. CREACIÓN DE LA BASE DE DATOS
-- ---------------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS sistema_prestamos
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sistema_prestamos;

-- ---------------------------------------------------------------------
-- 2. TABLA: equipos
--    Almacena cada equipo prestable y su estado de disponibilidad.
-- ---------------------------------------------------------------------
CREATE TABLE equipos (
    id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo  VARCHAR(20)  NOT NULL COMMENT 'Identificador de inventario, ej: PC-001',
    nombre  VARCHAR(100) NOT NULL,
    tipo    VARCHAR(50)  NOT NULL COMMENT 'Ej: Computador portátil, Proyector, Tablet...',
    estado  ENUM('Disponible', 'Prestado', 'Mantenimiento') NOT NULL DEFAULT 'Disponible',
    PRIMARY KEY (id),
    UNIQUE KEY uq_equipos_codigo (codigo),
    KEY idx_equipos_estado (estado)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- 3. TABLA: usuarios (solicitantes)
--    Corresponde al modelo Usuario.php. Representa a quien solicita
--    un préstamo (estudiante o docente). Sin login ni contraseña.
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    documento VARCHAR(20)  NOT NULL COMMENT 'Número de documento de identidad',
    nombre    VARCHAR(150) NOT NULL,
    correo    VARCHAR(150) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_documento (documento)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- 4. TABLA: prestamos
--    Une equipos con solicitantes. Conserva historial completo:
--    ningún registro se elimina al devolver un equipo.
-- ---------------------------------------------------------------------
CREATE TABLE prestamos (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    equipo_id      INT UNSIGNED NOT NULL,
    solicitante_id INT UNSIGNED NOT NULL,
    fecha_prestamo DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_entrega  DATETIME NULL COMMENT 'Fecha real de devolución; NULL mientras el préstamo esté Activo',
    estado         ENUM('Activo', 'Devuelto', 'Cancelado') NOT NULL DEFAULT 'Activo',
    PRIMARY KEY (id),
    KEY idx_prestamos_equipo (equipo_id),
    KEY idx_prestamos_solicitante (solicitante_id),
    KEY idx_prestamos_estado (estado),
    CONSTRAINT fk_prestamos_equipo
        FOREIGN KEY (equipo_id) REFERENCES equipos (id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_prestamos_solicitante
        FOREIGN KEY (solicitante_id) REFERENCES usuarios (id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE = InnoDB;

-- =====================================================================
-- 5. DATOS DE PRUEBA
-- =====================================================================

-- 5 equipos, con estados variados
INSERT INTO equipos (codigo, nombre, tipo, estado) VALUES
    ('PC-001',   'Portátil Dell Latitude 5420',        'Computador portátil',   'Disponible'),
    ('PC-002',   'Portátil HP ProBook 440',             'Computador portátil',   'Prestado'),
    ('PROY-001', 'Proyector Epson PowerLite X49',       'Proyector',             'Disponible'),
    ('TAB-001',  'Tablet Samsung Galaxy Tab A8',        'Tablet',                'Mantenimiento'),
    ('CAM-001',  'Cámara Canon EOS Rebel T7',           'Cámara',                'Prestado');

-- 5 solicitantes
INSERT INTO usuarios (documento, nombre, correo) VALUES
    ('1005678901', 'Laura Marcela Rojas',   'laura.rojas@correo.edu.co'),
    ('1005678902', 'Juan David Pérez',      'juan.perez@correo.edu.co'),
    ('1005678903', 'María Camila Torres',   'maria.torres@correo.edu.co'),
    ('1005678904', 'Andrés Felipe Gómez',   'andres.gomez@correo.edu.co'),
    ('1005678905', 'Sofía Valentina Ruiz',  'sofia.ruiz@correo.edu.co');

-- 3 préstamos, coherentes con el estado de cada equipo:
--   - PC-002  (Prestado)   -> préstamo Activo, sin fecha de entrega aún
--   - CAM-001 (Prestado)   -> préstamo Activo, sin fecha de entrega aún
--   - PROY-001 (Disponible)-> préstamo histórico ya Devuelto
INSERT INTO prestamos (equipo_id, solicitante_id, fecha_prestamo, fecha_entrega, estado) VALUES
    (2, 1, '2026-08-20 09:15:00', NULL,                  'Activo'),
    (5, 2, '2026-08-27 11:00:00', NULL,                  'Activo'),
    (3, 3, '2026-08-10 08:00:00', '2026-08-15 17:00:00', 'Devuelto');
