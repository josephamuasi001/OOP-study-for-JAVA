<?php
session_start();

class NumberGuessingGame {
    private $games = [
        'Easy' => [
            'min' => 1,
            'max' => 10,
            'attempts' => 5
        ],
        'Medium' => [
            'min' => 1,
            'max' => 100,
            'attempts' => 7
        ],
        'Hard' => [
            'min' => 1,
            'max' => 1000,
            'attempts' => 10
        ]
    ];

    private $usersFile = 'users.json';

    public function getDifficulties() {
        return array_keys($this->games);
    }

    public function getGameConfig($difficulty) {
        return $this->games[$difficulty] ?? null;
    }

    public function generateNumber($min, $max) {
        return rand($min, $max);
    }

    public function loadUsers() {
        if (file_exists($this->usersFile)) {
            return json_decode(file_get_contents($this->usersFile), true) ?? [];
        }
        return [];
    }

    public function saveUsers($users) {
        file_put_contents($this->usersFile, json_encode($users, JSON_PRETTY_PRINT));
    }

    public function registerUser($username, $password) {
        $users = $this->loadUsers();
        if (isset($users[$username])) {
            return false;
        }
        $users[$username] = [
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'level' => 1,
            'experience' => 0,
            'games_won' => 0,
            'games_played' => 0
        ];
        $this->saveUsers($users);
        return true;
    }

    public function loginUser($username, $password) {
        $users = $this->loadUsers();
        if (!isset($users[$username]) || !password_verify($password, $users[$username]['password'])) {
            return false;
        }
        return true;
    }

    public function getUser($username) {
        $users = $this->loadUsers();
        return $users[$username] ?? null;
    }

    public function updateUserStats($username, $won, $difficulty = 'Medium', $attemptsUsed = 1) {
        $users = $this->loadUsers();
        if (!isset($users[$username])) return;

        $users[$username]['games_played']++;
        
        if ($won) {
            $users[$username]['games_won']++;
            
            // Calculate experience based on difficulty and attempts
            $basExp = ['Easy' => 100, 'Medium' => 200, 'Hard' => 300];
            $baseXP = $basExp[$difficulty] ?? 200;
            
            // Decrease XP by 20 for each attempt after the first
            $experienceGain = max(50, $baseXP - (($attemptsUsed - 1) * 20));
            $users[$username]['experience'] += $experienceGain;
        } else {
            $users[$username]['experience'] += 10;
        }

        // Level up logic (1000 exp per level, max 1000)
        $users[$username]['level'] = min(1000, floor($users[$username]['experience'] / 50) + 1);
        
        $this->saveUsers($users);
    }

    public function getRankings() {
        $users = $this->loadUsers();
        usort($users, function($a, $b) {
            return $b['level'] <=> $a['level'] ?: $b['experience'] <=> $a['experience'];
        });
        return $users;
    }
}

$game = new NumberGuessingGame();
$action = $_GET['action'] ?? 'login';
$username = $_SESSION['username'] ?? null;

