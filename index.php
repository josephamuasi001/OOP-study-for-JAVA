<?php
session_start();

class NumberGuessingGame {
    private $games = [
        'Easy' => ['min' => 1, 'max' => 10, 'attempts' => 6, 'xp_base' => 80],
        'Medium' => ['min' => 1, 'max' => 100, 'attempts' => 8, 'xp_base' => 150],
        'Hard' => ['min' => 1, 'max' => 1000, 'attempts' => 10, 'xp_base' => 250],
        'Expert' => ['min' => 1, 'max' => 5000, 'attempts' => 12, 'xp_base' => 400]
    ];

    private $usersFile = 'users.json';
    private $historyFile = 'game_history.json';

    public function getDifficulties() {
        return array_keys($this->games);
    }

    public function getGameConfig($difficulty) {
        return $this->games[$difficulty] ?? null;
    }

    public function generateNumber($min, $max) {
        return random_int($min, $max);
    }

    private function loadUsers() {
        if (file_exists($this->usersFile)) {
            return json_decode(file_get_contents($this->usersFile), true) ?? [];
        }
        return [];
    }

    private function saveUsers($users) {
        file_put_contents($this->usersFile, json_encode($users, JSON_PRETTY_PRINT));
    }

    private function loadHistory() {
        if (file_exists($this->historyFile)) {
            return json_decode(file_get_contents($this->historyFile), true) ?? [];
        }
        return [];
    }

    private function saveHistory($history) {
        file_put_contents($this->historyFile, json_encode($history, JSON_PRETTY_PRINT));
    }

    public function registerUser($username, $password) {
        $users = $this->loadUsers();
        if (isset($users[$username])) return false;

        $users[$username] = [
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'level' => 1,
            'experience' => 0,
            'games_won' => 0,
            'games_played' => 0,
            'avatar' => '🎯',
            'achievements' => [],
            'join_date' => date('Y-m-d')
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

    public function updateUserStats($username, $won, $difficulty, $attemptsUsed, $timeTaken) {
        $users = $this->loadUsers();
        if (!isset($users[$username])) return;

        $users[$username]['games_played']++;

        $config = $this->games[$difficulty] ?? $this->games['Medium'];
        $xpGain = 10;

        if ($won) {
            $users[$username]['games_won']++;
            $xpGain = max(30, $config['xp_base'] - ($attemptsUsed - 1) * 15);
            
            // Bonus for fast win
            if ($timeTaken < 30) $xpGain += 50;
            elseif ($timeTaken < 60) $xpGain += 25;
        }

        $users[$username]['experience'] += $xpGain;
        $users[$username]['level'] = min(999, floor($users[$username]['experience'] / 60) + 1);

        // Simple achievements
        if ($won && $difficulty === 'Hard' && $attemptsUsed <= 3) {
            if (!in_array('Speed Demon', $users[$username]['achievements'])) {
                $users[$username]['achievements'][] = 'Speed Demon';
            }
        }
        if ($users[$username]['games_won'] >= 50 && !in_array('Master Guesser', $users[$username]['achievements'])) {
            $users[$username]['achievements'][] = 'Master Guesser';
        }

        $this->saveUsers($users);

        // Save game history
        $history = $this->loadHistory();
        $history[] = [
            'username' => $username,
            'difficulty' => $difficulty,
            'won' => $won,
            'attempts' => $attemptsUsed,
            'time' => $timeTaken,
            'date' => date('Y-m-d H:i')
        ];
        // Keep only last 50 games
        if (count($history) > 50) array_shift($history);
        $this->saveHistory($history);
    }

    public function getRankings() {
        $users = $this->loadUsers();
        $list = [];
        foreach ($users as $user => $data) {
            $list[] = array_merge(['username' => $user], $data);
        }
        usort($list, function($a, $b) {
            return $b['level'] <=> $a['level'] ?: $b['experience'] <=> $a['experience'];
        });
        return $list;
    }

    public function getUserHistory($username) {
        $history = $this->loadHistory();
        return array_filter($history, fn($g) => $g['username'] === $username);
    }
}

// ====================== MAIN LOGIC ======================
$game = new NumberGuessingGame();
$action = $_GET['action'] ?? 'login';
$username = $_SESSION['username'] ?? null;

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'login') {
        $user = trim($_POST['username'] ?? '');
        $pass = $_POST['password'] ?? '';
        if ($game->loginUser($user, $pass)) {
            $_SESSION['username'] = $user;
            header('Location: ?action=menu');
            exit;
        } else {
            $error = "Invalid username or password!";
        }
    }

