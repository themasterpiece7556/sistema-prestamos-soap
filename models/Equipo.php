<?php
class Equipo {
 public function __construct(private PDO $db) {}
 public function porId(int $id): ?array { $s=$this->db->prepare('SELECT * FROM equipos WHERE id=?');$s->execute([$id]);return $s->fetch() ?: null; }
 public function porCodigo(string $codigo): ?array { $s=$this->db->prepare('SELECT * FROM equipos WHERE codigo=?');$s->execute([$codigo]);return $s->fetch() ?: null; }
 public function listar(): array { return $this->db->query('SELECT * FROM equipos ORDER BY id')->fetchAll(); }
 public function crear(string $codigo,string $nombre,string $tipo,string $estado): int { $s=$this->db->prepare('INSERT INTO equipos(codigo,nombre,tipo,estado) VALUES(?,?,?,?)');$s->execute([$codigo,$nombre,$tipo,$estado]);return (int)$this->db->lastInsertId(); }
 public function actualizar(int $id,string $nombre,string $tipo,string $estado): bool { $s=$this->db->prepare('UPDATE equipos SET nombre=?,tipo=?,estado=? WHERE id=?');return $s->execute([$nombre,$tipo,$estado,$id]); }
 public function cambiarEstado(int $id,string $estado): bool { $s=$this->db->prepare('UPDATE equipos SET estado=? WHERE id=?');return $s->execute([$estado,$id]); }
}
