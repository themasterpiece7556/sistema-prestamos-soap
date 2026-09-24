<?php

namespace App\Models;

use PDO;

/**
 * Modelo de acceso a datos para la tabla `usuarios` (solicitantes de un préstamo).
 */
class Usuario
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, documento, nombre, correo FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();

        return $fila !== false ? $fila : null;
    }

    public function obtenerPorDocumento(string $documento): ?array
    {
        $stmt = $this->db->prepare('SELECT id, documento, nombre, correo FROM usuarios WHERE documento = :documento');
        $stmt->execute(['documento' => $documento]);
        $fila = $stmt->fetch();

        return $fila !== false ? $fila : null;
    }

    public function crear(array $datos): int
    {
        $documento = trim($datos['documento'] ?? '');
        $nombre    = trim($datos['nombre'] ?? '');
        $correo    = trim($datos['correo'] ?? '');

        if ($documento === '' || $nombre === '' || $correo === '') {
            throw new \InvalidArgumentException('documento, nombre y correo del solicitante son obligatorios.');
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("El correo '{$correo}' no es válido.");
        }

        $stmt = $this->db->prepare(
            'INSERT INTO usuarios (documento, nombre, correo) VALUES (:documento, :nombre, :correo)'
        );
        $stmt->execute([
            'documento' => $documento,
            'nombre'    => $nombre,
            'correo'    => $correo,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Devuelve el solicitante con ese documento; si no existe, lo registra
     * primero. Garantiza que todo préstamo quede asociado a un solicitante
     * registrado en la base de datos.
     */
    public function obtenerOcrear(array $datos): array
    {
        $documento = trim($datos['documento'] ?? '');
        if ($documento === '') {
            throw new \InvalidArgumentException('El documento del solicitante es obligatorio.');
        }

        $existente = $this->obtenerPorDocumento($documento);
        if ($existente !== null) {
            return $existente;
        }

        $id = $this->crear($datos);

        return $this->obtenerPorId($id);
    }

    public function listarTodos(): array
    {
        $stmt = $this->db->query('SELECT id, documento, nombre, correo FROM usuarios ORDER BY id ASC');

        return $stmt->fetchAll();
    }
}
