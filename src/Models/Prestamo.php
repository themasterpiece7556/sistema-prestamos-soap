<?php

namespace App\Models;

use PDO;

/**
 * Modelo de acceso a datos para la tabla `prestamos`.
 * Los préstamos nunca se eliminan: se conserva el historial completo,
 * cambiando únicamente su estado (Activo / Devuelto / Cancelado).
 */
class Prestamo
{
    public const ESTADOS_VALIDOS = ['Activo', 'Devuelto', 'Cancelado'];

    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function crear(int $equipoId, int $solicitanteId): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO prestamos (equipo_id, solicitante_id, fecha_prestamo, estado)
             VALUES (:equipo_id, :solicitante_id, NOW(), :estado)'
        );
        $stmt->execute([
            'equipo_id'      => $equipoId,
            'solicitante_id' => $solicitanteId,
            'estado'         => 'Activo',
        ]);

        return (int) $this->db->lastInsertId();
    }

    /** Marca un préstamo activo como devuelto y registra la fecha de entrega. */
    public function marcarDevuelto(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE prestamos SET estado = 'Devuelto', fecha_entrega = NOW()
             WHERE id = :id AND estado = 'Activo'"
        );
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    private function baseSelect(): string
    {
        return "SELECT
                    p.id,
                    p.equipo_id,
                    e.codigo   AS equipo_codigo,
                    e.nombre   AS equipo_nombre,
                    e.tipo     AS equipo_tipo,
                    p.solicitante_id,
                    u.documento AS solicitante_documento,
                    u.nombre    AS solicitante_nombre,
                    u.correo    AS solicitante_correo,
                    p.fecha_prestamo,
                    p.fecha_entrega,
                    p.estado
                FROM prestamos p
                INNER JOIN equipos  e ON e.id = p.equipo_id
                INNER JOIN usuarios u ON u.id = p.solicitante_id";
    }

    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->db->prepare($this->baseSelect() . ' WHERE p.id = :id');
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();

        return $fila !== false ? $fila : null;
    }

    /**
     * Lista préstamos, con filtros opcionales por estado, equipo o solicitante.
     * Pasar cadena vacía / null / 0 en un filtro significa "sin filtrar por ese campo".
     */
    public function listar(?string $estado = null, ?int $equipoId = null, ?int $solicitanteId = null): array
    {
        $sql  = $this->baseSelect();
        $cond = [];
        $params = [];

        if ($estado !== null && $estado !== '') {
            if (!in_array($estado, self::ESTADOS_VALIDOS, true)) {
                throw new \InvalidArgumentException(
                    'Estado inválido. Valores permitidos: ' . implode(', ', self::ESTADOS_VALIDOS)
                );
            }
            $cond[] = 'p.estado = :estado';
            $params['estado'] = $estado;
        }

        if (!empty($equipoId)) {
            $cond[] = 'p.equipo_id = :equipo_id';
            $params['equipo_id'] = $equipoId;
        }

        if (!empty($solicitanteId)) {
            $cond[] = 'p.solicitante_id = :solicitante_id';
            $params['solicitante_id'] = $solicitanteId;
        }

        if (!empty($cond)) {
            $sql .= ' WHERE ' . implode(' AND ', $cond);
        }

        $sql .= ' ORDER BY p.fecha_prestamo DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}
