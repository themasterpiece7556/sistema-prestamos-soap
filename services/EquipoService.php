<?php

// se hacen las validaciones antes de tocar la base de datos.
class EquipoService {

    public function __construct(private PDO $db, private Equipo $modelo) {}

    // Función de ayuda para armar siempre la respuesta con el mismo formato:
    private function r(bool $ok, string $msg, $datos = null): array {
        return ['exito' => $ok, 'mensaje' => $msg, 'datos' => $datos];
    }

    // registra un nuevo equipo
    public function registrar($codigo, $nombre, $tipo, $estado = 'Disponible'): array {
        if (trim($codigo) === '' || trim($nombre) === '' || trim($tipo) === '') {
            return $this->r(false, 'Campos obligatorios vacios');
        }
        if (!in_array($estado, ['Disponible', 'Prestado', 'Mantenimiento'], true)) {
            return $this->r(false, 'Estado invalido');
        }
        if ($this->modelo->porCodigo($codigo)) {
            return $this->r(false, 'El codigo ya existe');
        }

        $id = $this->modelo->crear($codigo, $nombre, $tipo, $estado);
        return $this->r(true, 'Equipo registrado', [
            'id' => $id, 'codigo' => $codigo, 'nombre' => $nombre, 'tipo' => $tipo, 'estado' => $estado
        ]);
    }

    // Busca y devuelve un equipo por su id.
    public function consultar($id): array {
        $x = $this->modelo->porId((int)$id);
        return $x ? $this->r(true, 'Equipo encontrado', $x) : $this->r(false, 'Equipo no encontrado');
    }

    // Devuelve la lista completa de equipos.
    public function listar(): array {
        return $this->r(true, 'Consulta realizada', $this->modelo->listar());
    }

    // Actualiza los datos de un equipo
    public function actualizar($id, $nombre, $tipo, $estado): array {
        if (trim($nombre) === '' || trim($tipo) === '') {
            return $this->r(false, 'Campos obligatorios vacios');
        }
        if (!in_array($estado, ['Disponible', 'Prestado', 'Mantenimiento'], true)) {
            return $this->r(false, 'Estado invalido');
        }
        if (!$this->modelo->porId((int)$id)) {
            return $this->r(false, 'Equipo no encontrado');
        }

        $this->modelo->actualizar((int)$id, $nombre, $tipo, $estado);
        return $this->r(true, 'Equipo actualizado');
    }
}
