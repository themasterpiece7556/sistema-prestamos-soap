<?php
class Prestamo {
 public function __construct(private PDO $db) {}

    // Inserta un nuevo préstamo en la BD (por defecto queda en estado "Activo").
    // Devuelve el id del préstamo recién creado.
    public function crear(int $equipoId, int $solicitanteId, string $entrada, string $entrega, string $estado = 'Activo'): int {
        $s = $this->db->prepare('INSERT INTO prestamos(equipo_id,solicitante_id,fecha_prestamo,fecha_entrega,estado) VALUES(?,?,?,?,?)');
        $s->execute([$equipoId, $solicitanteId, $entrada, $entrega, $estado]);
        return (int)$this->db->lastInsertId();
    }

    // Devuelve el listado de todos los préstamos, incluyendo datos del equipo
    // (código, nombre) y del solicitante (documento, nombre) gracias a los JOIN.
    public function listar(): array {
        return $this->db->query(
            'SELECT p.*, e.codigo, e.nombre AS equipo_nombre, s.documento, s.nombre AS solicitante_nombre
             FROM prestamos p
             JOIN equipos e ON e.id = p.equipo_id
             JOIN solicitantes s ON s.id = p.solicitante_id
             ORDER BY p.id'
        )->fetchAll();
    }
}