    if ($action === 'register') {
        $user = trim($_POST['username'] ?? '');
        $pass = $_POST['password'] ?? '';
        if (strlen($user) < 3) $error = "Username too short!";
        elseif (strlen($pass) < 4) $error = "Password too short!";
        elseif ($game->registerUser($user, $pass)) {
            $_SESSION['username'] = $user;
            header('Location: ?action=menu');
            exit;
        } else {
            $error = "Username already taken!";
        }
    }
}

// Logout
if ($action === 'logout') {
    session_destroy();
    header('Location: ?action=login');
    exit;
}

$user = $username ? $game->getUser($username) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NumGuess Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        :root {
            --primary: 139 92 246;
        }
        body {
            font-family: 'Inter', system-ui, sans-serif;
        }
        .glass {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }
        .neon-text {
            text-shadow: 0 0 20px rgb(139 92 246);
        }
        .card-hover:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.4);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-violet-950 via-purple-900 to-indigo-950 text-white min-h-screen">

<?php if ($action === 'login' || $action === 'register'): ?>
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="glass border border-white/20 rounded-3xl shadow-2xl max-w-md w-full overflow-hidden">
            <div class="p-10">
                <div class="text-center mb-8">
                    <div class="text-6xl mb-4">🎯</div>
                    <h1 class="text-4xl font-bold tracking-tight neon-text">NumGuess Pro</h1>
                    <p class="text-purple-300 mt-2">Test your intuition</p>
                </div>

                <?php if ($action === 'login'): ?>
                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium mb-2">Username</label>
                        <input type="text" name="username" required 
                               class="w-full bg-white/10 border border-white/30 rounded-2xl px-5 py-4 focus:outline-none focus:border-violet-400 transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Password</label>
                        <input type="password" name="password" required 
                               class="w-full bg-white/10 border border-white/30 rounded-2xl px-5 py-4 focus:outline-none focus:border-violet-400 transition">
                    </div>
                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-violet-500 to-fuchsia-500 hover:from-violet-600 hover:to-fuchsia-600 py-4 rounded-2xl font-semibold text-lg transition transform hover:scale-105">
                        Login
                    </button>
                    <?php if (isset($error)): ?>
                        <p class="text-red-400 text-center"><?= htmlspecialchars($error) ?></p>
                    <?php endif; ?>
                </form>
                <p class="text-center mt-6 text-purple-300">
                    Don't have an account? <a href="?action=register" class="text-violet-400 hover:text-violet-300 font-medium">Register</a>
                </p>
                <?php endif; ?>

                <?php if ($action === 'register'): ?>
                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium mb-2">Choose Username</label>
                        <input type="text" name="username" required 
                               class="w-full bg-white/10 border border-white/30 rounded-2xl px-5 py-4 focus:outline-none focus:border-violet-400 transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Password</label>
                        <input type="password" name="password" required 
                               class="w-full bg-white/10 border border-white/30 rounded-2xl px-5 py-4 focus:outline-none focus:border-violet-400 transition">
                    </div>
                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-violet-500 to-fuchsia-500 hover:from-violet-600 hover:to-fuchsia-600 py-4 rounded-2xl font-semibold text-lg transition transform hover:scale-105">
                        Create Account
                    </button>
                    <?php if (isset($error)): ?>
                        <p class="text-red-400 text-center"><?= htmlspecialchars($error) ?></p>
                    <?php endif; ?>
                </form>
                <p class="text-center mt-6 text-purple-300">
                    Already have an account? <a href="?action=login" class="text-violet-400 hover:text-violet-300 font-medium">Login</a>
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (in_array($action, ['menu', 'select', 'game', 'results', 'rankings']) && $username): ?>

