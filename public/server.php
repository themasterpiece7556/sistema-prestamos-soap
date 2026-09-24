<?php

/**
 * Endpoint SOAP del sistema de préstamo de equipos tecnológicos.
 *
 * Expone 6 operaciones (registrarEquipo, consultarEquipo, listarEquipos,
 * actualizarEquipo, registrarPrestamo, consultarPrestamos) más una
 * operación adicional (registrarDevolucion) usando NuSOAP.
 *
 * Toda la lógica de negocio vive en App\Services\PrestamoService; este
 * archivo solo se encarga de: cargar dependencias, definir el contrato
 * WSDL (tipos + operaciones) y despachar cada llamada SOAP a un método
 * del servicio. El cliente NUNCA toca la base de datos directamente.
 *
 * WSDL:      http://<host>/server.php?wsdl
 * Endpoint:  http://<host>/server.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\PrestamoService;
use Config\Database;

// Ver todos los errores como excepciones capturables, pero sin imprimir
// warnings/notices sueltos en medio de la respuesta SOAP (rompería el XML).
error_reporting(E_ALL);
ini_set('display_errors', '0');

$namespace = 'urn:sistemaPrestamosSoap';
$endpoint  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://')
    . ($_SERVER['HTTP_HOST'] ?? 'localhost')
    . ($_SERVER['PHP_SELF'] ?? '/server.php');

$server = new \nusoap_server();
$server->configureWSDL('SistemaPrestamosSoap', $namespace, $endpoint, 'rpc', 'http://schemas.xmlsoap.org/soap/http');
$server->wsdl->schemaTargetNamespace = $namespace;

// ---------------------------------------------------------------------
// Tipos complejos del WSDL
// ---------------------------------------------------------------------

$server->wsdl->addComplexType(
    'Equipo',
    'complexType',
    'struct',
    'all',
    '',
    [
        'id'     => ['name' => 'id', 'type' => 'xsd:int'],
        'codigo' => ['name' => 'codigo', 'type' => 'xsd:string'],
        'nombre' => ['name' => 'nombre', 'type' => 'xsd:string'],
        'tipo'   => ['name' => 'tipo', 'type' => 'xsd:string'],
        'estado' => ['name' => 'estado', 'type' => 'xsd:string'],
    ]
);

$server->wsdl->addComplexType(
    'EquipoArray',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    [],
    [
        [
            'ref'          => 'SOAP-ENC:arrayType',
            'wsdl:arrayType' => 'tns:Equipo[]',
        ],
    ],
    'tns:Equipo'
);

$server->wsdl->addComplexType(
    'Prestamo',
    'complexType',
    'struct',
    'all',
    '',
    [
        'id'                    => ['name' => 'id', 'type' => 'xsd:int'],
        'equipo_id'             => ['name' => 'equipo_id', 'type' => 'xsd:int'],
        'equipo_codigo'         => ['name' => 'equipo_codigo', 'type' => 'xsd:string'],
        'equipo_nombre'         => ['name' => 'equipo_nombre', 'type' => 'xsd:string'],
        'equipo_tipo'           => ['name' => 'equipo_tipo', 'type' => 'xsd:string'],
        'solicitante_id'        => ['name' => 'solicitante_id', 'type' => 'xsd:int'],
        'solicitante_documento' => ['name' => 'solicitante_documento', 'type' => 'xsd:string'],
        'solicitante_nombre'    => ['name' => 'solicitante_nombre', 'type' => 'xsd:string'],
        'solicitante_correo'    => ['name' => 'solicitante_correo', 'type' => 'xsd:string'],
        'fecha_prestamo'        => ['name' => 'fecha_prestamo', 'type' => 'xsd:string'],
        'fecha_entrega'         => ['name' => 'fecha_entrega', 'type' => 'xsd:string'],
        'estado'                => ['name' => 'estado', 'type' => 'xsd:string'],
    ]
);

$server->wsdl->addComplexType(
    'PrestamoArray',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    [],
    [
        [
            'ref'          => 'SOAP-ENC:arrayType',
            'wsdl:arrayType' => 'tns:Prestamo[]',
        ],
    ],
    'tns:Prestamo'
);

// ---------------------------------------------------------------------
// Registro de operaciones
// ---------------------------------------------------------------------

// 1. registrarEquipo
$server->register(
    'registrarEquipo',
    ['codigo' => 'xsd:string', 'nombre' => 'xsd:string', 'tipo' => 'xsd:string', 'estado' => 'xsd:string'],
    ['return' => 'tns:Equipo'],
    $namespace,
    "$namespace#registrarEquipo",
    'rpc',
    'encoded',
    'Registra un nuevo equipo. El código debe ser único.'
);

// 2. consultarEquipo
$server->register(
    'consultarEquipo',
    ['id' => 'xsd:int'],
    ['return' => 'tns:Equipo'],
    $namespace,
    "$namespace#consultarEquipo",
    'rpc',
    'encoded',
    'Consulta un equipo por su id.'
);

// 3. listarEquipos
$server->register(
    'listarEquipos',
    [],
    ['return' => 'tns:EquipoArray'],
    $namespace,
    "$namespace#listarEquipos",
    'rpc',
    'encoded',
    'Lista todos los equipos registrados.'
);

// 4. actualizarEquipo
$server->register(
    'actualizarEquipo',
    ['id' => 'xsd:int', 'codigo' => 'xsd:string', 'nombre' => 'xsd:string', 'tipo' => 'xsd:string', 'estado' => 'xsd:string'],
    ['return' => 'tns:Equipo'],
    $namespace,
    "$namespace#actualizarEquipo",
    'rpc',
    'encoded',
    'Actualiza los datos y/o el estado de un equipo existente.'
);

// 5. registrarPrestamo
$server->register(
    'registrarPrestamo',
    ['equipoId' => 'xsd:int', 'documento' => 'xsd:string', 'nombreSolicitante' => 'xsd:string', 'correoSolicitante' => 'xsd:string'],
    ['return' => 'tns:Prestamo'],
    $namespace,
    "$namespace#registrarPrestamo",
    'rpc',
    'encoded',
    'Registra el préstamo de un equipo disponible a un solicitante. El equipo queda en estado Prestado.'
);

// 6. consultarPrestamos
$server->register(
    'consultarPrestamos',
    ['estado' => 'xsd:string'],
    ['return' => 'tns:PrestamoArray'],
    $namespace,
    "$namespace#consultarPrestamos",
    'rpc',
    'encoded',
    'Lista préstamos, opcionalmente filtrados por estado (Activo, Devuelto, Cancelado).'
);

// Extra: registrarDevolucion
$server->register(
    'registrarDevolucion',
    ['prestamoId' => 'xsd:int'],
    ['return' => 'tns:Prestamo'],
    $namespace,
    "$namespace#registrarDevolucion",
    'rpc',
    'encoded',
    'Registra la devolución de un préstamo activo; el equipo vuelve a estar Disponible.'
);

// ---------------------------------------------------------------------
// Funciones "wrapper" que NuSOAP invoca por nombre.
// Cada una delega en PrestamoService y traduce cualquier excepción en un
// SOAP Fault legible para el cliente, sin exponer detalles internos.
// ---------------------------------------------------------------------

function getService(): PrestamoService
{
    static $service = null;
    if ($service === null) {
        $service = new PrestamoService(Database::getConnection());
    }
    return $service;
}

function registrarEquipo($codigo, $nombre, $tipo, $estado)
{
    try {
        return getService()->registrarEquipo($codigo, $nombre, $tipo, $estado !== '' ? $estado : 'Disponible');
    } catch (\Throwable $e) {
        return new \nusoap_fault('Server', '', $e->getMessage());
    }
}

function consultarEquipo($id)
{
    try {
        return getService()->consultarEquipo((int) $id);
    } catch (\Throwable $e) {
        return new \nusoap_fault('Server', '', $e->getMessage());
    }
}

function listarEquipos()
{
    try {
        return getService()->listarEquipos();
    } catch (\Throwable $e) {
        return new \nusoap_fault('Server', '', $e->getMessage());
    }
}

function actualizarEquipo($id, $codigo, $nombre, $tipo, $estado)
{
    try {
        return getService()->actualizarEquipo((int) $id, $codigo, $nombre, $tipo, $estado);
    } catch (\Throwable $e) {
        return new \nusoap_fault('Server', '', $e->getMessage());
    }
}

function registrarPrestamo($equipoId, $documento, $nombreSolicitante, $correoSolicitante)
{
    try {
        return getService()->registrarPrestamo((int) $equipoId, $documento, $nombreSolicitante, $correoSolicitante);
    } catch (\Throwable $e) {
        return new \nusoap_fault('Server', '', $e->getMessage());
    }
}

function consultarPrestamos($estado)
{
    try {
        return getService()->consultarPrestamos($estado);
    } catch (\Throwable $e) {
        return new \nusoap_fault('Server', '', $e->getMessage());
    }
}

function registrarDevolucion($prestamoId)
{
    try {
        return getService()->registrarDevolucion((int) $prestamoId);
    } catch (\Throwable $e) {
        return new \nusoap_fault('Server', '', $e->getMessage());
    }
}

// ---------------------------------------------------------------------
// Despacho de la petición SOAP entrante
// ---------------------------------------------------------------------
$requestXml = file_get_contents('php://input');
$server->service($requestXml);
