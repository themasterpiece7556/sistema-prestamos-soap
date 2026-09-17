<?php
class Solicitante {
 public function __construct(private PDO $db) {}
 public function porId(int $id): ?array { $s=$this->db->prepare('SELECT * FROM solicitantes WHERE id=?');$s->execute([$id]);return $s->fetch() ?: null; }
}
