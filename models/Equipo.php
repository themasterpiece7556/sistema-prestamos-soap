<?php
class Equipo {
 // Recibe la conexión PDO para poder hacer consultas.
    public function __construct(private PDO $db) {}

    // Busca un equipo por su id. Devuelve el equipo como arreglo, o null si no existe.
    public function porId(int $id): ?array {
        $s = $this->db->prepare('SELECT * FROM equipos WHERE id=?');
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }

    // Busca un equipo por su código (el código debe ser único).
    // Se usa para verificar que no se repita un código antes de registrar uno nuevo.
    public function porCodigo(string $codigo): ?array {
        $s = $this->db->prepare('SELECT * FROM equipos WHERE codigo=?');
        $s->execute([$codigo]);
        return $s->fetch() ?: null;
    }

    // Devuelve todos los equipos registrados, ordenados por id.
    public function listar(): array {
        return $this->db->query('SELECT * FROM equipos ORDER BY id')->fetchAll();
    }

    // Inserta un nuevo equipo en la BD y devuelve el id generado.
    public function crear(string $codigo, string $nombre, string $tipo, string $estado): int {
        $s = $this->db->prepare('INSERT INTO equipos(codigo,nombre,tipo,estado) VALUES(?,?,?,?)');
        $s->execute([$codigo, $nombre, $tipo, $estado]);
        return (int)$this->db->lastInsertId();
    }

    // Actualiza los datos de un equipo existente (nombre, tipo y estado).
    public function actualizar(int $id, string $nombre, string $tipo, string $estado): bool {
        $s = $this->db->prepare('UPDATE equipos SET nombre=?,tipo=?,estado=? WHERE id=?');
        return $s->execute([$nombre, $tipo, $estado, $id]);
    }

    // Cambia solo el estado de un equipo (por ejemplo, a "Prestado" cuando se presta).
    public function cambiarEstado(int $id, string $estado): bool {
        $s = $this->db->prepare('UPDATE equipos SET estado=? WHERE id=?');
        return $s->execute([$estado, $id]);
    }
}