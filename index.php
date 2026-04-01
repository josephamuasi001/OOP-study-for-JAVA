<?php
session_start();

class NumberGuessingGame {
    private $games = [
        'Easy'    => ['min' => 1,   'max' => 10,   'attempts' => 6,  'xp_base' => 80],
        'Medium'  => ['min' => 1,   'max' => 100,  'attempts' => 8,  'xp_base' => 150],
        'Hard'    => ['min' => 1,   'max' => 1000, 'attempts' => 10, 'xp_base' => 250],
        'Expert'  => ['min' => 1,   'max' => 5000, 'attempts' => 12, 'xp_base' => 400]
    ];

    private $usersFile = 'users.json';
    private $historyFile = 'game_history.json';

    public function getDifficulties() { return array_keys($this->games); }

    public function getGameConfig($difficulty) {
        return $this->games[$difficulty] ?? null;
    }

    public function generateNumber($min, $max) {
        return random_int($min, $max);
    }

    private function loadUsers() {
        return file_exists($this->usersFile) ? json_decode(file_get_contents($this->usersFile), true) ?? [] : [];
    }

    private function saveUsers($users) {
        file_put_contents($this->usersFile, json_encode($users, JSON_PRETTY_PRINT));
    }

    private function loadHistory() {
        return file_exists($this->historyFile) ? json_decode(file_get_contents($this->historyFile), true) ?? [] : [];
    }

    private function saveHistory($history) {
        file_put_contents($this->historyFile, json_encode($history, JSON_PRETTY_PRINT));
    }

    public function registerUser($username, $password) {
        $users = $this->loadUsers();
        if (isset($users[$username])) return false;

        $users[$username] = [
            'password'     => password_hash($password, PASSWORD_DEFAULT),
            'level'        => 1,
            'experience'   => 0,
            'games_won'    => 0,
            'games_played' => 0,
            'avatar'       => '🎯',
            'achievements' => [],
            'win_streak'   => 0,
            'join_date'    => date('Y-m-d'),
            'theme'        => 'dark'
        ];
        $this->saveUsers($users);
        return true;
    }

    public function loginUser($username, $password) {
        $users = $this->loadUsers();
        return isset($users[$username]) && password_verify($password, $users[$username]['password']);
    }

    public function getUser($username) {
        return $this->loadUsers()[$username] ?? null;
    }

    public function updateUserStats($username, $won, $difficulty, $attemptsUsed, $timeTaken) {
        $users = $this->loadUsers();
        if (!isset($users[$username])) return;

        $users[$username]['games_played']++;
        $config = $this->games[$difficulty] ?? $this->games['Medium'];

        $xpGain = $won ? max(30, $config['xp_base'] - ($attemptsUsed - 1) * 15) : 10;

        if ($won) {
            $users[$username]['games_won']++;
            $users[$username]['win_streak']++;
            if ($timeTaken < 30) $xpGain += 50;
            elseif ($timeTaken < 60) $xpGain += 25;
        } else {
            $users[$username]['win_streak'] = 0;
        }

        $users[$username]['experience'] += $xpGain;
        $users[$username]['level'] = min(999, floor($users[$username]['experience'] / 60) + 1);

        // Achievements
        if ($won && $difficulty === 'Hard' && $attemptsUsed <= 3 && !in_array('Speed Demon', $users[$username]['achievements'])) {
            $users[$username]['achievements'][] = 'Speed Demon';
        }
        if ($users[$username]['win_streak'] >= 5 && !in_array('Hot Streak', $users[$username]['achievements'])) {
            $users[$username]['achievements'][] = 'Hot Streak';
        }
        if ($users[$username]['games_won'] >= 50 && !in_array('Master Guesser', $users[$username]['achievements'])) {
            $users[$username]['achievements'][] = 'Master Guesser';
        }

        $this->saveUsers($users);

        // Game History (last 50 games)
        $history = $this->loadHistory();
        $history[] = [
            'username' => $username,
            'difficulty' => $difficulty,
            'won' => $won,
            'attempts' => $attemptsUsed,
            'time' => $timeTaken,
            'date' => date('Y-m-d H:i')
        ];
        if (count($history) > 50) array_shift($history);
        $this->saveHistory($history);
    }

