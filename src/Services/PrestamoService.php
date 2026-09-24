<?php

namespace App\Services;

use App\Models\Equipo;
use App\Models\Prestamo;
use App\Models\Usuario;
use PDO;

/**
 * Capa de lógica de negocio expuesta como servicio SOAP.
 * public/server.php registra cada método público de esta clase como una
 * operación SOAP. Ningún cliente accede a la base de datos directamente:
 * todo pasa por aquí.
 *
 * Todos los métodos devuelven arrays asociativos (o arrays de arrays)
 * listos para ser serializados por NuSOAP, o lanzan \Exception con un
 * mensaje legible que el servidor convierte en un SOAP Fault.
 */
class PrestamoService
{
    private PDO $db;
    private Equipo $equipos;
    private Usuario $usuarios;
    private Prestamo $prestamos;

    public function __construct(PDO $db)
    {
        $this->db        = $db;
        $this->equipos    = new Equipo($db);
        $this->usuarios   = new Usuario($db);
        $this->prestamos  = new Prestamo($db);
    }

    // -----------------------------------------------------------------
    // 1. registrarEquipo
    // -----------------------------------------------------------------
    /**
     * Registra un nuevo equipo en el inventario.
     *
     * @param string $codigo Código único de inventario (ej. PC-006)
     * @param string $nombre Nombre descriptivo del equipo
     * @param string $tipo   Tipo de equipo (Computador portátil, Proyector, Tablet, Cámara, ...)
     * @param string $estado Estado inicial (Disponible | Prestado | Mantenimiento). Por defecto "Disponible".
     * @return array Equipo recién creado.
     */
    public function registrarEquipo(string $codigo, string $nombre, string $tipo, string $estado = 'Disponible'): array
    {
        $id = $this->equipos->crear([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'tipo'   => $tipo,
            'estado' => $estado,
        ]);

        return $this->equipos->obtenerPorId($id);
    }

    // -----------------------------------------------------------------
    // 2. consultarEquipo
    // -----------------------------------------------------------------
    /**
     * Consulta un equipo por id. Si no se encuentra, lanza una excepción.
     *
     * @param int $id
     * @return array Datos del equipo.
     */
    public function consultarEquipo(int $id): array
    {
        $equipo = $this->equipos->obtenerPorId($id);

        if ($equipo === null) {
            throw new \Exception("No existe un equipo con id {$id}.");
        }

        return $equipo;
    }

    // -----------------------------------------------------------------
    // 3. listarEquipos
    // -----------------------------------------------------------------
    /**
     * Lista todos los equipos registrados, sin importar su estado.
     *
     * @return array Lista de equipos.
     */
    public function listarEquipos(): array
    {
        return $this->equipos->listarTodos();
    }

    // -----------------------------------------------------------------
    // 4. actualizarEquipo
    // -----------------------------------------------------------------
    /**
     * Actualiza los datos (y/o el estado) de un equipo existente.
     * Cualquier parámetro dejado como cadena vacía conserva su valor actual.
     *
     * @param int    $id
     * @param string $codigo
     * @param string $nombre
     * @param string $tipo
     * @param string $estado Disponible | Prestado | Mantenimiento
     * @return array Equipo actualizado.
     */
    public function actualizarEquipo(int $id, string $codigo = '', string $nombre = '', string $tipo = '', string $estado = ''): array
    {
        $datos = array_filter([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'tipo'   => $tipo,
            'estado' => $estado,
        ], static fn ($valor) => $valor !== '');

        $this->equipos->actualizar($id, $datos);

        return $this->equipos->obtenerPorId($id);
    }

    // -----------------------------------------------------------------
    // 5. registrarPrestamo
    // -----------------------------------------------------------------
    /**
     * Registra el préstamo de un equipo a un solicitante.
     * - Solo se pueden prestar equipos en estado "Disponible".
     * - El solicitante debe quedar registrado en la tabla usuarios
     *   (si ya existe por documento, se reutiliza; si no, se crea).
     * - Al confirmar el préstamo, el equipo pasa a estado "Prestado".
     * La operación es transaccional: si algo falla, no se deja el
     * sistema en un estado inconsistente (equipo prestado sin préstamo, etc).
     *
     * @param int    $equipoId          Id del equipo a prestar.
     * @param string $documento         Documento del solicitante.
     * @param string $nombreSolicitante Nombre del solicitante (usado solo si hay que registrarlo).
     * @param string $correoSolicitante Correo del solicitante (usado solo si hay que registrarlo).
     * @return array Préstamo creado, con los datos de equipo y solicitante.
     */
    public function registrarPrestamo(int $equipoId, string $documento, string $nombreSolicitante = '', string $correoSolicitante = ''): array
    {
        $this->db->beginTransaction();

        try {
            $equipo = $this->equipos->obtenerPorId($equipoId);
            if ($equipo === null) {
                throw new \Exception("No existe un equipo con id {$equipoId}.");
            }

            if ($equipo['estado'] !== 'Disponible') {
                throw new \Exception(
                    "El equipo '{$equipo['codigo']}' no está disponible (estado actual: {$equipo['estado']})."
                );
            }

            $solicitante = $this->usuarios->obtenerOcrear([
                'documento' => $documento,
                'nombre'    => $nombreSolicitante,
                'correo'    => $correoSolicitante,
            ]);

            $prestamoId = $this->prestamos->crear($equipoId, (int) $solicitante['id']);
            $this->equipos->actualizarEstado($equipoId, 'Prestado');

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw new \Exception($e->getMessage());
        }

        return $this->prestamos->obtenerPorId($prestamoId);
    }

    // -----------------------------------------------------------------
    // 6. consultarPrestamos
    // -----------------------------------------------------------------
    /**
     * Consulta préstamos, con filtro opcional por estado.
     *
     * @param string $estado Activo | Devuelto | Cancelado | "" (todos)
     * @return array Lista de préstamos, con los datos del equipo y del solicitante.
     */
    public function consultarPrestamos(string $estado = ''): array
    {
        return $this->prestamos->listar($estado !== '' ? $estado : null);
    }

    // -----------------------------------------------------------------
    // Extra (no exigida por el enunciado, útil para cerrar el ciclo de vida
    // del préstamo desde el propio servicio SOAP): registra la devolución
    // de un equipo, liberándolo para volver a estar Disponible.
    // -----------------------------------------------------------------
    public function registrarDevolucion(int $prestamoId): array
    {
        $this->db->beginTransaction();

        try {
            $prestamo = $this->prestamos->obtenerPorId($prestamoId);
            if ($prestamo === null) {
                throw new \Exception("No existe un préstamo con id {$prestamoId}.");
            }

            if ($prestamo['estado'] !== 'Activo') {
                throw new \Exception("El préstamo {$prestamoId} no está activo (estado actual: {$prestamo['estado']}).");
            }

            $this->prestamos->marcarDevuelto($prestamoId);
            $this->equipos->actualizarEstado((int) $prestamo['equipo_id'], 'Disponible');

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw new \Exception($e->getMessage());
        }

        return $this->prestamos->obtenerPorId($prestamoId);
    }
}
