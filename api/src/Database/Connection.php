<?php
// ============================================================
//  src/Database/Connection.php
//  Singleton PDO connection
// ============================================================
namespace App\Database;

use PDO;
use PDOException;
use RuntimeException;

class Connection
{
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    /**
     * Returns the shared PDO instance.
     * Reads credentials from config/database.php.
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $cfg = require __DIR__ . '/../../config/database.php';

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $cfg['host'],
                $cfg['port'],
                $cfg['dbname'],
                $cfg['charset']
            );

            try {
                self::$instance = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                throw new RuntimeException(
                    'Database connection failed: ' . $e->getMessage(),
                    500,
                    $e
                );
            }
        }

        return self::$instance;
    }
}
