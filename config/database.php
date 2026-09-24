<?php
<<<<<<< HEAD
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
=======
namespace Config;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $host = '127.0.0.1';
            $db   = 'sistema_prestamos';
            $user = 'root';
            $pass = ''; // Tu clave de MySQL en Laragon
            $charset = 'utf8mb4';
            $port = 3307; // Puerto predeterminado de MySQL en Laragon

            $dsn = "mysql:host={$host};dbname={$db};charset={$charset};port={$port}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                throw new PDOException("Error en la conexión: " . $e->getMessage());
            }
        }
        return self::$instance;
    }
}
>>>>>>> 4aeddd56c171b86cb07c64082ff6ee02708d0c56