    public function getRankings() {
        $users = $this->loadUsers();
        $list = [];
        foreach ($users as $user => $data) {
            $list[] = array_merge(['username' => $user], $data);
        }
        usort($list, fn($a, $b) => $b['level'] <=> $a['level'] ?: $b['experience'] <=> $a['experience']);
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'login') {
        $user = trim($_POST['username'] ?? '');
        $pass = $_POST['password'] ?? '';
        if ($game->loginUser($user, $pass)) {
            $_SESSION['username'] = $user;
            header('Location: ?action=menu');
            exit;
        } else {
            $error = "Invalid credentials!";
        }
    }

    if ($action === 'register') {
        $user = trim($_POST['username'] ?? '');
        $pass = $_POST['password'] ?? '';
        if (strlen($user) < 3 || strlen($pass) < 4) {
            $error = "Username or password too short!";
        } elseif ($game->registerUser($user, $pass)) {
            $_SESSION['username'] = $user;
            header('Location: ?action=menu');
            exit;
        } else {
            $error = "Username already taken!";
        }
    }

    // Theme change
    if (isset($_POST['theme']) && $username) {
        $users = $game->loadUsers(); // wait, better to update directly
        $users = $game->loadUsers();
        if (isset($users[$username])) {
            $users[$username]['theme'] = $_POST['theme'];
            $game->saveUsers($users);
        }
    }
}

if ($action === 'logout') {
    session_destroy();
    header('Location: ?action=login');
    exit;
}

