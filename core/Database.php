<?php
/**
 * Classe de conexão com banco de dados
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 * @last_modified 2025-03-22 15:39:45
 */

class Database {
    private static $instance = null;
    private $connection;
    private $transactions = 0;

    /**
     * Construtor - estabelece conexão com banco
     */
    public function __construct() {
        try {
            $this->connection = new mysqli(
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME
            );

            if ($this->connection->connect_error) {
                throw new Exception("Conexão falhou: " . $this->connection->connect_error);
            }

            $this->connection->set_charset("utf8mb4");

        } catch (Exception $e) {
            error_log("Database Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Implementação do Singleton
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retorna conexão ativa
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Inicia uma transação
     */
    public function beginTransaction() {
        if ($this->transactions == 0) {
            $this->connection->begin_transaction();
        }
        $this->transactions++;
    }

    /**
     * Confirma uma transação
     */
    public function commit() {
        if ($this->transactions > 0) {
            $this->transactions--;
            if ($this->transactions == 0) {
                $this->connection->commit();
            }
        }
    }

    /**
     * Reverte uma transação
     */
    public function rollback() {
        if ($this->transactions > 0) {
            $this->transactions = 0;
            $this->connection->rollback();
        }
    }

    /**
     * Executa uma query com prepared statement
     */
    public function executeQuery($sql, $params = [], $types = '') {
        try {
            $stmt = $this->connection->prepare($sql);
            
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $this->connection->error);
            }

            if (!empty($params)) {
                if (empty($types)) {
                    $types = str_repeat('s', count($params));
                }
                $stmt->bind_param($types, ...$params);
            }

            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            return $stmt;

        } catch (Exception $e) {
            error_log("Query Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Escapa string para prevenir SQL Injection
     */
    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }

    /**
     * Retorna último ID inserido
     */
    public function getLastInsertId() {
        return $this->connection->insert_id;
    }

    /**
     * Retorna número de linhas afetadas
     */
    public function getAffectedRows() {
        return $this->connection->affected_rows;
    }

    /**
     * Fecha a conexão
     */
    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }

    /**
     * Destrutor - fecha conexão automaticamente
     */
    public function __destruct() {
        $this->close();
    }
}