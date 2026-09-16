<?php

/**
 * Cliente de ejemplo que consume el servicio SOAP usando la extensión
 * nativa de PHP (ext-soap). Sirve para probar manualmente las 6
 * operaciones sin necesidad de otra aplicación.
 *
 * Requiere que public/server.php esté corriendo (ej. con el servidor
 * embebido de PHP: `php -S localhost:8000 -t public`) y que la extensión
 * "soap" esté habilitada en php.ini.
 *
 * Uso: php examples/cliente_prueba.php
 */

$wsdl = 'http://localhost:8000/server.php?wsdl';

$opciones = [
    'trace'      => 1,
    'exceptions' => true,
    'cache_wsdl' => WSDL_CACHE_NONE,
];

try {
    $cliente = new SoapClient($wsdl, $opciones);

    echo "== 1. registrarEquipo ==" . PHP_EOL;
    $nuevoEquipo = $cliente->registrarEquipo([
        'codigo' => 'PC-999',
        'nombre' => 'Portátil de prueba',
        'tipo'   => 'Computador portátil',
        'estado' => 'Disponible',
    ]);
    print_r($nuevoEquipo);

    $idEquipo = $nuevoEquipo->return->id;

    echo "== 2. consultarEquipo ==" . PHP_EOL;
    $equipo = $cliente->consultarEquipo(['id' => $idEquipo]);
    print_r($equipo);

    echo "== 3. listarEquipos ==" . PHP_EOL;
    $todos = $cliente->listarEquipos();
    print_r($todos);

    echo "== 4. actualizarEquipo ==" . PHP_EOL;
    $actualizado = $cliente->actualizarEquipo([
        'id'     => $idEquipo,
        'codigo' => '',
        'nombre' => '',
        'tipo'   => '',
        'estado' => 'Mantenimiento',
    ]);
    print_r($actualizado);

    // Lo dejamos Disponible de nuevo para poder prestarlo.
    $cliente->actualizarEquipo([
        'id' => $idEquipo, 'codigo' => '', 'nombre' => '', 'tipo' => '', 'estado' => 'Disponible',
    ]);

    echo "== 5. registrarPrestamo ==" . PHP_EOL;
    $prestamo = $cliente->registrarPrestamo([
        'equipoId'          => $idEquipo,
        'documento'         => '1099999999',
        'nombreSolicitante' => 'Usuario de Prueba',
        'correoSolicitante' => 'prueba@correo.edu.co',
    ]);
    print_r($prestamo);

    echo "== Intento de re-préstamo (debe fallar: equipo ya Prestado) ==" . PHP_EOL;
    try {
        $cliente->registrarPrestamo([
            'equipoId' => $idEquipo, 'documento' => '1099999998',
            'nombreSolicitante' => 'Otro Usuario', 'correoSolicitante' => 'otro@correo.edu.co',
        ]);
    } catch (SoapFault $fault) {
        echo "Fault esperado: " . $fault->getMessage() . PHP_EOL;
    }

    echo "== 6. consultarPrestamos (Activos) ==" . PHP_EOL;
    $activos = $cliente->consultarPrestamos(['estado' => 'Activo']);
    print_r($activos);

    echo "== Extra: registrarDevolucion ==" . PHP_EOL;
    $idPrestamo = $prestamo->return->id;
    $devuelto = $cliente->registrarDevolucion(['prestamoId' => $idPrestamo]);
    print_r($devuelto);
} catch (SoapFault $fault) {
    echo 'Error SOAP: ' . $fault->getMessage() . PHP_EOL;
    if (isset($cliente)) {
        echo "Request:\n" . $cliente->__getLastRequest() . PHP_EOL;
    }
}
