<?php
session_start();

class Bank {
    private $conn;

    public function __construct(){
        $this->conn = new mysqli("localhost","root","","banking_db");
        if($this->conn->connect_error){
            die("Connection failed: ".$this->conn->connect_error);
        }

        // Create tables if they don't exist
        $this->conn->query("
        CREATE TABLE IF NOT EXISTS accounts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE,
            password VARCHAR(255),
            balance DECIMAL(10,2) DEFAULT 0,
            createdAt DATETIME
        )");

        $this->conn->query("
        CREATE TABLE IF NOT EXISTS transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender VARCHAR(50),
            recipient VARCHAR(50),
            type VARCHAR(20),
            amount DECIMAL(10,2),
            dateTime DATETIME
        )");
    }

    // Register
    public function createAccount($username,$password,$initialBalance=0){
        $stmt = $this->conn->prepare("SELECT username FROM accounts WHERE username=?");
        $stmt->bind_param("s",$username);
        $stmt->execute();
        if($stmt->get_result()->num_rows>0) return "Account exists!";
        $hashed = password_hash($password,PASSWORD_BCRYPT);
        $createdAt = date("Y-m-d H:i:s");
        $stmt = $this->conn->prepare("INSERT INTO accounts(username,password,balance,createdAt) VALUES(?,?,?,?)");
        $stmt->bind_param("ssds",$username,$hashed,$initialBalance,$createdAt);
        return $stmt->execute() ? "Account created!" : "Error!";
    }

    // Login
    public function login($username,$password){
        $stmt = $this->conn->prepare("SELECT password FROM accounts WHERE username=?");
        $stmt->bind_param("s",$username);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows==0) return "Account not found!";
        $row = $result->fetch_assoc();
        if(password_verify($password,$row['password'])){
            $_SESSION['user']=$username;
            return "Login successful!";
        }
        return "Invalid password!";
    }

    // Check balance
    public function checkBalance(){
        $stmt = $this->conn->prepare("SELECT balance FROM accounts WHERE username=?");
        $stmt->bind_param("s",$_SESSION['user']);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['balance'];
    }

    // Deposit
    public function deposit($amount){
        $stmt = $this->conn->prepare("UPDATE accounts SET balance = balance + ? WHERE username=?");
        $stmt->bind_param("ds",$amount,$_SESSION['user']);
        if($stmt->execute()){
            $this->logTransaction($_SESSION['user'],$_SESSION['user'],'Deposit',$amount);
            return "Deposit successful!";
        }
        return "Error depositing!";
    }

    // Withdraw
    public function withdraw($amount){
        $balance = $this->checkBalance();
        if($amount>$balance) return "Insufficient balance!";
        $stmt = $this->conn->prepare("UPDATE accounts SET balance = balance - ? WHERE username=?");
        $stmt->bind_param("ds",$amount,$_SESSION['user']);
        if($stmt->execute()){
            $this->logTransaction($_SESSION['user'],$_SESSION['user'],'Withdraw',$amount);
            return "Withdrawal successful!";
        }
        return "Error!";
    }

    // Transfer
    public function transfer($recipient,$amount){
        if($_SESSION['user']==$recipient) return "Cannot transfer to yourself!";
        // check recipient exists
        $stmt = $this->conn->prepare("SELECT username FROM accounts WHERE username=?");
        $stmt->bind_param("s",$recipient);
        $stmt->execute();
        if($stmt->get_result()->num_rows==0) return "Recipient not found!";
        // check balance
        $balance = $this->checkBalance();
        if($amount>$balance) return "Insufficient balance!";
        // transaction
        $this->conn->begin_transaction();
        $stmt1 = $this->conn->prepare("UPDATE accounts SET balance = balance - ? WHERE username=?");
        $stmt1->bind_param("ds",$amount,$_SESSION['user']);
        $stmt2 = $this->conn->prepare("UPDATE accounts SET balance = balance + ? WHERE username=?");
        $stmt2->bind_param("ds",$amount,$recipient);
        if($stmt1->execute() && $stmt2->execute()){
            $this->conn->commit();
            $this->logTransaction($_SESSION['user'],$recipient,'Transfer',$amount);
            return "Transfer successful!";
        } else {
            $this->conn->rollback();
            return "Transfer failed!";
        }
    }

    // Log transactions
    private function logTransaction($sender,$recipient,$type,$amount){
        $stmt = $this->conn->prepare("INSERT INTO transactions(sender,recipient,type,amount,dateTime) VALUES(?,?,?,?,?)");
        $dt = date("Y-m-d H:i:s");
        $stmt->bind_param("sssds",$sender,$recipient,$type,$amount,$dt);
        $stmt->execute();
    }

    // Get transaction history
    public function getHistory(){
        $stmt = $this->conn->prepare("SELECT * FROM transactions WHERE sender=? OR recipient=? ORDER BY id DESC");
        $stmt->bind_param("ss",$_SESSION['user'],$_SESSION['user']);
        $stmt->execute();
        return $stmt->get_result();
    }
}