<nav class="border-b border-white/10 bg-black/30 backdrop-blur-lg fixed w-full z-50">
    <div class="max-w-6xl mx-auto px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <span class="text-3xl">🎯</span>
            <div>
                <h1 class="font-bold text-xl tracking-tight">NumGuess Pro</h1>
                <p class="text-xs text-purple-400">Welcome back, <span class="font-medium"><?= htmlspecialchars($username) ?></span></p>
            </div>
        </div>
        <div class="flex items-center gap-6">
            <button onclick="toggleTheme()" class="text-2xl hover:scale-110 transition">🌗</button>
            <a href="?action=menu" class="hover:text-violet-400 transition">Menu</a>
            <a href="?action=rankings" class="hover:text-violet-400 transition">Rankings</a>
            <a href="?action=logout" class="text-red-400 hover:text-red-500 transition">Logout</a>
        </div>
    </div>
</nav>

<div class="pt-20 pb-12 px-4 max-w-4xl mx-auto">

<?php 
// Menu
if ($action === 'menu'): 
    $history = array_slice(array_reverse($game->getUserHistory($username)), 0, 5);
?>
    <div class="text-center mb-12">
        <div class="inline-flex items-center gap-4 bg-white/10 backdrop-blur-md px-8 py-4 rounded-3xl mb-6">
            <span class="text-5xl"><?= $user['avatar'] ?? '🎯' ?></span>
            <div>
                <h2 class="text-4xl font-bold"><?= htmlspecialchars($username) ?></h2>
                <p class="text-2xl text-violet-400">Level <?= $user['level'] ?> • <?= $user['experience'] ?> XP</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div onclick="location.href='?action=select'" 
             class="glass border border-white/20 rounded-3xl p-8 text-center cursor-pointer card-hover">
            <div class="text-6xl mb-4">🎮</div>
            <h3 class="text-2xl font-semibold mb-2">Play Game</h3>
            <p class="text-purple-300">Test your guessing skills</p>
        </div>

        <div onclick="location.href='?action=rankings'" 
             class="glass border border-white/20 rounded-3xl p-8 text-center cursor-pointer card-hover">
            <div class="text-6xl mb-4">🏆</div>
            <h3 class="text-2xl font-semibold mb-2">Rankings</h3>
            <p class="text-purple-300">See where you stand</p>
        </div>

        <div class="glass border border-white/20 rounded-3xl p-8">
            <h3 class="text-xl font-semibold mb-4 flex items-center gap-2"><i class="fas fa-trophy"></i> Achievements</h3>
            <?php if (empty($user['achievements'])): ?>
                <p class="text-purple-400 text-sm">No achievements yet. Keep playing!</p>
            <?php else: ?>
                <?php foreach ($user['achievements'] as $ach): ?>
                    <div class="bg-emerald-500/20 text-emerald-300 px-4 py-2 rounded-2xl text-sm inline-block mb-2"><?= $ach ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($history)): ?>
    <div class="mt-12">
        <h3 class="text-xl font-semibold mb-4">Recent Games</h3>
        <div class="space-y-3">
            <?php foreach ($history as $g): ?>
            <div class="glass border border-white/10 rounded-2xl p-4 flex justify-between items-center">
                <div>
                    <span class="font-medium"><?= $g['difficulty'] ?></span>
                    <span class="text-xs text-purple-400 ml-3"><?= $g['date'] ?></span>
                </div>
                <div class="<?= $g['won'] ? 'text-emerald-400' : 'text-red-400' ?>">
                    <?= $g['won'] ? '✓ Won' : '✕ Lost' ?> in <?= $g['attempts'] ?> attempts
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

<?php endif; ?>

<?php 
// Select Difficulty
if ($action === 'select'): ?>
    <div class="max-w-md mx-auto">
        <h1 class="text-5xl font-bold text-center mb-10">Choose Difficulty</h1>
        
        <form method="POST" action="?action=start" class="space-y-4">
            <?php foreach ($game->getDifficulties() as $diff): 
                $cfg = $game->getGameConfig($diff);
            ?>
            <button type="submit" name="difficulty" value="<?= $diff ?>"
                    class="w-full glass border border-white/20 hover:border-violet-400 rounded-3xl p-8 text-left transition-all group">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-3xl font-bold group-hover:text-violet-300"><?= $diff ?></h3>
                        <p class="text-purple-300 mt-1"><?= $cfg['min'] ?> - <?= $cfg['max'] ?> • <?= $cfg['attempts'] ?> attempts</p>
                    </div>
                    <div class="text-4xl opacity-70"><?= $diff === 'Easy' ? '🌱' : ($diff === 'Medium' ? '🔥' : ($diff === 'Hard' ? '☠️' : '⚡')) ?></div>
                </div>
            </button>
            <?php endforeach; ?>
        </form>
        
        <a href="?action=menu" class="block text-center mt-8 text-purple-400 hover:text-white">← Back to Menu</a>
    </div>
