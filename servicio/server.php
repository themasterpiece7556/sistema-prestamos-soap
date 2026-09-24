<?php

// Endpoint http://localhost/Prestamo/servicio/server.php
//WSDL Http://localhost/Prestamo/servicio/server.php?wsdl

// Se cargan las dependencias de Composer
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../models/Equipo.php';
require_once __DIR__.'/../models/Solicitante.php';
require_once __DIR__.'/../models/Prestamo.php';
require_once __DIR__.'/../services/EquipoService.php';
require_once __DIR__.'/../services/PrestamoService.php';

// Se crea el servidor SOAP y se configura el WSDL
$server = new soap_server();
$server->configureWSDL('PrestamoEquipos', 'urn:PrestamoEquipos');

// Se define un tipo de dato complejo "Respuesta" para el WSDL
$server->wsdl->addComplexType(
    'Respuesta',
    'complexType',
    'struct',
    'all',
    '',
    [
        'exito'   => ['name' => 'exito', 'type' => 'xsd:boolean'],
        'mensaje' => ['name' => 'mensaje', 'type' => 'xsd:string'],
        'datos'   => ['name' => 'datos', 'type' => 'xsd:string'],
    ]
);

// Aquí se listan las 6 operaciones SOAP del sistema, junto con los parámetros
// que recibe cada una (nombre del parámetro => tipo de dato SOAP/xsd).
$ops = [
    'registrarEquipo'    => ['codigo' => 'xsd:string', 'nombre' => 'xsd:string', 'tipo' => 'xsd:string', 'estado' => 'xsd:string'],
    'consultarEquipo'    => ['id' => 'xsd:int'],
    'listarEquipos'      => [],
    'actualizarEquipo'   => ['id' => 'xsd:int', 'nombre' => 'xsd:string', 'tipo' => 'xsd:string', 'estado' => 'xsd:string'],
    'registrarPrestamo'  => ['equipoId' => 'xsd:int', 'solicitanteId' => 'xsd:int', 'fechaPrestamo' => 'xsd:string', 'fechaEntrega' => 'xsd:string'],
    'consultarPrestamos' => [],
];

// Se registra cada operación en el servidor SOAP: todas devuelven un string (xsd:string)
// que en realidad es un XML sencillo armado por la función xml() de más abajo.
foreach ($ops as $name => $params) {
    $server->register(
        $name,
        $params,
        ['return' => 'xsd:string'],
        'urn:PrestamoEquipos',
        'urn:PrestamoEquipos#'.$name,
        'rpc',
        'encoded',
        $name
    );
}

// Se crea la conexión a la BD y se instancian los servicios que contienen
$db = obtenerConexion();
$es = new EquipoService($db, new Equipo($db));
$ps = new PrestamoService($db, new Equipo($db), new Solicitante($db), new Prestamo($db));

// Función de ayuda: convierte el arreglo de respuesta (['exito'=>.., 'mensaje'=>.., 'datos'=>..])
// en un XML simple para devolverlo al cliente SOAP.
function xml($x) {
    return '<respuesta>'
        .'<exito>'.($x['exito'] ? 'true' : 'false').'</exito>'
        .'<mensaje>'.htmlspecialchars($x['mensaje']).'</mensaje>'
        .'<datos>'.htmlspecialchars(json_encode($x['datos'], JSON_UNESCAPED_UNICODE)).'</datos>'
        .'</respuesta>';
}

// A continuación, una función global por cada operación SOAP registrada arriba.
// NuSOAP llama a estas funciones (por su nombre) cuando el cliente invoca la operación.

// Registra un nuevo equipo.
function registrarEquipo($codigo, $nombre, $tipo, $estado) {
    global $es;
    return xml($es->registrar($codigo, $nombre, $tipo, $estado));
}

// Consulta un equipo por su id.
function consultarEquipo($id) {
    global $es;
    return xml($es->consultar($id));
}

// Lista todos los equipos registrados.
function listarEquipos() {
    global $es;
    return xml($es->listar());
}

// Actualiza los datos de un equipo existente.
function actualizarEquipo($id, $nombre, $tipo, $estado) {
    global $es;
    return xml($es->actualizar($id, $nombre, $tipo, $estado));
}

// Registra un nuevo préstamo
function registrarPrestamo($equipoId, $solicitanteId, $fechaPrestamo, $fechaEntrega) {
    global $ps;
    return xml($ps->registrar($equipoId, $solicitanteId, $fechaPrestamo, $fechaEntrega));
}

// Lista todos los préstamos registrados.
function consultarPrestamos() {
    global $ps;
    return xml($ps->listar());
}

// Se lee el cuerpo de la petición SOAP que llega por POST y se le entrega al servidor
// para que decida qué operación ejecutar y devuelva la respuesta correspondiente.
$HTTP_RAW_POST_DATA = file_get_contents('php://input');
$server->service($HTTP_RAW_POST_DATA);
