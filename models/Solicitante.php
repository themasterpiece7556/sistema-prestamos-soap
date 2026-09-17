<?php
class Solicitante {
    
    public function __construct(private PDO $db) {}

    // Busca un solicitante por su id. Se usa al registrar un préstamo,
    // para confirmar que el solicitante ya existe en la BD.
    public function porId(int $id): ?array {
        $s = $this->db->prepare('SELECT * FROM solicitantes WHERE id=?');
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }
}
