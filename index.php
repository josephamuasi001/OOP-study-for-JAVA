<?php
session_start();

class NumberGuessingGame {
    private $games = [
        'Easy'   => ['min' => 1,  'max' => 10,   'attempts' => 5,  'xp_base' => 100, 'xp_mult' => 1.0],
        'Medium' => ['min' => 1,  'max' => 100,  'attempts' => 7,  'xp_base' => 200, 'xp_mult' => 1.5],
        'Hard'   => ['min' => 1,  'max' => 1000, 'attempts' => 10, 'xp_base' => 300, 'xp_mult' => 2.5],
    ];

    private $achievements = [
        'first_win'        => ['name' => 'First Blood',      'icon' => '🎯', 'desc' => 'Win your first game'],
        'win_streak_3'     => ['name' => 'On Fire',          'icon' => '🔥', 'desc' => '3 wins in a row'],
        'win_streak_5'     => ['name' => 'Unstoppable',      'icon' => '⚡', 'desc' => '5 wins in a row'],
        'win_streak_10'    => ['name' => 'Legendary',        'icon' => '👑', 'desc' => '10 wins in a row'],
        'one_shot'         => ['name' => 'Lucky Shot',       'icon' => '🍀', 'desc' => 'Guess correctly on first try'],
        'hard_win'         => ['name' => 'Hardboiled',       'icon' => '💪', 'desc' => 'Win on Hard difficulty'],
        'speed_demon'      => ['name' => 'Speed Demon',      'icon' => '⚡', 'desc' => 'Win in under 10 seconds'],
        'centurion'        => ['name' => 'Centurion',        'icon' => '🏅', 'desc' => 'Play 100 games'],
        'level_10'         => ['name' => 'Veteran',          'icon' => '🌟', 'desc' => 'Reach level 10'],
        'level_50'         => ['name' => 'Elite',            'icon' => '💎', 'desc' => 'Reach level 50'],
        'daily_champion'   => ['name' => 'Daily Grinder',    'icon' => '📅', 'desc' => 'Complete a daily challenge'],
        'hint_hoarder'     => ['name' => 'Thrifty',          'icon' => '💰', 'desc' => 'Use 0 hints in 10 games'],
        'power_player'     => ['name' => 'Power Player',     'icon' => '🚀', 'desc' => 'Use a power-up to win'],
        'win_50'           => ['name' => 'Sharpshooter',     'icon' => '🎖️', 'desc' => 'Win 50 games total'],
        'efficiency'       => ['name' => 'Efficient',        'icon' => '🧠', 'desc' => 'Win using 50% or fewer attempts'],
    ];

    private $themes = [
        'default'  => ['name' => 'Neon Noir',      'unlock_level' => 1,  'icon' => '🌃'],
        'ocean'    => ['name' => 'Deep Ocean',     'unlock_level' => 5,  'icon' => '🌊'],
        'forest'   => ['name' => 'Ancient Forest', 'unlock_level' => 10, 'icon' => '🌲'],
        'volcano'  => ['name' => 'Volcano',        'unlock_level' => 20, 'icon' => '🌋'],
        'galaxy'   => ['name' => 'Galaxy',         'unlock_level' => 35, 'icon' => '🌌'],
        'gold'     => ['name' => 'Gold Rush',      'unlock_level' => 50, 'icon' => '✨'],
    ];

    private $usersFile = '/tmp/ng_users.json';
    private $dailyFile = '/tmp/ng_daily.json';

    public function getDifficulties()   { return $this->games; }
    public function getGameConfig($d)   { return $this->games[$d] ?? null; }
    public function getAchievements()   { return $this->achievements; }
    public function getThemes()         { return $this->themes; }
    public function generateNumber($mn, $mx) { return rand($mn, $mx); }

    public function loadUsers() {
        if (file_exists($this->usersFile))
            return json_decode(file_get_contents($this->usersFile), true) ?? [];
        return [];
    }

    public function saveUsers($u) {
        file_put_contents($this->usersFile, json_encode($u, JSON_PRETTY_PRINT));
    }

    public function registerUser($username, $password) {
        $users = $this->loadUsers();
        if (isset($users[$username])) return false;
        $users[$username] = [
            'password'        => password_hash($password, PASSWORD_DEFAULT),
            'level'           => 1,
            'experience'      => 0,
            'games_won'       => 0,
            'games_played'    => 0,
            'streak'          => 0,
            'best_streak'     => 0,
            'hints_used'      => 0,
            'hint_xp'         => 200,
            'achievements'    => [],
            'theme'           => 'default',
            'dark_mode'       => false,
            'powerups'        => ['range_narrow' => 2, 'reveal_digit' => 1],
            'game_history'    => [],
            'no_hint_games'   => 0,
            'created_at'      => time(),
            'last_daily'      => null,
            'daily_streak'    => 0,
            'diff_stats'      => ['Easy' => ['w'=>0,'p'=>0], 'Medium' => ['w'=>0,'p'=>0], 'Hard' => ['w'=>0,'p'=>0]],
        ];
        $this->saveUsers($users);
        return true;
    }

    public function loginUser($u, $p) {
        $users = $this->loadUsers();
        if (!isset($users[$u]) || !password_verify($p, $users[$u]['password'])) return false;
        return true;
    }

    public function getUser($u) {
        $users = $this->loadUsers();
        return $users[$u] ?? null;
    }

    public function updateUser($username, $data) {
        $users = $this->loadUsers();
        if (!isset($users[$username])) return;
        $users[$username] = array_merge($users[$username], $data);
        $this->saveUsers($users);
    }

    public function getDailyChallenge() {
        $today = date('Y-m-d');
        if (file_exists($this->dailyFile)) {
            $d = json_decode(file_get_contents($this->dailyFile), true);
            if ($d && ($d['date'] ?? '') === $today) return $d;
        }
        $seed = crc32($today);
        srand($seed);
        $min = 1; $max = 500;
        $secret = rand($min, $max);
        srand(); // reset
        $daily = ['date' => $today, 'min' => $min, 'max' => $max, 'secret' => $secret, 'attempts' => 8];
        file_put_contents($this->dailyFile, json_encode($daily));
        return $daily;
    }

