<?php
session_start();

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'afie_pa');

// User Class
class User {
    private $conn;
    private $table = 'users';
    
    public $id;
    public $username;
    public $email;
    public $password;
    public $balance;
    public $created_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create user account
    public function register() {
        $query = "INSERT INTO " . $this->table . " (username, email, password, balance) 
                  VALUES (:username, :email, :password, :balance)";
        
        $stmt = $this->conn->prepare($query);
        
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        $this->balance = 0;
        
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':balance', $this->balance);
        
        return $stmt->execute();
    }
    
    // Login user
    public function login() {
        $query = "SELECT id, username, email, password, balance FROM " . $this->table . " 
                  WHERE email = :email LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($this->password, $row['password'])) {
                $this->id = $row['id'];
                $this->username = $row['username'];
                $this->balance = $row['balance'];
                return true;
            }
        }
        return false;
    }
    
    // Get user by ID
    public function getUserById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Update user balance
  