if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user = $_POST['username'] ?? '';
        $pass = $_POST['password'] ?? '';
        if ($game->loginUser($user, $pass)) {
            $_SESSION['username'] = $user;
            header('Location: ?action=menu');
            exit;
        } else {
            $error = "Invalid credentials!";
        }
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Login - Number Guessing Game</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                position: relative;
                overflow: hidden;
            }
            body::before {
                content: '';
                position: absolute;
                top: -50%;
                right: -50%;
                width: 100%;
                height: 100%;
                background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
                background-size: 50px 50px;
                animation: float 20s linear infinite;
            }
            @keyframes float {
                0% { transform: translate(0, 0); }
                100% { transform: translate(50px, 50px); }
            }
            .container { 
                background: rgba(255, 255, 255, 0.95);
                padding: 40px;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                max-width: 400px;
                width: 90%;
                position: relative;
                z-index: 1;
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.3);
                animation: slideUp 0.6s ease-out;
            }
            @keyframes slideUp {
                from { opacity: 0; transform: translateY(30px); }
                to { opacity: 1; transform: translateY(0); }
            }
            h1 { 
                color: #667eea; 
                margin-bottom: 30px; 
                text-align: center; 
                font-size: 2.2em;
                text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .form-group { margin: 20px 0; }
            label { 
                display: block; 
                margin-bottom: 8px; 
                font-weight: 600; 
                color: #333;
                text-transform: uppercase;
                font-size: 0.85em;
                letter-spacing: 1px;
            }
            input { 
                padding: 14px;
                width: 100%;
                border: 2px solid #e0e0e0;
                border-radius: 10px;
                font-size: 1em;
                transition: all 0.3s ease;
                background: #f9f9f9;
            }
            input:focus {
                outline: none;
                border-color: #667eea;
                box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
                background: white;
                transform: translateY(-2px);
            }
            button { 
                width: 100%;
                padding: 14px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 10px;
                cursor: pointer;
                font-size: 1.1em;
                font-weight: 600;
                margin-top: 20px;
                transition: all 0.3s ease;
                text-transform: uppercase;
                letter-spacing: 1px;
                box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            }
            button:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
            }
            button:active {
                transform: translateY(0);
            }
            .error { 
                color: #dc3545; 
                margin-top: 15px;
                padding: 12px;
                background: #ffe6e6;
                border-left: 4px solid #dc3545;
                border-radius: 5px;
                animation: shake 0.5s ease-in-out;
            }
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
            .register-link { 
                text-align: center; 
                margin-top: 25px;
                padding-top: 20px;
                border-top: 2px solid #e0e0e0;
            }
            .register-link a { 
                color: #667eea; 
                text-decoration: none;
                font-weight: 600;
                transition: all 0.3s ease;
                position: relative;
            }
            .register-link a::after {
                content: '';
                position: absolute;
                bottom: -2px;
                left: 0;
                width: 0;
                height: 2px;
                background: #667eea;
                transition: width 0.3s ease;
            }
            .register-link a:hover::after {
                width: 100%;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🎯 Login</h1>
            <form method="POST">
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit">Login</button>
                <?php if (isset($error)): ?>
                    <div class="error"><?= $error ?></div>
                <?php endif; ?>
            </form>
            <div class="register-link">
                Don't have an account? <a href="?action=register">Register here</a>
            </div>
        </div>
    </body>
    </html>
    <?php
}
elseif ($action === 'register') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user = $_POST['username'] ?? '';
        $pass = $_POST['password'] ?? '';
        if ($game->registerUser($user, $pass)) {
            $_SESSION['username'] = $user;
            header('Location: ?action=menu');
            exit;
        } else {
            $error = "Username already exists!";
        }
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Register - Number Guessing Game</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .container { 
                background: rgba(255, 255, 255, 0.95);
                padding: 40px;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                max-width: 400px;
                width: 90%;
            }
            h1 { color: #667eea; margin-bottom: 30px; text-align: center; }
            .form-group { margin: 20px 0; }
            label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
            input { 
                padding: 12px;
                width: 100%;
                border: 2px solid #667eea;
                border-radius: 10px;
                font-size: 1em;
            }
            button { 
                width: 100%;
                padding: 14px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 10px;
                cursor: pointer;
                font-size: 1.1em;
                font-weight: 600;
                margin-top: 20px;
            }
            .error { color: #dc3545; margin-top: 10px; }
            .login-link { text-align: center; margin-top: 20px; }
            .login-link a { color: #667eea; text-decoration: none; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🎯 Register</h1>
            <form method="POST">
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit">Register</button>
                <?php if (isset($error)): ?>
                    <div class="error"><?= $error ?></div>
                <?php endif; ?>
            </form>
            <div class="login-link">
                Already have an account? <a href="?action=login">Login here</a>
            </div>
        </div>
    </body>
    </html>
    <?php
}
elseif ($action === 'menu' && $username) {
    $user = $game->getUser($username);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Menu - Number Guessing Game</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .container { 
                background: rgba(255, 255, 255, 0.95);
                padding: 40px;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                max-width: 500px;
                width: 90%;
            }
            h1 { color: #667eea; margin-bottom: 10px; text-align: center; }
            .user-info {
                background: #f0f0f0;
                padding: 20px;
                border-radius: 10px;
                margin-bottom: 30px;
                text-align: center;
            }
            .user-info p { margin: 8px 0; color: #333; }
            .stats {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 15px;
                margin-bottom: 30px;
            }
            .stat-box {
                background: #e3f2fd;
                padding: 15px;
                border-radius: 10px;
                text-align: center;
            }
            .stat-label { color: #666; font-size: 0.9em; }
            .stat-value { color: #667eea; font-size: 1.8em; font-weight: bold; }
            .button-group {
                display: grid;
                gap: 15px;
            }
            a, button { 
                padding: 14px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 10px;
                cursor: pointer;
                font-size: 1.1em;
                font-weight: 600;
                text-decoration: none;
                text-align: center;
                transition: transform 0.2s;
            }
            a:hover, button:hover { transform: translateY(-2px); }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🎮 Main Menu</h1>
            <div class="user-info">
                <p><strong>Welcome, <?= htmlspecialchars($username) ?>!</strong></p>
                <p style="font-size: 1.2em; margin-top: 10px;">⭐ Level <?= $user['level'] ?></p>
            </div>

            <div class="stats">
                <div class="stat-box">
                    <div class="stat-label">Experience</div>
                    <div class="stat-value"><?= $user['experience'] ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Games Won</div>
                    <div class="stat-value"><?= $user['games_won'] ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Games Played</div>
                    <div class="stat-value"><?= $user['games_played'] ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Win Rate</div>
                    <div class="stat-value"><?= $user['games_played'] > 0 ? round(($user['games_won'] / $user['games_played']) * 100) : 0 ?>%</div>
                </div>
            </div>

            <div class="button-group">
                <a href="?action=select">Play Game</a>
                <a href="?action=rankings">View Rankings</a>
                <a href="?action=logout">Logout</a>
            </div>
        </div>
    </body>
    </html>
    <?php
}
elseif ($action === 'rankings') {
    $rankings = $game->getRankings();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Rankings - Number Guessing Game</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                padding: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .container { 
                background: rgba(255, 255, 255, 0.95);
                padding: 40px;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                max-width: 600px;
                width: 100%;
            }
            h1 { color: #667eea; margin-bottom: 30px; text-align: center; }
            table {
                width: 100%;
                border-collapse: collapse;
            }
            th {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 15px;
                text-align: left;
            }
            td {
                padding: 15px;
                border-bottom: 1px solid #ddd;
            }
            tr:hover { background: #f5f5f5; }
            .rank { font-weight: bold; color: #667eea; }
            a { 
                display: inline-block;
                margin-top: 30px;
                padding: 12px 30px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                text-decoration: none;
                border-radius: 10px;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🏆 Top Rankings</h1>
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Username</th>
                        <th>Level</th>
                        <th>Experience</th>
                        <th>Games Won</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rankings as $rank => $data): 
                        $username = array_search($data, $game->loadUsers());
                    ?>
                    <tr>
                        <td class="rank">#<?= $rank + 1 ?></td>
                        <td><?= htmlspecialchars($username) ?></td>
                        <td>⭐ <?= $data['level'] ?></td>
                        <td><?= $data['experience'] ?></td>
                        <td><?= $data['games_won'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <a href="?action=menu">Back to Menu</a>
        </div>
    </body>
    </html>
    <?php
}
elseif ($action === 'logout') {
    session_destroy();
    header('Location: ?action=login');
    exit;
}
elseif ($action === 'select' && $username) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Select Difficulty - Number Guessing Game</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .container { 
                background: rgba(255, 255, 255, 0.95);
                padding: 40px;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                max-width: 500px;
                width: 90%;
            }
            h1 { color: #667eea; margin-bottom: 30px; text-align: center; }
            .form-group { margin: 20px 0; }
            label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
            select { 
                padding: 12px;
                width: 100%;
                border: 2px solid #667eea;
                border-radius: 10px;
                font-size: 1em;
            }
            button { 
                width: 100%;
                padding: 14px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 10px;
                cursor: pointer;
                font-size: 1.1em;
                font-weight: 600;
                margin-top: 20px;
            }
            a {
                display: inline-block;
                margin-top: 15px;
                color: #667eea;
                text-decoration: none;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🎯 Guess The Number</h1>
            <form method="POST" action="?action=start">
                <div class="form-group">
                    <label>Select Difficulty:</label>
                    <select name="difficulty" required>
                        <option value="">-- Choose Difficulty --</option>
                        <?php foreach ($game->getDifficulties() as $diff): ?>
                            <option value="<?= $diff ?>"><?= $diff ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit">Start Game</button>
                <a href="?action=menu">← Back to Menu</a>
            </form>
        </div>
    </body>
    </html>
    <?php
}
elseif ($action === 'start' && $username) {
    $difficulty = $_POST['difficulty'];
    $config = $game->getGameConfig($difficulty);

    if (!$config) {
        header('Location: ?action=select');
        exit;
    }

    $_SESSION['secret'] = $game->generateNumber($config['min'], $config['max']);
    $_SESSION['difficulty'] = $difficulty;
    $_SESSION['min'] = $config['min'];
    $_SESSION['max'] = $config['max'];
    $_SESSION['attempts'] = $config['attempts'];
    $_SESSION['guesses'] = [];
    $_SESSION['current_attempt'] = 0;
    
    header('Location: ?action=game');
}
elseif ($action === 'game' && $username) {
    $secret = $_SESSION['secret'] ?? 0;
    $attempts = $_SESSION['attempts'] ?? 0;
    $current = $_SESSION['current_attempt'] ?? 0;
    $difficulty = $_SESSION['difficulty'] ?? '';
    $min = $_SESSION['min'] ?? 1;
    $max = $_SESSION['max'] ?? 10;
    $guesses = $_SESSION['guesses'] ?? [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $guess = intval($_POST['guess'] ?? 0);
        $_SESSION['guesses'][] = $guess;
        $_SESSION['current_attempt']++;

        if ($guess == $secret) {
            header('Location: ?action=results&result=win');
            exit;
        }

        if ($_SESSION['current_attempt'] >= $attempts) {
            header('Location: ?action=results&result=lose');
            exit;
        }
        header('Location: ?action=game');
        exit;
    }

    if ($current >= $attempts) {
        header('Location: ?action=results&result=lose');
        exit;
    }

    $lastGuess = end($guesses);
    $hint = '';
    $closenessHint = '';
    if ($lastGuess && $current > 0) {
        $hint = $lastGuess < $secret ? "Too low! Try higher." : "Too high! Try lower.";
        
        // Provide a clue only when user is extremely close (within 10 of the secret)
        $difference = abs($lastGuess - $secret);
        if ($difference <= 10 && $difference > 0) {
            $closenessHint = "🔥 You're extremely close! You're just a few numbers away!";
        }
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Playing - Number Guessing Game</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .container { 
                background: rgba(255, 255, 255, 0.95);
                padding: 40px;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                max-width: 600px;
                width: 90%;
            }
            h1 { color: #f5576c; text-align: center; margin-bottom: 20px; }
            .info {
                display: flex;
                justify-content: space-between;
                margin-bottom: 30px;
                padding: 15px;
                background: #f0f0f0;
                border-radius: 10px;
            }
            .info-item { text-align: center; }
            .info-item .label { color: #666; font-size: 0.9em; }
            .info-item .value { color: #f5576c; font-size: 1.5em; font-weight: bold; }
            .range { text-align: center; margin: 20px 0; font-size: 1.1em; color: #333; }
            .hint { 
                padding: 15px;
                background: #fff3cd;
                border-left: 4px solid #ffc107;
                border-radius: 5px;
                margin: 20px 0;
                color: #856404;
            }
            .closeness-hint {
                padding: 15px;
                background: #e8f5e9;
                border-left: 4px solid #4caf50;
                border-radius: 5px;
                margin: 15px 0;
                color: #2e7d32;
                font-weight: bold;
                text-align: center;
                font-size: 1.1em;
            }
            .guessed-numbers {
                padding: 15px;
                background: #f3e5f5;
                border-radius: 10px;
                margin: 20px 0;
            }
            .guessed-numbers strong {
                display: block;
                margin-bottom: 10px;
                color: #6a1b9a;
            }
            .guesses-list {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }
            .guess-badge {
                background: #ce93d8;
                color: white;
                padding: 6px 12px;
                border-radius: 20px;
                font-size: 0.9em;
                font-weight: 600;
            }
            input { 
                width: 100%;
                padding: 14px;
                border: 2px solid #f5576c;
                border-radius: 10px;
                font-size: 1.1em;
                margin: 20px 0;
            }
            button { 
                width: 100%;
                padding: 14px;
                background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                color: white;
                border: none;
                border-radius: 10px;
                cursor: pointer;
                font-size: 1.1em;
                font-weight: 600;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🎯 Guess The Number</h1>
            <div class="info">
                <div class="info-item">
                    <div class="label">Difficulty</div>
                    <div class="value"><?= $difficulty ?></div>
                </div>
                <div class="info-item">
                    <div class="label">Attempts Left</div>
                    <div class="value"><?= $attempts - $current ?>/<?= $attempts ?></div>
                </div>
            </div>

            <div class="range">Range: <strong><?= $min ?> - <?= $max ?></strong></div>

            <?php if ($hint): ?>
                <div class="hint"><?= $hint ?></div>
            <?php endif; ?>

            <?php if ($closenessHint): ?>
                <div class="closeness-hint"><?= $closenessHint ?></div>
            <?php endif; ?>

            <?php if (!empty($guesses)): ?>
                <div class="guessed-numbers">
                    <strong>📋 Your Guesses So Far:</strong>
                    <div class="guesses-list">
                        <?php foreach ($guesses as $g): ?>
                            <div class="guess-badge"><?= $g ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="number" name="guess" min="<?= $min ?>" max="<?= $max ?>" required autofocus placeholder="Enter your guess">
                <button type="submit">Submit Guess</button>
            </form>
        </div>
    </body>
    </html>
    <?php
}
elseif ($action === 'results' && $username) {
    $result = $_GET['result'] ?? 'lose';
    $secret = $_SESSION['secret'] ?? 0;
    $guesses = $_SESSION['guesses'] ?? [];
    $attempts = count($guesses);
    $difficulty = $_SESSION['difficulty'] ?? '';
    
    $won = $result === 'win';
    $userBefore = $game->getUser($username);
    $xpBefore = $userBefore['experience'] ?? 0;
    $game->updateUserStats($username, $won, $difficulty, $attempts);
    $user = $game->getUser($username);
    $xpEarned = $user['experience'] - $xpBefore;
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Results - Number Guessing Game</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, <?= $won ? '#11998e 0%, #38ef7d' : '#eb3349 0%, #f45c43' ?> 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .container { 
                background: rgba(255, 255, 255, 0.95);
                padding: 40px;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                max-width: 500px;
                width: 90%;
                text-align: center;
            }
            h1 { font-size: 2.5em; margin-bottom: 20px; color: <?= $won ? '#11998e' : '#eb3349' ?>; }
            .message { font-size: 1.3em; margin-bottom: 30px; color: #333; }
            .details {
                background: #f0f0f0;
                padding: 20px;
                border-radius: 10px;
                margin: 20px 0;
            }
            .details p { margin: 10px 0; color: #333; }
            .glow { color: #667eea; font-weight: bold; font-size: 1.1em; }
            .guesses { text-align: left; margin-top: 20px; }
            .guesses strong { display: block; margin-bottom: 10px; }
            .guess-item { padding: 8px; background: #e0e0e0; margin: 5px 0; border-radius: 5px; }
            a { 
                display: inline-block;
                margin: 15px auto;
                padding: 14px 30px;
                background: linear-gradient(135deg, <?= $won ? '#11998e 0%, #38ef7d' : '#eb3349 0%, #f45c43' ?> 100%);
                color: white;
                text-decoration: none;
                border-radius: 10px;
                font-weight: 600;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <?php if ($won): ?>
                <h1>🎉 You Won!</h1>
                <div class="message">You guessed the number in <?= $attempts ?> attempt<?= $attempts !== 1 ? 's' : '' ?>!</div>
            <?php else: ?>
                <h1>💔 Game Over</h1>
                <div class="message">You ran out of attempts!</div>
            <?php endif; ?>

            <div class="details">
                <p><strong>Difficulty:</strong> <?= $difficulty ?></p>
                <p><strong>The Secret Number:</strong> <?= $secret ?></p>
                <p><strong>Your Attempts:</strong> <?= $attempts ?></p>
                <p style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ccc;">
                    <strong>Level:</strong> <span class="glow">⭐ <?= $user['level'] ?></span><br>
                    <strong>Experience:</strong> <span class="glow"><?= $user['experience'] ?> XP</span>
                </p>
            </div>

            <div class="guesses">
                <strong>Your Guesses:</strong>
                <?php foreach ($guesses as $g): ?>
                    <div class="guess-item"><?= $g ?></div>
                <?php endforeach; ?>
            </div>

            <a href="?action=select">PlayAgain</a>
            <a href="?action=menu">Back to Menu</a>
        </div>
    </body>
    </html>
    <?php
} else {
    header('Location: ?action=login');
}