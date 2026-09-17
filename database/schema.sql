CREATE DATABASE IF NOT EXISTS prestamo_equipos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE prestamo_equipos;

CREATE TABLE equipos (
 id INT AUTO_INCREMENT PRIMARY KEY,
 codigo VARCHAR(50) NOT NULL UNIQUE,
 nombre VARCHAR(120) NOT NULL,
 tipo VARCHAR(80) NOT NULL,
 estado ENUM('Disponible','Prestado','Mantenimiento') NOT NULL DEFAULT 'Disponible'
);

CREATE TABLE solicitantes (
 id INT AUTO_INCREMENT PRIMARY KEY,
 documento VARCHAR(30) NOT NULL UNIQUE,
 nombre VARCHAR(120) NOT NULL,
 correo VARCHAR(150) NOT NULL
);

CREATE TABLE prestamos (
 id INT AUTO_INCREMENT PRIMARY KEY,
 equipo_id INT NOT NULL,
 solicitante_id INT NOT NULL,
 fecha_prestamo DATETIME NOT NULL,
 fecha_entrega DATETIME NOT NULL,
 estado ENUM('Activo','Finalizado') NOT NULL DEFAULT 'Activo',
 FOREIGN KEY (equipo_id) REFERENCES equipos(id),
 FOREIGN KEY (solicitante_id) REFERENCES solicitantes(id)
);
