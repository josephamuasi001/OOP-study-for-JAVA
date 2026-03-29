<?php

class Bank {
    private $conn;
    private $loggedInUser = null;

    public function __construct($servername, $username, $dbusername, $password, $dbname) {
        $this->conn = new mysqli($servername, $dbusername, $password, $dbname);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public function createAccount($username, $password, $initialBalance = 0) {
        $stmt = $this->conn->prepare("SELECT username FROM accounts WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return "Account already exists!";
        }
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $createdAt = date('Y-m-d H:i:s');
        $stmt = $this->conn->prepare("INSERT INTO accounts (username, password, balance, createdAt) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssds", $username, $hashedPassword, $initialBalance, $createdAt);
        return $stmt->execute() ? "Account created successfully!" : "Error creating account!";
    }

    public function login($username, $password) {
        $stmt = $this->conn->prepare("SELECT password FROM accounts WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) return "Account not found!";
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $this->loggedInUser = $username;
            return "Login successful!";
        }
        return "Invalid password!";
    }

    public function logout() {
        $this->loggedInUser = null;
        return "Logged out successfully!";
    }

    public function checkBalance() {
        if (!$this->loggedInUser) return "Please login first!";
        $stmt = $this->conn->prepare("SELECT balance FROM accounts WHERE username = ?");
        $stmt->bind_param("s", $this->loggedInUser);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['balance'];
    }

    public function deposit($amount) {
        if (!$this->loggedInUser) return "Please login first!";
        if ($amount <= 0) return "Invalid amount!";
        $stmt = $this->conn->prepare("UPDATE accounts SET balance = balance + ? WHERE username = ?");
        $stmt->bind_param("ds", $amount, $this->loggedInUser);
        return $stmt->execute() ? "Deposit successful! New balance: " . $this->checkBalance() : "Error!";
    }

    public function withdraw($amount) {
        if (!$this->loggedInUser) return "Please login first!";
        if ($amount <= 0) return "Invalid amount!";
        if ($amount > $this->checkBalance()) return "Insufficient balance!";
        $stmt = $this->conn->prepare("UPDATE accounts SET balance = balance - ? WHERE username = ?");
        $stmt->bind_param("ds", $amount, $this->loggedInUser);
        return $stmt->execute() ? "Withdrawal successful! New balance: " . $this->checkBalance() : "Error!";
    }

    public function transfer($recipientUsername, $amount) {
        if (!$this->loggedInUser) return "Please login first!";
        if ($amount <= 0) return "Invalid amount!";
        if ($amount > $this->checkBalance()) return "Insufficient balance!";
        $stmt = $this->conn->prepare("SELECT username FROM accounts WHERE username = ?");
        $stmt->bind_param("s", $recipientUsername);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) return "Recipient not found!";
        
        $this->conn->begin_transaction();
        $stmt1 = $this->conn->prepare("UPDATE accounts SET balance = balance - ? WHERE username = ?");
        $stmt1->bind_param("ds", $amount, $this->loggedInUser);
        $stmt2 = $this->conn->prepare("UPDATE accounts SET balance = balance + ? WHERE username = ?");
        $stmt2->bind_param("ds", $amount, $recipientUsername);
        if ($stmt1->execute() && $stmt2->execute()) {
            $this->conn->commit();
            return "Transfer successful!";
        }
        $this->conn->rollback();
        return "Transfer failed!";
    }

    public function getAccountDetails() {
        if (!$this->loggedInUser) return "Please login first!";
        $stmt = $this->conn->prepare("SELECT * FROM accounts WHERE username = ?");
        $stmt->bind_param("s", $this->loggedInUser);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function changePassword($oldPassword, $newPassword) {
        if (!$this->loggedInUser) return "Please login first!";
        $stmt = $this->conn->prepare("SELECT password FROM accounts WHERE username = ?");
        $stmt->bind_param("s", $this->loggedInUser);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!password_verify($oldPassword, $row['password'])) return "Old password is incorrect!";
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->conn->prepare("UPDATE accounts SET password = ? WHERE username = ?");
        $stmt->bind_param("ss", $hashedPassword, $this->loggedInUser);
        return $stmt->execute() ? "Password changed successfully!" : "Error!";
    }
}

// Usage
$bank = new Bank('localhost', 'root', 'root', '', 'banking_db');
?>