    public function updateUserStats($username, $won, $difficulty, $attemptsUsed, $timeSecs, $hintsUsed, $usedPowerup) {
        $users = $this->loadUsers();
        if (!isset($users[$username])) return [];
        $u = &$users[$username];

        $u['games_played']++;
        $u['diff_stats'][$difficulty]['p']++;

        $newAchievements = [];

        if ($won) {
            $u['games_won']++;
            $u['streak']++;
            $u['diff_stats'][$difficulty]['w']++;
            if ($u['streak'] > $u['best_streak']) $u['best_streak'] = $u['streak'];

            $cfg = $this->games[$difficulty];
            $baseXP = $cfg['xp_base'];
            $mult = $cfg['xp_mult'];
            $streakBonus = min(3.0, 1.0 + ($u['streak'] - 1) * 0.2);
            $attemptBonus = max(0.5, 1.0 - (($attemptsUsed - 1) / $cfg['attempts']) * 0.5);
            $xp = (int)round(max(50, $baseXP * $mult * $streakBonus * $attemptBonus));

            if ($hintsUsed === 0) $u['no_hint_games']++;
            else { $u['no_hint_games'] = 0; $u['hints_used'] += $hintsUsed; }

            if ($usedPowerup && !in_array('power_player', $u['achievements'])) {
                $u['achievements'][] = 'power_player';
                $newAchievements[] = 'power_player';
            }

            // Achievements
            $checks = [
                'first_win'      => $u['games_won'] === 1,
                'win_streak_3'   => $u['streak'] >= 3,
                'win_streak_5'   => $u['streak'] >= 5,
                'win_streak_10'  => $u['streak'] >= 10,
                'one_shot'       => $attemptsUsed === 1,
                'hard_win'       => $difficulty === 'Hard',
                'speed_demon'    => $timeSecs < 10,
                'win_50'         => $u['games_won'] >= 50,
                'efficiency'     => $attemptsUsed <= (int)ceil($cfg['attempts'] / 2),
            ];
            foreach ($checks as $key => $cond) {
                if ($cond && !in_array($key, $u['achievements'])) {
                    $u['achievements'][] = $key;
                    $newAchievements[] = $key;
                    $xp += 100;
                }
            }

            $u['experience'] += $xp;
        } else {
            $u['streak'] = 0;
            $u['no_hint_games'] = 0;
            $xp = 10;
            $u['experience'] += $xp;
        }

        // Level up
        $u['level'] = min(1000, (int)floor($u['experience'] / 50) + 1);

        // Level achievements
        foreach (['level_10' => 10, 'level_50' => 50] as $key => $lvl) {
            if ($u['level'] >= $lvl && !in_array($key, $u['achievements'])) {
                $u['achievements'][] = $key;
                $newAchievements[] = $key;
            }
        }
        if ($u['games_played'] >= 100 && !in_array('centurion', $u['achievements'])) {
            $u['achievements'][] = 'centurion';
            $newAchievements[] = 'centurion';
        }
        if ($u['no_hint_games'] >= 10 && !in_array('hint_hoarder', $u['achievements'])) {
            $u['achievements'][] = 'hint_hoarder';
            $newAchievements[] = 'hint_hoarder';
        }

        // History
        $u['game_history'][] = [
            'diff' => $difficulty, 'won' => $won, 'attempts' => $attemptsUsed,
            'time' => $timeSecs, 'date' => time()
        ];
        if (count($u['game_history']) > 50) array_shift($u['game_history']);

        // Replenish powerups daily (simple)
        $u['powerups']['range_narrow'] = min(5, ($u['powerups']['range_narrow'] ?? 0) + ($won ? 1 : 0));
        if ($won && $difficulty === 'Hard') $u['powerups']['reveal_digit'] = min(3, ($u['powerups']['reveal_digit'] ?? 0) + 1);

        $this->saveUsers($users);
        return ['xp' => $xp ?? 10, 'new_achievements' => $newAchievements];
    }

    public function getRankings() {
        $users = $this->loadUsers();
        $list = [];
        foreach ($users as $uname => $data) {
            $list[] = array_merge($data, ['username' => $uname]);
        }
        usort($list, fn($a,$b) => $b['level'] <=> $a['level'] ?: $b['experience'] <=> $a['experience']);
        return $list;
    }

    public function useHint($username) {
        $users = $this->loadUsers();
        if (!isset($users[$username])) return false;
        $cost = 50;
        if ($users[$username]['hint_xp'] < $cost) return false;
        $users[$username]['hint_xp'] -= $cost;
        $this->saveUsers($users);
        return true;
    }

    public function usePowerup($username, $type) {
        $users = $this->loadUsers();
        if (!isset($users[$username])) return false;
        if (($users[$username]['powerups'][$type] ?? 0) <= 0) return false;
        $users[$username]['powerups'][$type]--;
        $this->saveUsers($users);
        return true;
    }

    public function getWarmthHint($guess, $secret, $range) {
        $diff = abs($guess - $secret);
        $pct  = $diff / $range;
        if ($pct === 0) return ['msg' => '🎯 Exact!',             'cls' => 'exact'];
        if ($pct < 0.02) return ['msg' => '🔥🔥🔥 ON FIRE!',     'cls' => 'blazing'];
        if ($pct < 0.05) return ['msg' => '🔥🔥 Scorching!',     'cls' => 'hot'];
        if ($pct < 0.10) return ['msg' => '🌡️ Very Warm',        'cls' => 'warm'];
        if ($pct < 0.20) return ['msg' => '☀️ Getting Warmer',   'cls' => 'lukewarm'];
        if ($pct < 0.35) return ['msg' => '🌤️ Tepid',            'cls' => 'tepid'];
        if ($pct < 0.55) return ['msg' => '❄️ Cold',             'cls' => 'cold'];
        return             ['msg' => '🧊 Freezing!',              'cls' => 'freezing'];
    }
}

// ── CSS Themes ────────────────────────────────────────────────────────────────
function themeVars($theme, $dark) {
    $themes = [
        'default' => [
            'light' => '--bg:#0d0d1a;--panel:#161629;--acc1:#7c6af7;--acc2:#a78bfa;--acc3:#f472b6;--txt:#e2e8f0;--sub:#94a3b8;--brd:#2d2d50;--card:#1e1e3a',
            'dark'  => '--bg:#0d0d1a;--panel:#161629;--acc1:#7c6af7;--acc2:#a78bfa;--acc3:#f472b6;--txt:#e2e8f0;--sub:#94a3b8;--brd:#2d2d50;--card:#1e1e3a',
        ],
        'ocean' => [
            'light' => '--bg:#e8f4f8;--panel:#fff;--acc1:#0077b6;--acc2:#00b4d8;--acc3:#90e0ef;--txt:#03045e;--sub:#023e8a;--brd:#caf0f8;--card:#f0f8ff',
            'dark'  => '--bg:#03045e;--panel:#023e8a;--acc1:#00b4d8;--acc2:#90e0ef;--acc3:#caf0f8;--txt:#caf0f8;--sub:#90e0ef;--brd:#0077b6;--card:#0077b6',
        ],
        'forest' => [
            'light' => '--bg:#f0fdf4;--panel:#fff;--acc1:#16a34a;--acc2:#4ade80;--acc3:#bbf7d0;--txt:#14532d;--sub:#166534;--brd:#bbf7d0;--card:#f0fdf4',
            'dark'  => '--bg:#052e16;--panel:#14532d;--acc1:#4ade80;--acc2:#86efac;--acc3:#bbf7d0;--txt:#dcfce7;--sub:#bbf7d0;--brd:#166534;--card:#166534',
        ],
        'volcano' => [
            'light' => '--bg:#fff7ed;--panel:#fff;--acc1:#ea580c;--acc2:#fb923c;--acc3:#fed7aa;--txt:#431407;--sub:#7c2d12;--brd:#fed7aa;--card:#fff7ed',
            'dark'  => '--bg:#1c0a00;--panel:#431407;--acc1:#fb923c;--acc2:#fdba74;--acc3:#fed7aa;--txt:#ffedd5;--sub:#fed7aa;--brd:#7c2d12;--card:#7c2d12',
        ],
        'galaxy' => [
            'light' => '--bg:#f5f3ff;--panel:#fff;--acc1:#7c3aed;--acc2:#a78bfa;--acc3:#ddd6fe;--txt:#2e1065;--sub:#4c1d95;--brd:#ddd6fe;--card:#f5f3ff',
            'dark'  => '--bg:#0d0617;--panel:#1e0a3c;--acc1:#a78bfa;--acc2:#c4b5fd;--acc3:#ddd6fe;--txt:#ede9fe;--sub:#c4b5fd;--brd:#4c1d95;--card:#2e1065',
        ],
        'gold' => [
            'light' => '--bg:#fefce8;--panel:#fff;--acc1:#ca8a04;--acc2:#eab308;--acc3:#fef08a;--txt:#422006;--sub:#713f12;--brd:#fef08a;--card:#fefce8',
            'dark'  => '--bg:#1c0f00;--panel:#422006;--acc1:#eab308;--acc2:#facc15;--acc3:#fef08a;--txt:#fefce8;--sub:#fef08a;--brd:#713f12;--card:#713f12',
        ],
    ];
    $t = $themes[$theme] ?? $themes['default'];
    return $dark ? $t['dark'] : $t['light'];
}

