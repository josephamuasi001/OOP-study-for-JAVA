<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "mobile_money, 3306");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Classes
class User {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function register($username, $email, $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("INSERT INTO users (username, email, password, balance) VALUES (?, ?, ?, 0)");
        $stmt->bind_param("sss", $username, $email, $hashedPassword);
        return $stmt->execute();
    }
    
    public function login($username, $password) {
        $stmt = $this->conn->prepare("SELECT id, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            return true;
        }
        return false;
    }
    
    public function logout() {
        session_destroy();
    }
    
    public function getBalance($user_id) {
        $stmt = $this->conn->prepare("SELECT balance FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['balance'];
    }
}

class Transaction {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function deposit($user_id, $amount) {
        $stmt = $this->conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $stmt->bind_param("di", $amount, $user_id);
        $result = $stmt->execute();
        
        if ($result) {
            $this->recordTransaction($user_id, 'deposit', $amount);
        }
        return $result;
    }
    
    public function withdraw($user_id, $amount) {
        $balance = $this->getBalance($user_id);
        if ($balance >= $amount) {
            $stmt = $this->conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            $stmt->bind_param("di", $amount, $user_id);
            $result = $stmt->execute();
            
            if ($result) {
                $this->recordTransaction($user_id, 'withdraw', $amount);
            }
            return $result;
        }
        return false;
    }
    
    public function transfer($from_user_id, $to_username, $amount) {
        $to_user = $this->getUserByUsername($to_username);
        if (!$to_user) return false;
        
        $balance = $this->getBalance($from_user_id);
        if ($balance >= $amount) {
            $this->conn->begin_transaction();
            
            $stmt = $this->conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            $stmt->bind_param("di", $amount, $from_user_id);
            $stmt->execute();
            
            $stmt = $this->conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $stmt->bind_param("di", $amount, $to_user['id']);
            $stmt->execute();
            
            $this->recordTransaction($from_user_id, 'transfer_out', $amount, $to_user['id']);
            $this->recordTransaction($to_user['id'], 'transfer_in', $amount, $from_user_id);
            
            $this->conn->commit();
            return true;
        }
        return false;
    }
    
    private function recordTransaction($user_id, $type, $amount, $related_user = null) {
        $stmt = $this->conn->prepare("INSERT INTO transactions (user_id, type, amount, related_user_id, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("isdi", $user_id, $type, $amount, $related_user);
        $stmt->execute();
    }
    
    private function getBalance($user_id) {
        $stmt = $this->conn->prepare("SELECT balance FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['balance'];
    }
    
    private function getUserByUsername($username) {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}

// Handle requests
$user = new User($conn);
$transaction = new Transaction($conn);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'register') {
            $user->register($_POST['username'], $_POST['email'], $_POST['password']);
            echo "Account created successfully!";
        }
        elseif ($_POST['action'] == 'login') {
            if ($user->login($_POST['username'], $_POST['password'])) {
                echo "Login successful!";
            } else {
                echo "Invalid credentials!";
            }
        }
        elseif ($_POST['action'] == 'logout') {
            $user->logout();
            echo "Logged out!";
        }
        elseif ($_POST['action'] == 'deposit' && isset($_SESSION['user_id'])) {
            $transaction->deposit($_SESSION['user_id'], $_POST['amount']);
            echo "Deposit successful!";
        }
        elseif ($_POST['action'] == 'withdraw' && isset($_SESSION['user_id'])) {
            if ($transaction->withdraw($_SESSION['user_id'], $_POST['amount'])) {
                echo "Withdrawal successful!";
            } else {
                echo "Insufficient balance!";
            }
        }
        elseif ($_POST['action'] == 'transfer' && isset($_SESSION['user_id'])) {
            if ($transaction->transfer($_SESSION['user_id'], $_POST['to_username'], $_POST['amount'])) {
                echo "Transfer successful!";
            } else {
                echo "Transfer failed!";
            }
        }
    }
}

$balance = isset($_SESSION['user_id']) ? $user->getBalance($_SESSION['user_id']) : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Mobile Money</title>
</head>
<body>
    <h1>Mobile Money Platform</h1>
    
    <?php if (!isset($_SESSION['user_id'])): ?>
        <h2>Register</h2>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="action" value="register">Register</button>
        </form>
        
        <h2>Login</h2>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="action" value="login">Login</button>
        </form>
    <?php else: ?>
        <h2>Balance: $<?php echo $balance; ?></h2>
        
        <h3>Deposit</h3>
        <form method="POST">
            <input type="number" name="amount" placeholder="Amount" step="0.01" required>
            <button type="submit" name="action" value="deposit">Deposit</button>
        </form>
        
        <h3>Withdraw</h3>
        <form method="POST">
            <input type="number" name="amount" placeholder="Amount" step="0.01" required>
            <button type="submit" name="action" value="withdraw">Withdraw</button>
        </form>
        
        <h3>Transfer</h3>
        <form method="POST">
            <input type="text" name="to_username" placeholder="Recipient Username" required>
            <input type="number" name="amount" placeholder="Amount" step="0.01" required>
            <button type="submit" name="action" value="transfer">Transfer</button>
        </form>
        
        <form method="POST">
            <button type="submit" name="action" value="logout">Logout</button>
        </form>
    <?php endif; ?>
</body>
</html>