$user = $username ? $game->getUser($username) : null;
$currentTheme = $user['theme'] ?? 'dark';
$themes = ['dark', 'neon', 'ocean'];
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
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap');
        
        body { font-family: 'Inter', system-ui, sans-serif; }
        .title-font { font-family: 'Space Grotesk', sans-serif; }

        .glass { background: rgba(255,255,255,0.08); backdrop-filter: blur(20px); }
        
        /* Theme Styles */
        body.dark  { --bg: linear-gradient(to bottom right, #1e1b4b, #312e81, #4338ca); }
        body.neon  { --bg: linear-gradient(to bottom right, #0a0a0a, #1a0033, #330033); }
        body.ocean { --bg: linear-gradient(to bottom right, #0c4a6e, #164e63, #0f766e); }

        body { background: var(--bg); }
        
        .neon-glow { text-shadow: 0 0 15px #a855f7, 0 0 30px #c026d3; }
    </style>
</head>
<body class="<?= $currentTheme ?> min-h-screen text-white">

<?php if (in_array($action, ['login', 'register'])): ?>
<div class="min-h-screen flex items-center justify-center p-6">
    <div class="glass border border-white/20 rounded-3xl shadow-2xl max-w-md w-full p-10">
        <div class="text-center mb-10">
            <div class="text-7xl mb-4">🎯</div>
            <h1 class="title-font text-5xl font-bold tracking-tighter neon-glow">NumGuess Pro</h1>
            <p class="text-purple-300 mt-2">Master the art of guessing</p>
        </div>

        <?php if ($action === 'login'): ?>
        <form method="POST" class="space-y-6">
            <input type="text" name="username" required placeholder="Username" 
                   class="w-full bg-white/10 border border-white/30 rounded-2xl px-6 py-4 focus:border-violet-400 outline-none">
            <input type="password" name="password" required placeholder="Password" 
                   class="w-full bg-white/10 border border-white/30 rounded-2xl px-6 py-4 focus:border-violet-400 outline-none">
            <button type="submit" class="w-full bg-gradient-to-r from-violet-600 to-fuchsia-600 py-4 rounded-2xl font-semibold text-lg hover:scale-105 transition">
                Login
            </button>
            <?php if (isset($error)) echo "<p class='text-red-400 text-center'>$error</p>"; ?>
        </form>
        <p class="text-center mt-6">No account? <a href="?action=register" class="text-violet-400 hover:underline">Register</a></p>
        <?php endif; ?>

        <?php if ($action === 'register'): ?>
        <form method="POST" class="space-y-6">
            <input type="text" name="username" required placeholder="Choose username" 
                   class="w-full bg-white/10 border border-white/30 rounded-2xl px-6 py-4 focus:border-violet-400 outline-none">
            <input type="password" name="password" required placeholder="Password (min 4 chars)" 
                   class="w-full bg-white/10 border border-white/30 rounded-2xl px-6 py-4 focus:border-violet-400 outline-none">
            <button type="submit" class="w-full bg-gradient-to-r from-violet-600 to-fuchsia-600 py-4 rounded-2xl font-semibold text-lg hover:scale-105 transition">
                Create Account
            </button>
            <?php if (isset($error)) echo "<p class='text-red-400 text-center'>$error</p>"; ?>
        </form>
        <p class="text-center mt-6">Already have an account? <a href="?action=login" class="text-violet-400 hover:underline">Login</a></p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($username && in_array($action, ['menu','select','game','results','rankings','profile'])): ?>

<nav class="fixed top-0 w-full border-b border-white/10 bg-black/40 backdrop-blur-xl z-50">
    <div class="max-w-6xl mx-auto px-6 py-5 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <span class="text-4xl">🎯</span>
            <div class="title-font text-2xl font-bold tracking-tight">NumGuess</div>
        </div>
        <div class="flex items-center gap-6 text-sm">
            <a href="?action=menu" class="hover:text-violet-300 transition">Home</a>
            <a href="?action=select" class="hover:text-violet-300 transition">Play</a>
            <a href="?action=rankings" class="hover:text-violet-300 transition">Rankings</a>
            <a href="?action=profile" class="hover:text-violet-300 transition">Profile</a>
            <button onclick="changeTheme()" class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-2xl text-xs font-medium transition">THEME</button>
            <a href="?action=logout" class="text-red-400 hover:text-red-500">Logout</a>
        </div>
    </div>
</nav>

<div class="pt-24 pb-12 px-6 max-w-5xl mx-auto">

<?php 
// MENU
if ($action === 'menu'):
    $history = array_slice(array_reverse(iterator_to_array($game->getUserHistory($username))), 0, 5);
?>
    <div class="text-center mb-16">
        <div class="inline-flex items-center gap-6 bg-white/10 backdrop-blur-md px-10 py-6 rounded-3xl">
            <span class="text-6xl"><?= $user['avatar'] ?></span>
            <div>
                <h1 class="text-5xl font-bold"><?= htmlspecialchars($username) ?></h1>
                <p class="text-3xl text-violet-400">Level <?= $user['level'] ?> • <?= $user['experience'] ?> XP</p>
                <p class="text-emerald-400">Current Streak: <?= $user['win_streak'] ?> 🔥</p>
            </div>
        </div>
    </div>

    <div class="grid md:grid-cols-3 gap-6">
        <div onclick="location.href='?action=select'" class="glass border border-white/20 rounded-3xl p-10 text-center cursor-pointer hover:border-violet-400 transition-all hover:-translate-y-2">
            <div class="text-7xl mb-6">🎮</div>
            <h3 class="text-3xl font-semibold">Play Game</h3>
        </div>
        <div onclick="location.href='?action=rankings'" class="glass border border-white/20 rounded-3xl p-10 text-center cursor-pointer hover:border-violet-400 transition-all hover:-translate-y-2">
            <div class="text-7xl mb-6">🏆</div>
            <h3 class="text-3xl font-semibold">Rankings</h3>
        </div>
        <div onclick="location.href='?action=profile'" class="glass border border-white/20 rounded-3xl p-10 text-center cursor-pointer hover:border-violet-400 transition-all hover:-translate-y-2">
            <div class="text-7xl mb-6">👤</div>
            <h3 class="text-3xl font-semibold">Profile</h3>
        </div>
    </div>

    <?php if ($history): ?>
    <div class="mt-16">
        <h2 class="text-2xl font-semibold mb-6">Recent Games</h2>
        <?php foreach ($history as $g): ?>
        <div class="glass border border-white/10 rounded-2xl p-5 mb-4 flex justify-between items-center">
            <div>
                <span class="font-medium"><?= $g['difficulty'] ?></span>
                <span class="ml-4 text-xs text-gray-400"><?= $g['date'] ?></span>
            </div>
            <div class="<?= $g['won'] ? 'text-emerald-400' : 'text-red-400' ?>">
                <?= $g['won'] ? 'WIN' : 'LOSE' ?> • <?= $g['attempts'] ?> attempts
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
<?php endif; ?>

<?php 
// SELECT DIFFICULTY
if ($action === 'select'): ?>
    <h1 class="text-5xl font-bold text-center mb-12 title-font">Choose Your Challenge</h1>
    <div class="max-w-2xl mx-auto grid gap-6">
        <?php foreach ($game->getDifficulties() as $diff):
            $cfg = $game->getGameConfig($diff);
        ?>
        <form method="POST" action="?action=start">
            <button type="submit" name="difficulty" value="<?= $diff ?>"
                    class="w-full glass border border-white/20 hover:border-violet-400 p-8 rounded-3xl text-left transition-all group flex justify-between items-center">
                <div>
                    <h3 class="text-4xl font-bold group-hover:text-violet-300"><?= $diff ?></h3>
                    <p class="text-lg text-purple-300"><?= $cfg['min'] ?>–<?= $cfg['max'] ?> • <?= $cfg['attempts'] ?> attempts</p>
                </div>
                <span class="text-5xl opacity-75"><?= $diff[0] === 'E' ? '🌱' : ($diff[0] === 'M' ? '🔥' : ($diff[0] === 'H' ? '☠️' : '⚡')) ?></span>
            </button>
        </form>
        <?php endforeach; ?>
    </div>
    <div class="text-center mt-10">
        <a href="?action=menu" class="text-purple-400 hover:text-white">← Back</a>
    </div>
<?php endif; ?>

<?php 
// START GAME
if ($action === 'start' && isset($_POST['difficulty'])) {
    $diff = $_POST['difficulty'];
    $cfg = $game->getGameConfig($diff);
    if ($cfg) {
        $_SESSION['secret'] = $game->generateNumber($cfg['min'], $cfg['max']);
        $_SESSION['difficulty'] = $diff;
        $_SESSION['min'] = $cfg['min'];
        $_SESSION['max'] = $cfg['max'];
        $_SESSION['attempts_left'] = $cfg['attempts'];
        $_SESSION['guesses'] = [];
        $_SESSION['start_time'] = time();
        $_SESSION['hints_used'] = 0;
        header("Location: ?action=game");
        exit;
    }
}
?>

<?php 
// GAME SCREEN
if ($action === 'game' && isset($_SESSION['secret'])):
    $time_elapsed = time() - ($_SESSION['start_time'] ?? time());
    $guesses = $_SESSION['guesses'] ?? [];
    $attempts_left = $_SESSION['attempts_left'];
?>
    <div class="max-w-xl mx-auto">
        <div class="glass rounded-3xl p-10">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <div class="text-sm text-purple-400">DIFFICULTY</div>
                    <div class="text-3xl font-bold"><?= $_SESSION['difficulty'] ?></div>
                </div>
                <div class="text-right">
                    <div class="text-sm text-purple-400">TIME</div>
                    <div id="timer" class="font-mono text-4xl font-bold"><?= gmdate("i:s", $time_elapsed) ?></div>
                </div>
            </div>

            <div class="text-center mb-10">
                <div class="inline-flex items-center gap-8 bg-black/30 rounded-3xl px-10 py-6">
                    <div class="text-center">
                        <div class="text-6xl font-bold text-emerald-400"><?= $attempts_left ?></div>
                        <div class="text-xs tracking-widest">ATTEMPTS LEFT</div>
                    </div>
                </div>
            </div>

            <?php if (!empty($guesses)): ?>
            <div class="mb-8">
                <p class="text-purple-400 text-sm mb-3">Your guesses</p>
                <div class="flex flex-wrap gap-3 justify-center">
                    <?php foreach ($guesses as $g): ?>
                    <div class="bg-white/10 px-6 py-3 rounded-2xl"><?= $g ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" action="?action=guess" class="space-y-6">
                <input type="number" name="guess" min="<?= $_SESSION['min'] ?>" max="<?= $_SESSION['max'] ?>" 
                       class="w-full text-center text-5xl py-8 bg-white/5 border border-white/30 rounded-3xl focus:border-violet-400 outline-none" 
                       placeholder="Enter guess" autofocus required>
                <div class="flex gap-4">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-violet-600 to-purple-600 py-6 rounded-3xl font-semibold text-xl hover:scale-105 transition">
                        GUESS
                    </button>
                    <button type="button" onclick="useHint()" 
                            class="px-8 bg-white/10 hover:bg-white/20 rounded-3xl font-medium transition">
                        Hint <span class="text-xs">(−1 attempt)</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    let seconds = <?= $time_elapsed ?>;
    setInterval(() => {
        seconds++;
        const min = Math.floor(seconds / 60);
        const sec = seconds % 60;
        document.getElementById('timer').textContent = `${min.toString().padStart(2,'0')}:${sec.toString().padStart(2,'0')}`;
    }, 1000);

    function useHint() {
        if (confirm("Use a hint? It will cost 1 attempt.")) {
            fetch('?action=hint', {method: 'POST'})
                .then(() => location.reload());
        }
    }
    </script>
<?php endif; ?>

<?php 
// PROCESS GUESS + HINT
if ($action === 'guess' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['secret'])) {
    $guess = (int)($_POST['guess'] ?? 0);
    $_SESSION['guesses'][] = $guess;
    $_SESSION['attempts_left']--;

    if ($guess === $_SESSION['secret']) {
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

if ($action === 'hint' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['secret'])) {
    if ($_SESSION['attempts_left'] > 1) {
        $_SESSION['attempts_left']--;
        $_SESSION['hints_used'] = ($_SESSION['hints_used'] ?? 0) + 1;
        // You could store hint info if you want more advanced hints
    }
    header('Location: ?action=game');
    exit;
}
?>

<?php 
// RESULTS
if ($action === 'results'):
    $won = ($_GET['result'] ?? '') === 'win';
    if ($won) echo "<script>confetti({particleCount:250, spread:80, origin:{y:0.6}});</script>";
?>
    <div class="max-w-lg mx-auto text-center">
        <div class="glass rounded-3xl p-12 <?= $won ? 'border-4 border-emerald-400' : 'border-4 border-red-500' ?>">
            <?php if ($won): ?>
                <h1 class="text-7xl mb-6">🎉</h1>
                <h2 class="text-5xl font-bold text-emerald-400 mb-4">Amazing!</h2>
                <p class="text-2xl">You won in <strong><?= count($_SESSION['guesses'] ?? []) ?></strong> attempts</p>
            <?php else: ?>
                <h1 class="text-7xl mb-6">💔</h1>
                <h2 class="text-5xl font-bold text-red-400">Game Over</h2>
                <p class="text-3xl mt-6">The number was <strong><?= $_SESSION['secret'] ?? '?' ?></strong></p>
            <?php endif; ?>

            <div class="my-10 bg-black/40 rounded-2xl p-6 text-left space-y-2">
                <p><strong>Difficulty:</strong> <?= $_SESSION['difficulty'] ?? '' ?></p>
                <p><strong>Time:</strong> <?= floor((time() - ($_SESSION['start_time'] ?? time())) / 60) ?>m <?= (time() - ($_SESSION['start_time'] ?? time())) % 60 ?>s</p>
            </div>

            <div class="flex gap-4">
                <a href="?action=select" class="flex-1 py-6 bg-gradient-to-r from-violet-600 to-fuchsia-600 rounded-3xl font-semibold text-xl">Play Again</a>
                <a href="?action=menu" class="flex-1 py-6 bg-white/10 hover:bg-white/20 rounded-3xl font-semibold text-xl transition">Menu</a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php 
// RANKINGS
if ($action === 'rankings'):
    $rankings = $game->getRankings();
?>
    <h1 class="text-5xl font-bold text-center mb-12 title-font">🏆 Leaderboard</h1>
    <div class="glass rounded-3xl overflow-hidden">
        <table class="w-full">
            <thead class="bg-white/10">
                <tr>
                    <th class="py-6 px-8 text-left">Rank</th>
                    <th class="py-6 px-8 text-left">Player</th>
                    <th class="py-6 px-8 text-center">Level</th>
                    <th class="py-6 px-8 text-center">XP</th>
                    <th class="py-6 px-8 text-center">Wins</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                <?php foreach ($rankings as $i => $p): ?>
                <tr class="hover:bg-white/5">
                    <td class="py-6 px-8 font-bold text-violet-400">#<?= $i+1 ?></td>
                    <td class="py-6 px-8"><?= htmlspecialchars($p['username']) ?></td>
                    <td class="py-6 px-8 text-center text-2xl">⭐ <?= $p['level'] ?></td>
                    <td class="py-6 px-8 text-center"><?= $p['experience'] ?></td>
                    <td class="py-6 px-8 text-center"><?= $p['games_won'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php 
// PROFILE
if ($action === 'profile'): 
?>
    <div class="max-w-2xl mx-auto glass rounded-3xl p-10">
        <div class="flex items-center gap-8 mb-12">
            <div class="text-8xl"><?= $user['avatar'] ?></div>
            <div>
                <h1 class="text-4xl font-bold"><?= htmlspecialchars($username) ?></h1>
                <p class="text-2xl text-violet-400">Level <?= $user['level'] ?></p>
            </div>
        </div>

        <form method="POST" class="mb-10">
            <label class="block text-sm mb-3">Choose Theme</label>
            <div class="flex gap-3">
                <?php foreach (['dark','neon','ocean'] as $t): ?>
                <button type="submit" name="theme" value="<?= $t ?>" 
                        class="flex-1 py-4 rounded-2xl <?= $currentTheme === $t ? 'bg-violet-600' : 'bg-white/10' ?>">
                    <?= ucfirst($t) ?>
                </button>
                <?php endforeach; ?>
            </div>
        </form>

        <h3 class="text-xl font-semibold mb-4">Achievements</h3>
        <?php if (empty($user['achievements'])): ?>
            <p class="text-purple-400">Keep playing to unlock achievements!</p>
        <?php else: ?>
            <div class="flex flex-wrap gap-3">
                <?php foreach ($user['achievements'] as $ach): ?>
                    <div class="bg-emerald-500/20 text-emerald-300 px-6 py-3 rounded-2xl"><?= $ach ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

</div>
<?php endif; ?>

<script>
function changeTheme() {
    const themes = ['dark', 'neon', 'ocean'];
    let current = '<?= $currentTheme ?>';
    let next = themes[(themes.indexOf(current) + 1) % 3];
    fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'theme=' + next })
        .then(() => location.reload());
}
</script>

</body>
</html>