function baseCSS($theme='default', $dark=true) {
    $v = themeVars($theme, $dark);
    return "
    <style>
    :root { $v }
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html{scroll-behavior:smooth}
    body{font-family:'Courier Prime',monospace;background:var(--bg);color:var(--txt);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px;position:relative;overflow-x:hidden}
    .noise{position:fixed;inset:0;pointer-events:none;opacity:.035;background-image:url(\"data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E\");z-index:9999}
    .card{background:var(--panel);border:1px solid var(--brd);border-radius:16px;padding:36px;max-width:480px;width:100%;box-shadow:0 24px 64px rgba(0,0,0,.45);animation:rise .5s cubic-bezier(.22,1,.36,1)}
    @keyframes rise{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:none}}
    h1{font-size:2rem;font-weight:700;background:linear-gradient(135deg,var(--acc1),var(--acc2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;text-align:center;letter-spacing:-.03em;margin-bottom:28px}
    h2{font-size:1.3rem;color:var(--acc2);margin-bottom:18px;font-weight:600}
    label{display:block;font-size:.78rem;text-transform:uppercase;letter-spacing:.12em;color:var(--sub);margin-bottom:7px;font-weight:600}
    input,select{width:100%;padding:13px 16px;background:var(--card);border:1.5px solid var(--brd);border-radius:10px;color:var(--txt);font-size:1rem;font-family:inherit;transition:border .2s,box-shadow .2s;outline:none}
    input:focus,select:focus{border-color:var(--acc1);box-shadow:0 0 0 3px color-mix(in srgb,var(--acc1) 20%,transparent)}
    .btn{display:block;width:100%;padding:13px;border:none;border-radius:10px;cursor:pointer;font-size:1rem;font-family:inherit;font-weight:700;letter-spacing:.06em;transition:transform .15s,box-shadow .15s;text-decoration:none;text-align:center}
    .btn-primary{background:linear-gradient(135deg,var(--acc1),var(--acc2));color:#fff;box-shadow:0 4px 20px color-mix(in srgb,var(--acc1) 40%,transparent)}
    .btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 28px color-mix(in srgb,var(--acc1) 50%,transparent)}
    .btn-ghost{background:var(--card);color:var(--txt);border:1.5px solid var(--brd)}
    .btn-ghost:hover{border-color:var(--acc1);color:var(--acc1);transform:translateY(-1px)}
    .btn-sm{padding:8px 16px;font-size:.85rem;width:auto;display:inline-block}
    .btn-danger{background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff}
    .form-group{margin-bottom:20px}
    .alert{padding:13px 16px;border-radius:10px;font-size:.9rem;margin-top:14px;border-left:3px solid}
    .alert-err{background:color-mix(in srgb,#ef4444 12%,transparent);border-color:#ef4444;color:#fca5a5}
    .alert-ok{background:color-mix(in srgb,#22c55e 12%,transparent);border-color:#22c55e;color:#86efac}
    .badge{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:999px;font-size:.78rem;font-weight:600;border:1px solid}
    .badge-acc{background:color-mix(in srgb,var(--acc1) 15%,transparent);border-color:color-mix(in srgb,var(--acc1) 40%,transparent);color:var(--acc2)}
    .divider{height:1px;background:var(--brd);margin:22px 0}
    .link{color:var(--acc2);text-decoration:none;font-weight:600}
    .link:hover{text-decoration:underline}
    .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    .stat-box{background:var(--card);border:1px solid var(--brd);border-radius:12px;padding:18px;text-align:center}
    .stat-val{font-size:1.8rem;font-weight:800;background:linear-gradient(135deg,var(--acc1),var(--acc2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
    .stat-lbl{font-size:.75rem;color:var(--sub);text-transform:uppercase;letter-spacing:.1em;margin-top:4px}
    .flex{display:flex;align-items:center;gap:10px}
    .flex-between{display:flex;align-items:center;justify-content:space-between}
    .mt-1{margin-top:8px}.mt-2{margin-top:16px}.mt-3{margin-top:24px}
    .mb-1{margin-bottom:8px}.mb-2{margin-bottom:16px}
    .text-sm{font-size:.85rem}.text-xs{font-size:.75rem}
    .text-muted{color:var(--sub)}.text-acc{color:var(--acc2)}
    .progress-wrap{background:var(--brd);border-radius:999px;height:8px;overflow:hidden;margin:10px 0}
    .progress-bar{height:100%;border-radius:999px;background:linear-gradient(90deg,var(--acc1),var(--acc2));transition:width .6s cubic-bezier(.22,1,.36,1)}
    .warn-box{background:color-mix(in srgb,#f59e0b 10%,transparent);border:1px solid color-mix(in srgb,#f59e0b 40%,transparent);border-radius:10px;padding:13px;color:#fbbf24;font-size:.9rem}
    .success-box{background:color-mix(in srgb,#22c55e 10%,transparent);border:1px solid color-mix(in srgb,#22c55e 40%,transparent);border-radius:10px;padding:13px;color:#86efac;font-size:.9rem}
    .stacks{display:flex;flex-direction:column;gap:12px}
    @media(max-width:520px){.card{padding:24px}.grid-2{grid-template-columns:1fr}}
    </style>
    <link href='https://fonts.googleapis.com/css2?family=Courier+Prime:wght@400;700&display=swap' rel='stylesheet'>
    <div class='noise'></div>
    ";
}

// ── Helpers ───────────────────────────────────────────────────────────────────
$game = new NumberGuessingGame();
$action = $_GET['action'] ?? 'login';
$username = $_SESSION['username'] ?? null;

function requireAuth() {
    global $username;
    if (!$username) { header('Location: ?action=login'); exit; }
}

function userTheme() {
    global $game, $username;
    if (!$username) return ['default', true];
    $u = $game->getUser($username);
    return [$u['theme'] ?? 'default', $u['dark_mode'] ?? true];
}

function levelProgress($xp) {
    $level = (int)floor($xp / 50) + 1;
    $xpForCurrent = ($level - 1) * 50;
    $xpForNext = $level * 50;
    $pct = round(($xp - $xpForCurrent) / ($xpForNext - $xpForCurrent) * 100);
    return [$level, $pct, $xpForNext - $xp];
}

// ═══════════════════════════════════════════════════════════════════════════════
// LOGIN
// ═══════════════════════════════════════════════════════════════════════════════
if ($action === 'login') {
    $err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $u = trim($_POST['username'] ?? '');
        $p = $_POST['password'] ?? '';
        if ($game->loginUser($u, $p)) {
            $_SESSION['username'] = $u;
            // sync hint_xp with xp
            $usr = $game->getUser($u);
            if (!isset($usr['hint_xp'])) $game->updateUser($u, ['hint_xp' => $usr['experience']]);
            header('Location: ?action=menu'); exit;
        } else { $err = 'Invalid credentials. Try again.'; }
    }
    echo baseCSS();
    ?>
    <div class="card">
        <h1>🎯 NumGenius</h1>
        <p class="text-sm text-muted text-center mb-2">The number guessing game that rewards skill</p>
        <div class="divider"></div>
        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" autofocus required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button class="btn btn-primary" type="submit">Login →</button>
            <?php if ($err): ?><div class="alert alert-err"><?= htmlspecialchars($err) ?></div><?php endif; ?>
        </form>
        <div class="divider"></div>
        <p class="text-sm text-center">No account? <a class="link" href="?action=register">Register here</a></p>
    </div>
    <?php
}

// ═══════════════════════════════════════════════════════════════════════════════
// REGISTER
// ═══════════════════════════════════════════════════════════════════════════════
elseif ($action === 'register') {
    $err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $u = trim($_POST['username'] ?? '');
        $p = $_POST['password'] ?? '';
        if (strlen($u) < 3) $err = 'Username must be at least 3 characters.';
        elseif (strlen($p) < 4) $err = 'Password must be at least 4 characters.';
        elseif ($game->registerUser($u, $p)) {
            $_SESSION['username'] = $u; header('Location: ?action=menu'); exit;
        } else { $err = 'Username already taken.'; }
    }
    echo baseCSS();
    ?>
    <div class="card">
        <h1>✨ Join NumGenius</h1>
        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button class="btn btn-primary" type="submit">Create Account →</button>
            <?php if ($err): ?><div class="alert alert-err"><?= htmlspecialchars($err) ?></div><?php endif; ?>
        </form>
        <div class="divider"></div>
        <p class="text-sm text-center">Have an account? <a class="link" href="?action=login">Login</a></p>
    </div>
    <?php
}

// ═══════════════════════════════════════════════════════════════════════════════
// MENU
// ═══════════════════════════════════════════════════════════════════════════════
elseif ($action === 'menu') {
    requireAuth();
    [$th, $dm] = userTheme();
    echo baseCSS($th, $dm);
    $u = $game->getUser($username);
    [$lvl, $pct, $xpLeft] = levelProgress($u['experience']);
    $daily = $game->getDailyChallenge();
    $dailyDone = $u['last_daily'] === date('Y-m-d');
    $ach = $game->getAchievements();
    $earned = $u['achievements'] ?? [];
    $themes = $game->getThemes();
    ?>
    <div class="card" style="max-width:520px">
        <div class="flex-between mb-2">
            <span class="badge badge-acc">⭐ Lv <?= $lvl ?> &nbsp;<?= htmlspecialchars($username) ?></span>
            <div class="flex">
                <a href="?action=toggle_dark" class="btn btn-ghost btn-sm" title="Toggle theme"><?= $dm ? '☀️' : '🌙' ?></a>
                <a href="?action=logout" class="btn btn-ghost btn-sm">Exit</a>
            </div>
        </div>

        <div style="margin-bottom:20px">
            <div class="flex-between text-xs text-muted mb-1">
                <span><?= $u['experience'] ?> XP</span>
                <span><?= $xpLeft ?> XP to next level</span>
            </div>
            <div class="progress-wrap"><div class="progress-bar" style="width:<?= $pct ?>%"></div></div>
        </div>

        <div class="grid-2 mb-2">
            <div class="stat-box"><div class="stat-val"><?= $u['games_won'] ?></div><div class="stat-lbl">Wins</div></div>
            <div class="stat-box"><div class="stat-val"><?= $u['streak'] ?><?php if($u['streak']>=3) echo ' 🔥'; ?></div><div class="stat-lbl">Streak</div></div>
            <div class="stat-box"><div class="stat-val"><?= $u['games_played'] ?></div><div class="stat-lbl">Played</div></div>
            <div class="stat-box"><div class="stat-val"><?= $u['games_played'] > 0 ? round($u['games_won']/$u['games_played']*100) : 0 ?>%</div><div class="stat-lbl">Win Rate</div></div>
        </div>

        <?php if (!$dailyDone): ?>
        <div class="warn-box mb-2" style="cursor:pointer" onclick="location='?action=daily'">
            📅 <strong>Daily Challenge available!</strong> — Guess 1–500 in 8 tries<br>
            <small class="text-muted">Bonus XP + achievement if completed</small>
        </div>
        <?php else: ?>
        <div class="success-box mb-2">✅ Daily challenge completed! Come back tomorrow.</div>
        <?php endif; ?>

        <div class="stacks">
            <a href="?action=select" class="btn btn-primary">▶ Play Game</a>
            <a href="?action=daily" class="btn btn-ghost<?= $dailyDone ? ' btn-disabled' : '' ?>">📅 Daily Challenge</a>
            <div class="grid-2">
                <a href="?action=rankings" class="btn btn-ghost">🏆 Rankings</a>
                <a href="?action=achievements" class="btn btn-ghost">🎖 Achievements (<?= count($earned) ?>/<?= count($ach) ?>)</a>
            </div>
            <div class="grid-2">
                <a href="?action=stats" class="btn btn-ghost">📊 My Stats</a>
                <a href="?action=themes" class="btn btn-ghost">🎨 Themes</a>
            </div>
        </div>

        <div class="divider"></div>
        <div class="flex text-xs text-muted" style="justify-content:center;gap:20px">
            <span>💰 Hint XP: <strong class="text-acc"><?= $u['hint_xp'] ?? 0 ?></strong></span>
            <span>🔭 Range↓: <strong class="text-acc"><?= $u['powerups']['range_narrow'] ?? 0 ?></strong></span>
            <span>🔮 Reveal: <strong class="text-acc"><?= $u['powerups']['reveal_digit'] ?? 0 ?></strong></span>
        </div>
    </div>
    <?php
}

// ═══════════════════════════════════════════════════════════════════════════════
// TOGGLE DARK MODE
// ═══════════════════════════════════════════════════════════════════════════════
elseif ($action === 'toggle_dark') {
    requireAuth();
    $u = $game->getUser($username);
    $game->updateUser($username, ['dark_mode' => !($u['dark_mode'] ?? true)]);
    header('Location: ?action=menu'); exit;
}

// ═══════════════════════════════════════════════════════════════════════════════
// SELECT DIFFICULTY
// ═══════════════════════════════════════════════════════════════════════════════
elseif ($action === 'select') {
    requireAuth();
    [$th, $dm] = userTheme();
    echo baseCSS($th, $dm);
    $diffs = $game->getDifficulties();
    $u = $game->getUser($username);
    ?>
    <div class="card">
        <a href="?action=menu" class="link text-sm">← Back</a>
        <h1 class="mt-2">Choose Difficulty</h1>
        <form method="POST" action="?action=start">
            <div class="stacks">
                <?php foreach ($diffs as $name => $cfg): 
                    $ds = $u['diff_stats'][$name] ?? ['w'=>0,'p'=>0];
                    $wr = $ds['p'] > 0 ? round($ds['w']/$ds['p']*100) : 0;
                ?>
                <label style="cursor:pointer;display:block">
                    <input type="radio" name="difficulty" value="<?= $name ?>" required style="display:none" onclick="this.form.submit()">
                    <div class="stat-box" style="text-align:left;padding:18px;cursor:pointer" onmouseover="this.style.borderColor='var(--acc1)'" onmouseout="this.style.borderColor='var(--brd)'">
                        <div class="flex-between">
                            <strong><?= $name ?></strong>
                            <span class="badge badge-acc">×<?= $cfg['xp_mult'] ?> XP</span>
                        </div>
                        <div class="text-sm text-muted mt-1">Range: <?= $cfg['min'] ?>–<?= $cfg['max'] ?> · <?= $cfg['attempts'] ?> attempts · Base <?= $cfg['xp_base'] ?> XP</div>
                        <div class="text-xs text-muted mt-1">Your record: <?= $ds['w'] ?>/<?= $ds['p'] ?> wins (<?= $wr ?>%)</div>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </form>
    </div>
    <?php
}

// ═══════════════════════════════════════════════════════════════════════════════
// START GAME
// ═══════════════════════════════════════════════════════════════════════════════
elseif ($action === 'start') {
    requireAuth();
    $difficulty = $_POST['difficulty'] ?? '';
    $config = $game->getGameConfig($difficulty);
    if (!$config) { header('Location: ?action=select'); exit; }

    $_SESSION['secret']          = $game->generateNumber($config['min'], $config['max']);
    $_SESSION['difficulty']      = $difficulty;
    $_SESSION['min']             = $config['min'];
    $_SESSION['max_range']       = $config['max'];
    $_SESSION['cur_min']         = $config['min'];
    $_SESSION['cur_max']         = $config['max'];
    $_SESSION['attempts']        = $config['attempts'];
    $_SESSION['guesses']         = [];
    $_SESSION['current_attempt'] = 0;
    $_SESSION['hints_used']      = 0;
    $_SESSION['powerup_used']    = false;
    $_SESSION['start_time']      = time();
    $_SESSION['is_daily']        = false;
    header('Location: ?action=game');
}

// ═══════════════════════════════════════════════════════════════════════════════
// DAILY CHALLENGE
// ═══════════════════════════════════════════════════════════════════════════════
elseif ($action === 'daily') {
    requireAuth();
    $u = $game->getUser($username);
    if ($u['last_daily'] === date('Y-m-d')) { header('Location: ?action=menu'); exit; }

    $daily = $game->getDailyChallenge();
    $_SESSION['secret']          = $daily['secret'];
    $_SESSION['difficulty']      = 'Medium';
    $_SESSION['min']             = $daily['min'];
    $_SESSION['max_range']       = $daily['max'];
    $_SESSION['cur_min']         = $daily['min'];
    $_SESSION['cur_max']         = $daily['max'];
    $_SESSION['attempts']        = $daily['attempts'];
    $_SESSION['guesses']         = [];
    $_SESSION['current_attempt'] = 0;
    $_SESSION['hints_used']      = 0;
    $_SESSION['powerup_used']    = false;
    $_SESSION['start_time']      = time();
    $_SESSION['is_daily']        = true;
    header('Location: ?action=game');
}

// ═══════════════════════════════════════════════════════════════════════════════
// GAME (USE HINT)
// ═══════════════════════════════════════════════════════════════════════════════
elseif ($action === 'use_hint') {
    requireAuth();
    if ($game->useHint($username)) {
        $_SESSION['hints_used'] = ($_SESSION['hints_used'] ?? 0) + 1;
        $_SESSION['show_hint'] = true;
    }
    header('Location: ?action=game');
}

// ═══════════════════════════════════════════════════════════════════════════════
// GAME (USE POWERUP)
// ═══════════════════════════════════════════════════════════════════════════════
elseif ($action === 'use_powerup') {
    requireAuth();
    $type = $_GET['type'] ?? '';
    $secret = $_SESSION['secret'] ?? 0;
    $curMin = $_SESSION['cur_min'] ?? 1;
    $curMax = $_SESSION['cur_max'] ?? 10;

    if ($type === 'range_narrow' && $game->usePowerup($username, $type)) {
        $range = $curMax - $curMin;
        $quarter = (int)floor($range / 4);
        if ($secret - $quarter > $curMin) $_SESSION['cur_min'] = $secret - $quarter;
        if ($secret + $quarter < $curMax) $_SESSION['cur_max'] = $secret + $quarter;
        $_SESSION['powerup_used'] = true;
        $_SESSION['powerup_msg'] = "🔭 Range narrowed to {$_SESSION['cur_min']}–{$_SESSION['cur_max']}!";
    } elseif ($type === 'reveal_digit' && $game->usePowerup($username, $type)) {
        $digit = $secret % 10;
        $_SESSION['powerup_used'] = true;
        $_SESSION['powerup_msg'] = "🔮 The number ends in: <strong>$digit</strong>";
    }
    header('Location: ?action=game');
}

// ═══════════════════════════════════════════════════════════════════════════════
// GAME LOOP
// ═══════════════════════════════════════════════════════════════════════════════
elseif ($action === 'game') {
    requireAuth();
    [$th, $dm] = userTheme();

    $secret   = $_SESSION['secret'] ?? 0;
    $attempts = $_SESSION['attempts'] ?? 5;
    $current  = $_SESSION['current_attempt'] ?? 0;
    $difficulty = $_SESSION['difficulty'] ?? 'Medium';
    $minR     = $_SESSION['min'] ?? 1;
    $maxR     = $_SESSION['max_range'] ?? 10;
    $curMin   = $_SESSION['cur_min'] ?? $minR;
    $curMax   = $_SESSION['cur_max'] ?? $maxR;
    $guesses  = $_SESSION['guesses'] ?? [];
    $isDaily  = $_SESSION['is_daily'] ?? false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $guess = intval($_POST['guess'] ?? 0);
        $_SESSION['guesses'][] = $guess;
        $_SESSION['current_attempt']++;
        $_SESSION['show_hint'] = false;
        unset($_SESSION['powerup_msg']);

        if ($guess == $secret) { header('Location: ?action=results&result=win'); exit; }
        if ($_SESSION['current_attempt'] >= $attempts) { header('Location: ?action=results&result=lose'); exit; }
        header('Location: ?action=game'); exit;
    }

    if ($current >= $attempts) { header('Location: ?action=results&result=lose'); exit; }

    $u = $game->getUser($username);
    $lastGuess = $guesses ? end($guesses) : null;
    $dirHint = '';
    $warmth  = null;
    if ($lastGuess !== null) {
        $dirHint = $lastGuess < $secret ? 'Too low — go higher ↑' : 'Too high — go lower ↓';
        $warmth = $game->getWarmthHint($lastGuess, $secret, $maxR - $minR);
    }

    $showHintResult = $_SESSION['show_hint'] ?? false;
    $hintResult = '';
    if ($showHintResult && $secret) {
        $mid = (int)(($curMin + $curMax) / 2);
        $hintResult = $secret <= $mid ? "The number is in the LOWER half ({$curMin}–{$mid})" : "The number is in the UPPER half (".($mid+1)."–{$curMax})";
        $_SESSION['show_hint'] = false;
    }
    $powerupMsg = $_SESSION['powerup_msg'] ?? '';
    unset($_SESSION['powerup_msg']);

    $elapsed = time() - ($_SESSION['start_time'] ?? time());
    $left = $attempts - $current;

    $warmthColors = [
        'exact'    => '#22c55e',
        'blazing'  => '#ef4444',
        'hot'      => '#f97316',
        'warm'     => '#f59e0b',
        'lukewarm' => '#eab308',
        'tepid'    => '#84cc16',
        'cold'     => '#38bdf8',
        'freezing' => '#818cf8',
    ];

    echo baseCSS($th, $dm);
    ?>
    <div class="card" style="max-width:520px">
        <div class="flex-between mb-2">
            <span class="badge badge-acc"><?= htmlspecialchars($difficulty) ?><?= $isDaily ? ' · 📅 Daily' : '' ?></span>
            <span id="timer" class="text-sm text-muted">⏱ <span id="ts">0</span>s</span>
        </div>

        <div class="flex-between" style="margin-bottom:18px">
            <div>
                <div class="text-xs text-muted">Attempts Left</div>
                <div style="font-size:1.6rem;font-weight:800;color:<?= $left <= 2 ? '#ef4444' : 'var(--acc2)' ?>"><?= $left ?>/<?= $attempts ?></div>
            </div>
            <div style="text-align:right">
                <div class="text-xs text-muted">Range</div>
                <div style="font-size:1.1rem;font-weight:700;color:var(--acc1)"><?= $curMin ?> – <?= $curMax ?></div>
            </div>
        </div>

        <!-- Attempts bar -->
        <div class="progress-wrap mb-2">
            <div class="progress-bar" style="width:<?= round(($current/$attempts)*100) ?>%;background:linear-gradient(90deg,<?= $left<=2?'#ef4444':'var(--acc1)' ?>,<?= $left<=2?'#dc2626':'var(--acc2)' ?>)"></div>
        </div>

        <?php if ($warmth): ?>
        <div class="warn-box mb-2" style="background:color-mix(in srgb,<?= $warmthColors[$warmth['cls']] ?> 12%,transparent);border-color:<?= $warmthColors[$warmth['cls']] ?>;color:<?= $warmthColors[$warmth['cls']] ?>;font-size:1rem;font-weight:700;text-align:center">
            <?= $warmth['msg'] ?>
            <div class="text-xs mt-1" style="font-weight:400;color:var(--sub)"><?= $dirHint ?></div>
        </div>
        <?php endif; ?>

        <?php if ($hintResult): ?>
        <div class="success-box mb-2">💡 Hint: <?= $hintResult ?></div>
        <?php endif; ?>
        <?php if ($powerupMsg): ?>
        <div class="success-box mb-2"><?= $powerupMsg ?></div>
        <?php endif; ?>

        <?php if (!empty($guesses)): ?>
        <div style="background:var(--card);border:1px solid var(--brd);border-radius:10px;padding:14px;margin-bottom:18px">
            <div class="text-xs text-muted mb-1">Previous Guesses</div>
            <div style="display:flex;flex-wrap:wrap;gap:8px">
                <?php foreach ($guesses as $i => $g):
                    $w = $game->getWarmthHint($g, $secret, $maxR - $minR);
                    $c = $warmthColors[$w['cls']];
                ?>
                <span style="background:color-mix(in srgb,<?= $c ?> 18%,transparent);border:1px solid color-mix(in srgb,<?= $c ?> 40%,transparent);color:<?= $c ?>;padding:4px 12px;border-radius:999px;font-size:.85rem;font-weight:700"><?= $g ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Your Guess (<?= $curMin ?>–<?= $curMax ?>)</label>
                <input type="number" name="guess" min="<?= $curMin ?>" max="<?= $curMax ?>" required autofocus placeholder="Enter a number…" style="font-size:1.3rem;text-align:center;font-weight:700">
            </div>
            <button class="btn btn-primary" type="submit">Submit Guess</button>
        </form>

        <div class="divider"></div>
        <div class="flex-between text-xs">
            <div class="flex" style="gap:8px">
                <?php if (($u['hint_xp'] ?? 0) >= 50): ?>
                <a href="?action=use_hint" class="btn btn-ghost btn-sm" title="Use 50 hint XP for a range clue">💡 Hint (50 XP)</a>
                <?php else: ?>
                <span class="text-muted">💡 Need 50 XP for hint</span>
                <?php endif; ?>
            </div>
            <div class="flex" style="gap:6px">
                <?php if (($u['powerups']['range_narrow'] ?? 0) > 0): ?>
                <a href="?action=use_powerup&type=range_narrow" class="btn btn-ghost btn-sm">🔭 ×<?= $u['powerups']['range_narrow'] ?></a>
                <?php endif; ?>
                <?php if (($u['powerups']['reveal_digit'] ?? 0) > 0): ?>
                <a href="?action=use_powerup&type=reveal_digit" class="btn btn-ghost btn-sm">🔮 ×<?= $u['powerups']['reveal_digit'] ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
    let start = <?= $elapsed ?>;
    setInterval(()=>{ document.getElementById('ts').textContent = ++start; }, 1000);
    </script>
    <?php
}

// ═══════════════════════════════════════════════════════════════════════════════
// RESULTS
// ═══════════════════════════════════════════════════════════════════════════════
elseif ($action === 'results') {
    requireAuth();
    [$th, $dm] = userTheme();

    $result     = $_GET['result'] ?? 'lose';
    $secret     = $_SESSION['secret'] ?? 0;
    $guesses    = $_SESSION['guesses'] ?? [];
    $attUsed    = count($guesses);
    $difficulty = $_SESSION['difficulty'] ?? 'Medium';
    $timeSecs   = time() - ($_SESSION['start_time'] ?? time());
    $hintsUsed  = $_SESSION['hints_used'] ?? 0;
    $puUsed     = $_SESSION['powerup_used'] ?? false;
    $isDaily    = $_SESSION['is_daily'] ?? false;
    $won        = $result === 'win';

    $uBefore    = $game->getUser($username);
    $xpBefore   = $uBefore['experience'] ?? 0;
    $stats      = $game->updateUserStats($username, $won, $difficulty, $attUsed, $timeSecs, $hintsUsed, $puUsed);

    if ($isDaily && $won) {
        $game->updateUser($username, ['last_daily' => date('Y-m-d'), 'daily_streak' => ($uBefore['daily_streak']??0)+1]);
        $game->updateUser($username, ['achievements' => array_unique(array_merge($uBefore['achievements']??[], ['daily_champion']))]);
    }

    // top up hint XP if won
    if ($won) {
        $u2 = $game->getUser($username);
        $game->updateUser($username, ['hint_xp' => $u2['hint_xp'] + ($stats['xp'] ?? 0)]);
    }

    $u     = $game->getUser($username);
    $xpEarned = $stats['xp'] ?? 0;
    $newAch = $stats['new_achievements'] ?? [];
    $achs   = $game->getAchievements();
    [$lvl, $pct] = levelProgress($u['experience']);

    echo baseCSS($th, $dm);
    $winColor   = $won ? '#22c55e' : '#ef4444';
    $winBg      = $won ? 'color-mix(in srgb,#22c55e 8%,transparent)' : 'color-mix(in srgb,#ef4444 8%,transparent)';
    ?>
    <div class="card" style="max-width:480px;text-align:center">
        <div style="font-size:3rem;margin-bottom:8px;animation:pop .5s cubic-bezier(.22,1,.36,1)"><?= $won ? '🎉' : '💔' ?></div>
        <h1 style="margin-bottom:8px"><?= $won ? 'You Won!' : 'Game Over' ?></h1>
        <p class="text-muted mb-2"><?= $won ? "Cracked it in $attUsed attempt" . ($attUsed!==1?'s':'') . " — {$timeSecs}s" : "The number was <strong style='color:var(--acc2)'>$secret</strong>" ?></p>

        <div style="background:<?= $winBg ?>;border:1px solid <?= $winColor ?>;border-radius:12px;padding:20px;margin-bottom:20px">
            <div style="font-size:2rem;font-weight:800;color:<?= $winColor ?>">+<?= $xpEarned ?> XP</div>
            <div class="text-sm text-muted mt-1">Difficulty: <?= $difficulty ?><?= $isDaily ? ' · Daily' : '' ?><?= $hintsUsed ? " · $hintsUsed hint(s) used" : '' ?></div>
            <?php if ($u['streak'] >= 2): ?>
            <div class="text-sm" style="color:#f97316;margin-top:6px">🔥 <?= $u['streak'] ?>-win streak!</div>
            <?php endif; ?>
        </div>

        <?php if (!empty($newAch)): ?>
        <div style="margin-bottom:18px">
            <div class="text-xs text-muted mb-1">🏅 New Achievements Unlocked!</div>
            <?php foreach ($newAch as $k):
                $a = $achs[$k] ?? ['name'=>$k,'icon'=>'🏅'];
            ?>
            <div style="background:color-mix(in srgb,var(--acc1) 12%,transparent);border:1px solid color-mix(in srgb,var(--acc1) 30%,transparent);border-radius:10px;padding:10px;margin-top:8px">
                <?= $a['icon'] ?> <strong><?= $a['name'] ?></strong>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="text-xs text-muted mb-1">Level <?= $lvl ?> Progress</div>
        <div class="progress-wrap mb-2"><div class="progress-bar" style="width:<?= $pct ?>%"></div></div>

        <div style="background:var(--card);border:1px solid var(--brd);border-radius:10px;padding:14px;margin-bottom:20px;text-align:left">
            <div class="text-xs text-muted mb-1">Your Guesses</div>
            <div style="display:flex;flex-wrap:wrap;gap:6px">
                <?php foreach ($guesses as $i => $g):
                    $isLast = $i === count($guesses)-1;
                    $col = ($isLast && $won) ? '#22c55e' : 'var(--sub)';
                ?>
                <span style="background:var(--brd);color:<?= $col ?>;padding:4px 12px;border-radius:999px;font-size:.85rem;font-weight:600"><?= $g ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="stacks">
            <a href="?action=select" class="btn btn-primary">▶ Play Again</a>
            <a href="?action=menu" class="btn btn-ghost">← Menu</a>
        </div>
    </div>
    <style>@keyframes pop{from{transform:scale(0)}to{transform:scale(1)}}</style>
    <?php
}

// ═══════════════════════════════════════════════════════════════════════════════
// RANKINGS
// ═══════════════════════════════════════════════════════════════════════════════
elseif ($action === 'rankings') {
    requireAuth();
    [$th, $dm] = userTheme();
    echo baseCSS($th, $dm);
    $rankings = $game->getRankings();
    $medals = ['🥇','🥈','🥉'];
    ?>
    <div class="card" style="max-width:560px">
        <a href="?action=menu" class="link text-sm">← Back</a>
        <h1 class="mt-2">🏆 Rankings</h1>
        <div style="display:flex;flex-direction:column;gap:10px">
        <?php foreach ($rankings as $i => $data):
            $isMe = $data['username'] === $username;
            $medal = $medals[$i] ?? '#'.($i+1);
            [$lvl] = levelProgress($data['experience']);
            $wr = $data['games_played'] > 0 ? round($data['games_won']/$data['games_played']*100) : 0;
        ?>
        <div style="background:<?= $isMe ? 'color-mix(in srgb,var(--acc1) 12%,transparent)' : 'var(--card)' ?>;border:1px solid <?= $isMe ? 'var(--acc1)' : 'var(--brd)' ?>;border-radius:12px;padding:16px;display:flex;align-items:center;gap:14px">
            <div style="font-size:1.4rem;width:32px;text-align:center"><?= $medal ?></div>
            <div style="flex:1">
                <div style="font-weight:700;color:<?= $isMe ? 'var(--acc2)' : 'var(--txt)' ?>"><?= htmlspecialchars($data['username']) ?><?= $isMe ? ' (you)' : '' ?></div>
                <div class="text-xs text-muted">Lv <?= $lvl ?> · <?= $data['experience'] ?> XP · <?= $data['games_won'] ?> wins · <?= $wr ?>% WR</div>
                <?php if ($data['best_streak'] >= 3): ?>
                <div class="text-xs" style="color:#f97316">🔥 Best streak: <?= $data['best_streak'] ?></div>
                <?php endif; ?>
            </div>
            <div style="text-align:right">
                <div class="text-xs text-muted">⭐ Level</div>
                <div style="font-size:1.3rem;font-weight:800;color:var(--acc2)"><?= $lvl ?></div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php
}

// ═══════════════════════════════════════════════════════════════════════════════
// ACHIEVEMENTS
// ═══════════════════════════════════════════════════════════════════════════════
elseif ($action === 'achievements') {
    requireAuth();
    [$th, $dm] = userTheme();
    echo baseCSS($th, $dm);
    $u = $game->getUser($username);
    $earned = $u['achievements'] ?? [];
    $achs = $game->getAchievements();
    ?>
    <div class="card" style="max-width:520px">
        <a href="?action=menu" class="link text-sm">← Back</a>
        <h1 class="mt-2">🎖 Achievements</h1>
        <p class="text-muted text-sm mb-2"><?= count($earned) ?> / <?= count($achs) ?> unlocked</p>
        <div style="display:flex;flex-direction:column;gap:10px">
        <?php foreach ($achs as $key => $a):
            $got = in_array($key, $earned);
        ?>
        <div style="background:<?= $got ? 'color-mix(in srgb,var(--acc1) 10%,transparent)' : 'var(--card)' ?>;border:1px solid <?= $got ? 'var(--acc1)' : 'var(--brd)' ?>;border-radius:12px;padding:14px;display:flex;align-items:center;gap:12px;opacity:<?= $got ? '1' : '.5' ?>">
            <div style="font-size:1.8rem"><?= $a['icon'] ?></div>
            <div>
                <div style="font-weight:700;color:<?= $got ? 'var(--acc2)' : 'var(--txt)' ?>"><?= $a['name'] ?></div>
                <div class="text-xs text-muted"><?= $a['desc'] ?></div>
            </div>
            <?php if ($got): ?><div style="margin-left:auto;color:#22c55e;font-size:1.2rem">✅</div><?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php
}

// ═══════════════════════════════════════════════════════════════════════════════
// STATS
// ═══════════════════════════════════════════════════════════════════════════════
elseif ($action === 'stats') {
    requireAuth();
    [$th, $dm] = userTheme();
    echo baseCSS($th, $dm);
    $u = $game->getUser($username);
    [$lvl, $pct, $xpLeft] = levelProgress($u['experience']);
    $history = array_reverse($u['game_history'] ?? []);
    $avgTime = 0;
    if (!empty($history)) {
        $times = array_column(array_filter($history, fn($h)=>$h['won']), 'time');
        $avgTime = $times ? round(array_sum($times) / count($times)) : 0;
    }
    ?>
    <div class="card" style="max-width:520px">
        <a href="?action=menu" class="link text-sm">← Back</a>
        <h1 class="mt-2">📊 Statistics</h1>

        <div class="grid-2 mb-2">
            <div class="stat-box"><div class="stat-val"><?= $lvl ?></div><div class="stat-lbl">Level</div></div>
            <div class="stat-box"><div class="stat-val"><?= $u['experience'] ?></div><div class="stat-lbl">Total XP</div></div>
            <div class="stat-box"><div class="stat-val"><?= $u['games_won'] ?></div><div class="stat-lbl">Wins</div></div>
            <div class="stat-box"><div class="stat-val"><?= $u['games_played'] ?></div><div class="stat-lbl">Played</div></div>
            <div class="stat-box"><div class="stat-val"><?= $u['best_streak'] ?><?= $u['best_streak']>=3?' 🔥':'' ?></div><div class="stat-lbl">Best Streak</div></div>
            <div class="stat-box"><div class="stat-val"><?= $avgTime ?>s</div><div class="stat-lbl">Avg Win Time</div></div>
        </div>

        <h2>Per Difficulty</h2>
        <?php foreach (['Easy','Medium','Hard'] as $d):
            $ds = $u['diff_stats'][$d] ?? ['w'=>0,'p'=>0];
            $wr = $ds['p'] > 0 ? round($ds['w']/$ds['p']*100) : 0;
        ?>
        <div class="stat-box flex-between mb-1" style="text-align:left">
            <span><strong><?= $d ?></strong></span>
            <span class="text-sm text-muted"><?= $ds['w'] ?>/<?= $ds['p'] ?> wins · <strong class="text-acc"><?= $wr ?>%</strong></span>
        </div>
        <?php endforeach; ?>

        <div class="divider"></div>
        <h2>Recent Games</h2>
        <?php if (empty($history)): ?>
        <p class="text-muted text-sm">No games yet.</p>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:8px">
        <?php foreach (array_slice($history,0,10) as $h): ?>
        <div style="background:var(--card);border:1px solid var(--brd);border-radius:10px;padding:12px;display:flex;justify-content:space-between;align-items:center">
            <span><?= $h['won'] ? '✅' : '❌' ?> <strong><?= $h['diff'] ?></strong></span>
            <span class="text-sm text-muted"><?= $h['attempts'] ?> attempts · <?= $h['time'] ?>s</span>
            <span class="text-xs text-muted"><?= date('M j', $h['date']) ?></span>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

// ═══════════════════════════════════════════════════════════════════════════════
// THEMES
// ═══════════════════════════════════════════════════════════════════════════════
elseif ($action === 'themes') {
    requireAuth();
    [$th, $dm] = userTheme();
    echo baseCSS($th, $dm);
    $u = $game->getUser($username);
    [$lvl] = levelProgress($u['experience']);
    $themes = $game->getThemes();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $t = $_POST['theme'] ?? 'default';
        if (isset($themes[$t]) && $lvl >= $themes[$t]['unlock_level']) {
            $game->updateUser($username, ['theme' => $t]);
            header('Location: ?action=themes'); exit;
        }
    }
    ?>
    <div class="card">
        <a href="?action=menu" class="link text-sm">← Back</a>
        <h1 class="mt-2">🎨 Themes</h1>
        <p class="text-muted text-sm mb-2">Your level: <?= $lvl ?></p>
        <form method="POST">
        <div style="display:flex;flex-direction:column;gap:10px">
        <?php foreach ($themes as $key => $t):
            $unlocked = $lvl >= $t['unlock_level'];
            $active = ($u['theme'] ?? 'default') === $key;
        ?>
        <div style="background:var(--card);border:2px solid <?= $active ? 'var(--acc1)' : 'var(--brd)' ?>;border-radius:12px;padding:16px;opacity:<?= $unlocked ? '1' : '.45' ?>;display:flex;align-items:center;justify-content:space-between">
            <div class="flex">
                <span style="font-size:1.6rem"><?= $t['icon'] ?></span>
                <div>
                    <div style="font-weight:700"><?= $t['name'] ?></div>
                    <div class="text-xs text-muted">Unlock at level <?= $t['unlock_level'] ?></div>
                </div>
            </div>
            <?php if ($active): ?>
            <span class="badge badge-acc">Active</span>
            <?php elseif ($unlocked): ?>
            <button type="submit" name="theme" value="<?= $key ?>" class="btn btn-primary btn-sm">Select</button>
            <?php else: ?>
            <span class="text-xs text-muted">🔒 Lv <?= $t['unlock_level'] ?></span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
        </form>
    </div>
    <?php
}

// ═══════════════════════════════════════════════════════════════════════════════
// LOGOUT
// ═══════════════════════════════════════════════════════════════════════════════
elseif ($action === 'logout') {
    session_destroy();
    header('Location: ?action=login'); exit;
} else {
    header('Location: ?action=login'); exit;
}
?>