// ---------------- POST Processing ----------------
$bank = new Bank();
$message = "";

if(isset($_POST['register'])){
    $message = $bank->createAccount($_POST['username'],$_POST['password'],$_POST['balance']);
}

if(isset($_POST['login'])){
    $message = $bank->login($_POST['username'],$_POST['password']);
}

if(isset($_POST['deposit'])){
    $message = $bank->deposit($_POST['amount']);
}

if(isset($_POST['withdraw'])){
    $message = $bank->withdraw($_POST['amount']);
}

if(isset($_POST['transfer'])){
    $message = $bank->transfer($_POST['recipient'],$_POST['amount']);
}

if(isset($_POST['logout'])){
    session_destroy();
    header("Refresh:0");
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Mini Banking App</title>
<style>
body { font-family: Arial; background: #f4f4f4; padding: 20px; }
h1,h2,h3 { color: #333; }
form { background: #fff; padding: 15px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 0 5px #ccc; }
input, button { padding: 8px; margin: 5px 0; width: 100%; }
button { background: #28a745; color: white; border: none; cursor: pointer; }
button:hover { background: #218838; }
.message { background: #fffae6; padding: 10px; margin-bottom: 20px; border-left: 4px solid #ffc107; }
table { border-collapse: collapse; width: 100%; margin-top: 10px; }
table, th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
th { background: #eee; }
</style>
</head>
<body>

<h1>Mini Banking App</h1>

<?php if($message) echo "<div class='message'>$message</div>"; ?>

<?php if(!isset($_SESSION['user'])) { ?>

<h2>Register</h2>
<form method="POST">
Username:<br><input type="text" name="username" required>
Password:<br><input type="password" name="password" required>
Initial Balance:<br><input type="number" name="balance" value="0"><br>
<button name="register">Register</button>
</form>

<h2>Login</h2>
<form method="POST">
Username:<br><input type="text" name="username" required>
Password:<br><input type="password" name="password" required><br>
<button name="login">Login</button>
</form>

<?php } else { ?>

<h2>Welcome <?php echo $_SESSION['user']; ?></h2>
<p><strong>Balance:</strong> <?php echo $bank->checkBalance(); ?></p>

<h3>Deposit</h3>
<form method="POST">
Amount:<br><input type="number" name="amount" required><br>
<button name="deposit">Deposit</button>
</form>

<h3>Withdraw</h3>
<form method="POST">
Amount:<br><input type="number" name="amount" required><br>
<button name="withdraw">Withdraw</button>
</form>

<h3>Transfer</h3>
<form method="POST">
Recipient Username:<br><input type="text" name="recipient" required><br>
Amount:<br><input type="number" name="amount" required><br>
<button name="transfer">Transfer</button>
</form>

<h3>Transaction History</h3>
<table>
<tr><th>ID</th><th>Sender</th><th>Recipient</th><th>Type</th><th>Amount</th><th>Date</th></tr>
<?php
$history = $bank->getHistory();
while($row = $history->fetch_assoc()){
    echo "<tr>
    <td>{$row['id']}</td>
    <td>{$row['sender']}</td>
    <td>{$row['recipient']}</td>
    <td>{$row['type']}</td>
    <td>{$row['amount']}</td>
    <td>{$row['dateTime']}</td>
    </tr>";
}
?>
</table>

<form method="POST">
<button name="logout">Logout</button>
</form>

<?php } ?>

</body>
</html>