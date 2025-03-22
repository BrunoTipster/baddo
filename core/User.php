<?php
/**
 * Modelo de usuário
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 * @last_modified 2025-03-22 15:39:45
 */

class User {
    private $db;
    private $data;

    /**
     * Construtor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Busca usuário por ID
     */
    public function find($id) {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                SELECT * FROM users 
                WHERE id = ? 
                LIMIT 1
            ");
            
            $stmt->bind_param('i', $id);
            $stmt->execute();
            
            $this->data = $stmt->get_result()->fetch_assoc();
            return $this;

        } catch (Exception $e) {
            error_log("User Find Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Busca usuário por username
     */
    public function findByUsername($username) {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                SELECT * FROM users 
                WHERE username = ? 
                LIMIT 1
            ");
            
            $stmt->bind_param('s', $username);
            $stmt->execute();
            
            $this->data = $stmt->get_result()->fetch_assoc();
            return $this;

        } catch (Exception $e) {
            error_log("User Find Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Cria novo usuário
     */
    public function create($data) {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                INSERT INTO users 
                (username, email, password, name, gender, birth_date)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param('ssssss',
                $data['username'],
                $data['email'],
                password_hash($data['password'], PASSWORD_DEFAULT),
                $data['name'],
                $data['gender'],
                $data['birth_date']
            );
            
            if ($stmt->execute()) {
                return $this->find($stmt->insert_id);
            }
            
            return false;

        } catch (Exception $e) {
            error_log("User Create Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Atualiza dados do usuário
     */
    public function update($data) {
        try {
            if (!$this->data) {
                throw new Exception("No user loaded");
            }

            $conn = $this->db->getConnection();
            
            $sql = "UPDATE users SET ";
            $params = [];
            $types = "";

            foreach ($data as $key => $value) {
                if ($key != 'id' && $key != 'password') {
                    $sql .= "$key = ?, ";
                    $params[] = $value;
                    $types .= "s";
                }
            }

            if (isset($data['password'])) {
                $sql .= "password = ?, ";
                $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
                $types .= "s";
            }

            $sql = rtrim($sql, ", ");
            $sql .= " WHERE id = ?";
            $params[] = $this->data['id'];
            $types .= "i";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                return $this->find($this->data['id']);
            }
            
            return false;

        } catch (Exception $e) {
            error_log("User Update Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retorna dados do usuário
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Verifica se é o usuário atual
     */
    public function isCurrentUser() {
        return $this->data && 
               isset($_SESSION['user_id']) && 
               $this->data['id'] == $_SESSION['user_id'];
    }

    /**
     * Retorna fotos do usuário
     */
    public function getPhotos() {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                SELECT * FROM user_photos 
                WHERE user_id = ? 
                ORDER BY is_primary DESC, order_position ASC
            ");
            
            $stmt->bind_param('i', $this->data['id']);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        } catch (Exception $e) {
            error_log("User Photos Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retorna preferências do usuário
     */
    public function getPreferences() {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                SELECT * FROM user_preferences 
                WHERE user_id = ? 
                LIMIT 1
            ");
            
            $stmt->bind_param('i', $this->data['id']);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_assoc();

        } catch (Exception $e) {
            error_log("User Preferences Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Atualiza preferências do usuário
     */
    public function updatePreferences($data) {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                UPDATE user_preferences 
                SET interested_in = ?,
                    min_age = ?,
                    max_age = ?,
                    max_distance = ?,
                    show_online = ?,
                    show_distance = ?
                WHERE user_id = ?
            ");
            
            $stmt->bind_param('siiiiis',
                $data['interested_in'],
                $data['min_age'],
                $data['max_age'],
                $data['max_distance'],
                $data['show_online'],
                $data['show_distance'],
                $this->data['id']
            );
            
            return $stmt->execute();

        } catch (Exception $e) {
            error_log("User Preferences Update Error: " . $e->getMessage());
            return false;
        }
    }
}