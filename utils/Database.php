<?php
/**
 * Classe de conexão com o banco de dados
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 */

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );

            $this->connection = new PDO(
                $dsn,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("Erro de conexão: " . $e->getMessage());
            } else {
                die("Erro ao conectar ao banco de dados");
            }
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                throw new Exception("Erro na query: " . $e->getMessage());
            } else {
                throw new Exception("Erro ao executar operação no banco de dados");
            }
        }
    }

    public function single($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    public function all($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }

    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    public function commit() {
        return $this->connection->commit();
    }

    public function rollBack() {
        return $this->connection->rollBack();
    }

    public function quote($value) {
        return $this->connection->quote($value);
    }

    public function count($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn();
    }

    public function exists($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount() > 0;
    }
}