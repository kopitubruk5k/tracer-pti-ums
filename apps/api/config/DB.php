<?php
// Database PDO Connection Manager

class DB {
    private static $instance = null;

    public static function getConnection() {
        if (self::$instance === null) {
            $config = require __DIR__ . '/config.php';
            $db = $config['db'];

            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
                $db['host'],
                $db['port'],
                $db['name']
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, $db['user'], $db['pass'], $options);
            } catch (PDOException $e) {
                // Return structured API response instead of raw HTML on db failure
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Database connection failed: ' . $e->getMessage()
                ]);
                exit();
            }
        }
        return self::$instance;
    }

    // Helper for transactions
    public static function transaction(callable $callback) {
        $pdo = self::getConnection();
        try {
            $pdo->beginTransaction();
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