<?php endif; ?>

<?php 
// Start Game
if ($action === 'start' && isset($_POST['difficulty'])) {
    $difficulty = $_POST['difficulty'];
    $config = $game->getGameConfig($difficulty);
    if ($config) {
        $_SESSION['secret'] = $game->generateNumber($config['min'], $config['max']);
        $_SESSION['difficulty'] = $difficulty;
        $_SESSION['min'] = $config['min'];
        $_SESSION['max'] = $config['max'];
        $_SESSION['attempts_left'] = $config['attempts'];
        $_SESSION['guesses'] = [];
        $_SESSION['start_time'] = time();
        header('Location: ?action=game');
        exit;
    }
}
?>

<?php 
// Game Screen
if ($action === 'game' && isset($_SESSION['secret'])): 
    $secret = $_SESSION['secret'];
    $difficulty = $_SESSION['difficulty'];
    $attempts_left = $_SESSION['attempts_left'];
    $guesses = $_SESSION['guesses'] ?? [];
    $time_elapsed = time() - ($_SESSION['start_time'] ?? time());
?>

    <div class="max-w-lg mx-auto text-center">
        <div class="glass rounded-3xl p-10">
            <h1 class="text-4xl font-bold mb-2">Guess the Number</h1>
            <p class="text-purple-300 mb-8"><?= $difficulty ?> • <?= $_SESSION['min'] ?> - <?= $_SESSION['max'] ?></p>

            <div class="flex justify-center gap-8 mb-10">
                <div class="text-center">
                    <div class="text-5xl font-mono font-bold text-emerald-400"><?= $attempts_left ?></div>
                    <div class="text-xs tracking-widest uppercase">Attempts Left</div>
                </div>
                <div class="text-center">
                    <div class="text-5xl font-mono font-bold"><?= floor($time_elapsed/60) ?>:<?= str_pad($time_elapsed%60, 2, '0', STR_PAD_LEFT) ?></div>
                    <div class="text-xs tracking-widest uppercase">Time</div>
                </div>
            </div>

            <?php if (!empty($guesses)): ?>
            <div class="mb-8">
                <p class="text-sm text-purple-400 mb-3">Your guesses</p>
                <div class="flex flex-wrap gap-3 justify-center">
                    <?php foreach ($guesses as $g): ?>
                        <div class="bg-white/10 px-5 py-2 rounded-2xl text-lg"><?= $g ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" action="?action=guess" class="space-y-6">
                <input type="number" name="guess" min="<?= $_SESSION['min'] ?>" max="<?= $_SESSION['max'] ?>" 
                       class="w-full bg-white/10 border border-white/30 text-4xl text-center py-8 rounded-3xl focus:outline-none focus:border-violet-400" 
                       placeholder="Your guess" autofocus required>
                <button type="submit"
                        class="w-full bg-gradient-to-r from-violet-500 to-fuchsia-500 py-6 rounded-3xl text-xl font-semibold hover:scale-105 transition">
                    Submit Guess
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php 
// Process Guess
if ($action === 'guess' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['secret'])) {
    $guess = intval($_POST['guess'] ?? 0);
    $_SESSION['guesses'][] = $guess;
    $_SESSION['attempts_left']--;

    if ($guess == $_SESSION['secret']) {
        $timeTaken = time() - ($_SESSION['start_time'] ?? time());
        $game->updateUserStats($username, true, $_SESSION['difficulty'], count($_SESSION['guesses']), $timeTaken);
        header('Location: ?action=results&result=win');
        exit;
    }

    if ($_SESSION['attempts_left'] <= 0) {
        $timeTaken = time() - ($_SESSION['start_time'] ?? time());
        $game->updateUserStats($username, false, $_SESSION['difficulty'], count($_SESSION['guesses']), $timeTaken);
        header('Location: ?action=results&result=lose');
        exit;
    }

    header('Location: ?action=game');
    exit;
}
?>

