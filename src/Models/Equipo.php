<?php

namespace App\Models;

use PDO;

/**
 * Modelo de acceso a datos para la tabla `equipos`.
 * No contiene lógica SOAP: solo persistencia.
 */
class Equipo
{
    private PDO $db;

    /** Estados válidos para un equipo. */
    public const ESTADOS_VALIDOS = ['Disponible', 'Prestado', 'Mantenimiento'];

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Verifica si ya existe un equipo con el código dado.
     * $excluirId permite ignorar el propio registro al actualizar.
     */
    public function existeCodigo(string $codigo, ?int $excluirId = null): bool
    {
        if ($excluirId !== null) {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM equipos WHERE codigo = :codigo AND id != :id'
            );
            $stmt->execute(['codigo' => $codigo, 'id' => $excluirId]);
        } else {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM equipos WHERE codigo = :codigo'
            );
            $stmt->execute(['codigo' => $codigo]);
        }

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Inserta un nuevo equipo. Devuelve el id generado.
     * Lanza \InvalidArgumentException si el código ya existe o el estado es inválido.
     */
    public function crear(array $datos): int
    {
        $codigo = trim($datos['codigo'] ?? '');
        $nombre = trim($datos['nombre'] ?? '');
        $tipo   = trim($datos['tipo'] ?? '');
        $estado = trim($datos['estado'] ?? 'Disponible');

        if ($codigo === '' || $nombre === '' || $tipo === '') {
            throw new \InvalidArgumentException('codigo, nombre y tipo son obligatorios.');
        }

        if (!in_array($estado, self::ESTADOS_VALIDOS, true)) {
            throw new \InvalidArgumentException(
                'Estado inválido. Valores permitidos: ' . implode(', ', self::ESTADOS_VALIDOS)
            );
        }

        if ($this->existeCodigo($codigo)) {
            throw new \InvalidArgumentException("Ya existe un equipo con el código '{$codigo}'.");
        }

        $stmt = $this->db->prepare(
            'INSERT INTO equipos (codigo, nombre, tipo, estado) VALUES (:codigo, :nombre, :tipo, :estado)'
        );
        $stmt->execute([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'tipo'   => $tipo,
            'estado' => $estado,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualiza los datos y/o el estado de un equipo existente.
     * Lanza \InvalidArgumentException si el equipo no existe, el código ya
     * pertenece a otro equipo, o el estado es inválido.
     */
    public function actualizar(int $id, array $datos): bool
    {
        $actual = $this->obtenerPorId($id);
        if ($actual === null) {
            throw new \InvalidArgumentException("No existe un equipo con id {$id}.");
        }

        $codigo = trim($datos['codigo'] ?? $actual['codigo']);
        $nombre = trim($datos['nombre'] ?? $actual['nombre']);
        $tipo   = trim($datos['tipo'] ?? $actual['tipo']);
        $estado = trim($datos['estado'] ?? $actual['estado']);

        if ($codigo === '' || $nombre === '' || $tipo === '') {
            throw new \InvalidArgumentException('codigo, nombre y tipo no pueden estar vacíos.');
        }

        if (!in_array($estado, self::ESTADOS_VALIDOS, true)) {
            throw new \InvalidArgumentException(
                'Estado inválido. Valores permitidos: ' . implode(', ', self::ESTADOS_VALIDOS)
            );
        }

        if ($this->existeCodigo($codigo, $id)) {
            throw new \InvalidArgumentException("Ya existe otro equipo con el código '{$codigo}'.");
        }

        $stmt = $this->db->prepare(
            'UPDATE equipos SET codigo = :codigo, nombre = :nombre, tipo = :tipo, estado = :estado WHERE id = :id'
        );

        return $stmt->execute([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'tipo'   => $tipo,
            'estado' => $estado,
            'id'     => $id,
        ]);
    }

    /** Cambia únicamente el estado de un equipo (usado al prestar/devolver). */
    public function actualizarEstado(int $id, string $estado): bool
    {
        if (!in_array($estado, self::ESTADOS_VALIDOS, true)) {
            throw new \InvalidArgumentException(
                'Estado inválido. Valores permitidos: ' . implode(', ', self::ESTADOS_VALIDOS)
            );
        }

        $stmt = $this->db->prepare('UPDATE equipos SET estado = :estado WHERE id = :id');

        return $stmt->execute(['estado' => $estado, 'id' => $id]);
    }

    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, codigo, nombre, tipo, estado FROM equipos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();

        return $fila !== false ? $fila : null;
    }

    public function obtenerPorCodigo(string $codigo): ?array
    {
        $stmt = $this->db->prepare('SELECT id, codigo, nombre, tipo, estado FROM equipos WHERE codigo = :codigo');
        $stmt->execute(['codigo' => $codigo]);
        $fila = $stmt->fetch();

        return $fila !== false ? $fila : null;
    }

    public function listarTodos(): array
    {
        $stmt = $this->db->query('SELECT id, codigo, nombre, tipo, estado FROM equipos ORDER BY id ASC');

        return $stmt->fetchAll();
    }
}
