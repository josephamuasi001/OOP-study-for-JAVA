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
    public function updateBalance($userId, $amount) {
        $query = "UPDATE " . $this->table . " SET balance = balance + :amount WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':id', $userId);
        
        return $stmt->execute();
    }
}

// Product Class
class Product {
    private $conn;
    private $table = 'products';
    
    public $id;
    public $name;
    public $description;
    public $price;
    public $stock;
    public $category;
    public $created_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Get all products
    public function getAllProducts() {
        $query = "SELECT * FROM " . $this->table . " WHERE stock > 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get product by ID
    public function getProductById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get products by category
    public function getProductsByCategory($category) {
        $query = "SELECT * FROM " . $this->table . " WHERE category = :category AND stock > 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':category', $category);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Create product
    public function createProduct() {
        $query = "INSERT INTO " . $this->table . " (name, description, price, stock, category) 
                  VALUES (:name, :description, :price, :stock, :category)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':stock', $this->stock);
        $stmt->bindParam(':category', $this->category);
        
        return $stmt->execute();
    }
    
    // Update stock
    public function updateStock($id, $quantity) {
        $query = "UPDATE " . $this->table . " SET stock = stock - :quantity WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
}

// Cart Class
class Cart {
    private $conn;
    private $table = 'cart';
    
    public $id;
    public $user_id;
    public $product_id;
    public $quantity;
    public $added_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Add item to cart
    public function addToCart() {
        $query = "INSERT INTO " . $this->table . " (user_id, product_id, quantity) 
                  VALUES (:user_id, :product_id, :quantity)
                  ON DUPLICATE KEY UPDATE quantity = quantity + :quantity";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':product_id', $this->product_id);
        $stmt->bindParam(':quantity', $this->quantity);
        
        return $stmt->execute();
    }
    
    // Get cart items
    public function getCartItems($user_id) {
        $query = "SELECT c.id, c.user_id, c.product_id, c.quantity, p.name, p.price, 
                         (c.quantity * p.price) as subtotal 
                  FROM " . $this->table . " c
                  JOIN products p ON c.product_id = p.id
                  WHERE c.user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Remove item from cart
    public function removeFromCart($cart_id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $cart_id);
        
        return $stmt->execute();
    }
    
    // Clear cart
    public function clearCart($user_id) {
        $query = "DELETE FROM " . $this->table . " WHERE user_id = :user_id";
