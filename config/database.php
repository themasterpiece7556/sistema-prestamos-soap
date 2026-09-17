<?php
function obtenerConexion(): \PDO {
    $host = '127.0.0.1'; $port = 3307; $db = 'prestamo_equipos'; $user = 'root'; $pass = '';
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    return new \PDO($dsn, $user, $pass, [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_EMULATE_PREPARES => false,
        \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
    ]);
}
