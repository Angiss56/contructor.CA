<?php
class Conexion {
    private static $instancia = null;

    private function __construct() {
        // Constructor privado para evitar instanciación directa
    }

    public static function conectar() {
        if (self::$instancia === null) {
            try {
                self::$instancia = new PDO(
                    'mysql:host=localhost;dbname=civilnuevo;charset=utf8',
                    'root',
                    '',
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch (PDOException $e) {
                die("Error de conexión: " . $e->getMessage());
            }
        }
        return self::$instancia;
    }

    public static function getInstance()
    {
    }
}

// Establecer conexión para uso global
$pdo = Conexion::conectar();
?>