<?php 
// Results
if ($action === 'results'):
    $won = ($_GET['result'] ?? '') === 'win';
    $secret = $_SESSION['secret'] ?? '?';
    $guesses = $_SESSION['guesses'] ?? [];
    $difficulty = $_SESSION['difficulty'] ?? 'Medium';
    $timeTaken = time() - ($_SESSION['start_time'] ?? time());

    // Confetti on win
    if ($won) echo "<script>confetti({particleCount: 200, spread: 70, origin: { y: 0.6 }});</script>";
?>

    <div class="max-w-lg mx-auto text-center">
        <div class="glass rounded-3xl p-12 <?= $won ? 'border-emerald-400' : 'border-red-400' ?> border-4">
            <?php if ($won): ?>
                <div class="text-8xl mb-6">🎉</div>
                <h1 class="text-5xl font-bold text-emerald-400 mb-4">Brilliant!</h1>
                <p class="text-2xl mb-8">You guessed it in <strong><?= count($guesses) ?></strong> attempts</p>
            <?php else: ?>
                <div class="text-8xl mb-6">😢</div>
                <h1 class="text-5xl font-bold text-red-400 mb-4">Game Over</h1>
                <p class="text-xl mb-8">The number was <strong class="text-3xl"><?= $secret ?></strong></p>
            <?php endif; ?>

            <div class="bg-black/30 rounded-2xl p-6 mb-8 text-left">
                <p><strong>Difficulty:</strong> <?= $difficulty ?></p>
                <p><strong>Time taken:</strong> <?= floor($timeTaken/60) ?>m <?= $timeTaken%60 ?>s</p>
                <p><strong>Final Level:</strong> <?= $user['level'] ?> (<?= $user['experience'] ?> XP)</p>
            </div>

            <div class="flex gap-4">
                <a href="?action=select" 
                   class="flex-1 bg-gradient-to-r from-violet-500 to-fuchsia-500 py-5 rounded-3xl font-semibold text-lg">
                    Play Again
                </a>
                <a href="?action=menu" 
                   class="flex-1 bg-white/10 hover:bg-white/20 py-5 rounded-3xl font-semibold text-lg transition">
                    Main Menu
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php 
// Rankings
if ($action === 'rankings'):
    $rankings = $game->getRankings();
?>
    <h1 class="text-4xl font-bold text-center mb-10">🏆 Global Rankings</h1>
    
    <div class="glass rounded-3xl overflow-hidden">
        <table class="w-full">
            <thead class="bg-white/10">
                <tr>
                    <th class="px-8 py-5 text-left">Rank</th>
                    <th class="px-8 py-5 text-left">Player</th>
                    <th class="px-8 py-5 text-center">Level</th>
                    <th class="px-8 py-5 text-center">XP</th>
                    <th class="px-8 py-5 text-center">Wins</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                <?php foreach ($rankings as $i => $player): ?>
                <tr class="hover:bg-white/5 transition">
                    <td class="px-8 py-6 font-bold text-violet-400">#<?= $i + 1 ?></td>
                    <td class="px-8 py-6"><?= htmlspecialchars($player['username']) ?></td>
                    <td class="px-8 py-6 text-center text-2xl">⭐ <?= $player['level'] ?></td>
                    <td class="px-8 py-6 text-center"><?= $player['experience'] ?></td>
                    <td class="px-8 py-6 text-center"><?= $player['games_won'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="text-center mt-8">
        <a href="?action=menu" class="text-purple-400 hover:text-white">← Back to Menu</a>
    </div>
<?php endif; ?>

</div>
<?php endif; ?>

<script>
// Simple dark/light toggle (you can expand this)
function toggleTheme() {
    document.documentElement.classList.toggle('light');
    alert("Theme toggle coming soon! 🌗");
}

// Sound effects (optional - you can add real audio files)
function playSound(type) {
    // You can add <audio> elements and play them here
}
</script>

</body>
</html>