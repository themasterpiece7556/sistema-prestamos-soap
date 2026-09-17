<?php
// Clase PrestamoService: contiene la lógica de negocio para registrar y consultar préstamos.
class PrestamoService {

    public function __construct(
        private PDO $db,
        private Equipo $equipos,
        private Solicitante $solicitantes,
        private Prestamo $prestamos
    ) {}

    // Función de ayuda para armar la respuesta siempre con el mismo formato.
    private function r(bool $ok, string $msg, $datos = null): array {
        return ['exito' => $ok, 'mensaje' => $msg, 'datos' => $datos];
    }

    // Registra un préstamo
    public function registrar($equipoId, $solicitanteId, $fechaPrestamo, $fechaEntrega): array {
        if (!$equipoId || !$solicitanteId || trim($fechaPrestamo) === '' || trim($fechaEntrega) === '') {
            return $this->r(false, 'Campos obligatorios vacios');
        }

        if (strtotime($fechaEntrega) === false || strtotime($fechaPrestamo) === false
            || strtotime($fechaEntrega) <= strtotime($fechaPrestamo)) {
            return $this->r(false, 'Fechas invalidas');
        }

        $e = $this->equipos->porId((int)$equipoId);
        if (!$e) {
            return $this->r(false, 'Equipo no encontrado');
        }

        if ($e['estado'] !== 'Disponible') {
            return $this->r(false, 'El equipo no esta disponible');
        }

        if (!$this->solicitantes->porId((int)$solicitanteId)) {
            return $this->r(false, 'Solicitante no registrado');
        }

        try {
            // Se inicia una transacción para que "crear préstamo" y "cambiar estado del equipo"
            // ocurran juntos: o se hacen las dos cosas, o no se hace ninguna.
            $this->db->beginTransaction();
            $id = $this->prestamos->crear((int)$equipoId, (int)$solicitanteId, $fechaPrestamo, $fechaEntrega);
            $this->equipos->cambiarEstado((int)$equipoId, 'Prestado');
            $this->db->commit();
            return $this->r(true, 'Prestamo registrado', ['id' => $id, 'estado' => 'Activo']);
        } catch (Throwable $x) {
            // Si algo falla en medio de la transacción, se revierte todo.
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return $this->r(false, 'No se pudo registrar el prestamo');
        }
    }

    // Devuelve el listado completo de préstamos
    public function listar(): array {
        return $this->r(true, 'Consulta realizada', $this->prestamos->listar());
    }
}
