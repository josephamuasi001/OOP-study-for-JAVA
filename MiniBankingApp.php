<?php
session_start();

class Bank {
    private $conn;

    public function __construct() {
        $this->conn = new mysqli("localhost", "root", "", "banking_db");

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public function createAccount($username, $password, $initialBalance = 0) {
        $stmt = $this->conn->prepare("SELECT username FROM accounts WHERE username=?");
        $stmt->bind_param("s",$username);
        $stmt->execute();

        if($stmt->get_result()->num_rows > 0){
            return "Account already exists!";
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $createdAt = date("Y-m-d H:i:s");

        $stmt = $this->conn->prepare("INSERT INTO accounts(username,password,balance,createdAt) VALUES(?,?,?,?)");
        $stmt->bind_param("ssds",$username,$hashedPassword,$initialBalance,$createdAt);

        return $stmt->execute() ? "Account created successfully!" : "Error creating account!";
    }

    public function login($username,$password){
        $stmt = $this->conn->prepare("SELECT password FROM accounts WHERE username=?");
        $stmt->bind_param("s",$username);
        $stmt->execute();

        $result = $stmt->get_result();

        if($result->num_rows == 0){
            return "Account not found!";
        }

        $row = $result->fetch_assoc();

        if(password_verify($password,$row['password'])){
            $_SESSION['user'] = $username;
            return "Login successful!";
        }

        return "Invalid password!";
    }

    public function checkBalance(){
        $stmt = $this->conn->prepare("SELECT balance FROM accounts WHERE username=?");
        $stmt->bind_param("s",$_SESSION['user']);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc()['balance'];
    }

    public function deposit($amount){
        $stmt = $this->conn->prepare("UPDATE accounts SET balance = balance + ? WHERE username=?");
        $stmt->bind_param("ds",$amount,$_SESSION['user']);

        return $stmt->execute();
    }

    public function withdraw($amount){

        $balance = $this->checkBalance();

        if($amount > $balance){
            return "Insufficient balance!";
        }

        $stmt = $this->conn->prepare("UPDATE accounts SET balance = balance - ? WHERE username=?");
        $stmt->bind_param("ds",$amount,$_SESSION['user']);

        return $stmt->execute();
    }
}

$bank = new Bank();
$message = "";

if(isset($_POST['register'])){
    $message = $bank->createAccount($_POST['username'],$_POST['password'],$_POST['balance']);
}

if(isset($_POST['login'])){
    $message = $bank->login($_POST['username'],$_POST['password']);
}

if(isset($_POST['deposit'])){
    $bank->deposit($_POST['amount']);
}

if(isset($_POST['withdraw'])){
    $message = $bank->withdraw($_POST['amount']);
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
</head>
<body>

<h1>Mini Banking App</h1>

<?php echo $message; ?>

<?php if(!isset($_SESSION['user'])) { ?>

<h2>Register</h2>

<form method="POST">
Username<br>
<input type="text" name="username" required><br><br>

Password<br>
<input type="password" name="password" required><br><br>

Initial Balance<br>
<input type="number" name="balance"><br><br>

<button name="register">Create Account</button>
</form>

<hr>

<h2>Login</h2>

<form method="POST">
Username<br>
<input type="text" name="username" required><br><br>

Password<br>
<input type="password" name="password" required><br><br>

<button name="login">Login</button>
</form>

<?php } else { ?>

<h2>Welcome <?php echo $_SESSION['user']; ?></h2>

<p>Balance: <?php echo $bank->checkBalance(); ?></p>

<h3>Deposit</h3>

<form method="POST">
<input type="number" name="amount" required>
<button name="deposit">Deposit</button>
</form>

<h3>Withdraw</h3>

<form method="POST">
<input type="number" name="amount" required>
<button name="withdraw">Withdraw</button>
</form>

<br>

<form method="POST">
<button name="logout">Logout</button>
</form>

<?php } ?>

</body>
</html>