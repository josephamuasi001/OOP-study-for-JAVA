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
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        
        return $stmt->execute();
    }
    
    // Get cart total
    public function getCartTotal($user_id) {
        $query = "SELECT SUM(c.quantity * p.price) as total 
                  FROM " . $this->table . " c
                  JOIN products p ON c.product_id = p.id
                  WHERE c.user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }
}

// Order Class
class Order {
    private $conn;
    private $table = 'orders';
    
    public $id;
    public $user_id;
    public $total_amount;
    public $status;
    public $payment_method;
    public $created_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create order
    public function createOrder() {
        $query = "INSERT INTO " . $this->table . " (user_id, total_amount, status, payment_method) 
                  VALUES (:user_id, :total_amount, :status, :payment_method)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':total_amount', $this->total_amount);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':payment_method', $this->payment_method);
        
        return $stmt->execute();
    }
    
    // Get order ID of last insert
    public function getLastInsertId() {
        return $this->conn->lastInsertId();
    }
    
    // Get user orders
    public function getUserOrders($user_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get order by ID
    public function getOrderById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Update order status
    public function updateOrderStatus($order_id, $status) {
        $query = "UPDATE " . $this->table . " SET status = :status WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $order_id);
        
        return $stmt->execute();
    }
}

// OrderItem Class
class OrderItem {
    private $conn;
    private $table = 'order_items';
    
    public $id;
    public $order_id;
    public $product_id;
    public $quantity;
    public $price;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Add item to order
    public function addOrderItem() {
        $query = "INSERT INTO " . $this->table . " (order_id, product_id, quantity, price) 
                  VALUES (:order_id, :product_id, :quantity, :price)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':order_id', $this->order_id);
        $stmt->bindParam(':product_id', $this->product_id);
        $stmt->bindParam(':quantity', $this->quantity);
        $stmt->bindParam(':price', $this->price);
        
        return $stmt->execute();
    }
    
    // Get order items
    public function getOrderItems($order_id) {
        $query = "SELECT oi.*, p.name, p.description FROM " . $this->table . " oi
                  JOIN products p ON oi.product_id = p.id
                  WHERE oi.order_id = :order_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Payment Class
class Payment {
    private $conn;
    private $table = 'payments';
    
    public $id;
    public $order_id;
    public $user_id;
    public $amount;
    public $payment_method;
    public $payment_status;
    public $transaction_id;
    public $created_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Process payment from account balance
    public function processPayment() {
        $query = "INSERT INTO " . $this->table . " (order_id, user_id, amount, payment_method, payment_status, transaction_id) 
                  VALUES (:order_id, :user_id, :amount, :payment_method, :payment_status, :transaction_id)";
        
        $stmt = $this->conn->prepare($query);
        
        $this->transaction_id = uniqid('TXN_');
        
        $stmt->bindParam(':order_id', $this->order_id);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':amount', $this->amount);
        $stmt->bindParam(':payment_method', $this->payment_method);
        $stmt->bindParam(':payment_status', $this->payment_status);
        $stmt->bindParam(':transaction_id', $this->transaction_id);
        
        return $stmt->execute();
    }
    
    // Get payment by order ID
    public function getPaymentByOrderId($order_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE order_id = :order_id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Update payment status
    public function updatePaymentStatus($payment_id, $status) {
        $query = "UPDATE " . $this->table . " SET payment_status = :status WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $payment_id);
        
        return $stmt->execute();
    }
    
    // Get user payments
    public function getUserPayments($user_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Database Connection
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $user = DB_USER;
    private $password = DB_PASS;
    private $conn;
    
    public function connect() {
        try {
            $this->conn = new PDO(
                'mysql:host=' . $this->host . ';dbname=' . $this->db_name,
                $this->user,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $this->conn;
        } catch (PDOException $e) {
            echo 'Connection Error: ' . $e->getMessage();
            return null;
        }
    }
}

// Initialize Database Connection
$db = new Database();
$conn = $db->connect();

// Handle Routes
$action = isset($_GET['action']) ? $_GET['action'] : 'home';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Commerce Store</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        header { background: #333; color: white; padding: 20px; text-align: center; }
        nav { background: #444; padding: 10px; text-align: center; }
        nav a { color: white; margin: 0 15px; text-decoration: none; }
        nav a:hover { text-decoration: underline; }
        container { max-width: 1200px; margin: 20px auto; background: white; padding: 20px; border-radius: 8px; }
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .product { border: 1px solid #ddd; padding: 15px; border-radius: 5px; text-align: center; }
        .product img { width: 100%; height: 200px; object-fit: cover; border-radius: 5px; }
        .product h3 { margin: 10px 0; }
        .product p { color: #666; font-size: 14px; }
        .price { font-size: 20px; font-weight: bold; color: #27ae60; margin: 10px 0; }
        .btn { padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #2980b9; }
        form { max-width: 400px; margin: 20px auto; }
        input, textarea { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
        .cart-item { display: flex; justify-content: space-between; padding: 10px; border-bottom: 1px solid #ddd; }
        .success { color: green; margin: 10px 0; }
        .error { color: red; margin: 10px 0; }
        footer { background: #333; color: white; text-align: center; padding: 20px; margin-top: 20px; }
    </style>
</head>
<body>
    <header>
        <h1>🛍️ E-Commerce Store</h1>
    </header>
    
    <nav>
        <a href="?action=home">Home</a>
        <a href="?action=products">Products</a>
        <a href="?action=cart">Cart</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="?action=orders">My Orders</a>
            <a href="?action=logout">Logout (<?php echo $_SESSION['username']; ?>)</a>
        <?php else: ?>
            <a href="?action=login">Login</a>
            <a href="?action=register">Register</a>
        <?php endif; ?>
    </nav>
    
    <container>
        <?php
        switch ($action) {
            case 'home':
                echo "<h2>Welcome to E-Commerce Store</h2>";
                echo "<p>Browse our products and add items to your cart!</p>";
                if (isset($_SESSION['user_id'])) {
                    echo "<p>Welcome back, " . htmlspecialchars($_SESSION['username']) . "!</p>";
                }
                break;
                
            case 'products':
                $product = new Product($conn);
                $products = $product->getAllProducts();
                echo "<h2>All Products</h2>";
                echo "<div class='product-grid'>";
                foreach ($products as $prod) {
                    echo "<div class='product'>";
                    echo "<h3>" . htmlspecialchars($prod['name']) . "</h3>";
                    echo "<p>" . htmlspecialchars($prod['description']) . "</p>";
                    echo "<p>Category: " . htmlspecialchars($prod['category']) . "</p>";
                    echo "<p class='price'>$" . number_format($prod['price'], 2) . "</p>";
   