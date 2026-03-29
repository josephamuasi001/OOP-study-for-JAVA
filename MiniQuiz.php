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

    public function getDifficulties() {
        return array_keys($this->games);
    }

    public function getGameConfig($difficulty) {
        return $this->games[$difficulty] ?? null;
    }

    public function generateNumber($min, $max) {
        return rand($min, $max);
    }
}

$game = new NumberGuessingGame();
$action = $_GET['action'] ?? 'select';

if ($action === 'select') {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Number Guessing Game</title>
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
                backdrop-filter: blur(10px);
            }
            h1 { 
                color: #667eea; 
                margin-bottom: 30px;
                text-align: center;
                font-size: 2.5em;
            }
            .form-group { margin: 20px 0; }
            label { 
                display: block; 
                margin-bottom: 8px; 
                font-weight: 600;
                color: #333;
            }
            select { 
                padding: 12px;
                width: 100%;
                border: 2px solid #667eea;
                border-radius: 10px;
                font-size: 1em;
                transition: all 0.3s;
            }
            select:focus {
                outline: none;
                border-color: #764ba2;
                box-shadow: 0 0 10px rgba(102, 126, 234, 0.5);
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
                transition: transform 0.2s;
            }
            button:hover { transform: translateY(-2px); }
            button:active { transform: translateY(0); }
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
            </form>
        </div>
    </body>
    </html>
    <?php
}
elseif ($action === 'start') {
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
elseif ($action === 'game') {
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
    if ($lastGuess && $current > 0) {
        $hint = $lastGuess < $secret ? "Too low! Try higher." : "Too high! Try lower.";
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Number Guessing Game</title>
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
                max-width: 500px;
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
            .range { 
                text-align: center; 
                margin: 20px 0;
                font-size: 1.1em;
                color: #333;
            }
            .hint { 
                padding: 15px;
                background: #fff3cd;
                border-left: 4px solid #ffc107;
                border-radius: 5px;
                margin: 20px 0;
                color: #856404;
            }
            input { 
                width: 100%;
                padding: 14px;
                border: 2px solid #f5576c;
                border-radius: 10px;
                font-size: 1.1em;
                margin: 20px 0;
            }
            input:focus {
                outline: none;
                border-color: #f093fb;
                box-shadow: 0 0 10px rgba(245, 87, 108, 0.5);
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
                transition: transform 0.2s;
            }
            button:hover { transform: translateY(-2px); }
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

            <form method="POST">
                <input type="number" name="guess" min="<?= $min ?>" max="<?= $max ?>" required autofocus placeholder="Enter your guess">
                <button type="submit">Submit Guess</button>
            </form>
        </div>

        <script>
            function playSound(text) {
                if ('speechSynthesis' in window) {
                    const utterance = new SpeechSynthesisUtterance(text);
                    utterance.rate = 1;
                    speechSynthesis.speak(utterance);
                }
            }
        </script>
    </body>
    </html>
    <?php
}
elseif ($action === 'results') {
    $result = $_GET['result'] ?? 'lose';
    $secret = $_SESSION['secret'] ?? 0;
    $guesses = $_SESSION['guesses'] ?? [];
    $attempts = count($guesses);
    $difficulty = $_SESSION['difficulty'] ?? '';
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Game Results</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, <?= $result === 'win' ? '#11998e 0%, #38ef7d' : '#eb3349 0%, #f45c43' ?> 100%);
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
            h1 { 
                font-size: 2.5em;
                margin-bottom: 20px;
                color: <?= $result === 'win' ? '#11998e' : '#eb3349' ?>;
            }
            .message { 
                font-size: 1.3em;
                margin-bottom: 30px;
                color: #333;
            }
            .details {
                background: #f0f0f0;
                padding: 20px;
                border-radius: 10px;
                margin: 20px 0;
            }
            .details p { margin: 10px 0; color: #333; }
            .guesses { 
                text-align: left; 
                margin-top: 20px;
            }
            .guesses strong { display: block; margin-bottom: 10px; }
            .guess-item {
                padding: 8px;
                background: #e0e0e0;
                margin: 5px 0;
                border-radius: 5px;
            }
            a { 
                display: inline-block;
                margin-top: 30px;
                padding: 14px 30px;
                background: linear-gradient(135deg, <?= $result === 'win' ? '#11998e 0%, #38ef7d' : '#eb3349 0%, #f45c43' ?> 100%);
                color: white;
                text-decoration: none;
                border-radius: 10px;
                font-weight: 600;
            }
            a:hover { transform: translateY(-2px); display: inline-block; }
        </style>
    </head>
    <body>
        <div class="container">
            <?php if ($result === 'win'): ?>
                <h1>🎉 You Won!</h1>
                <div class="message">You guessed the number in <?= $attempts ?> attempt<?= $attempts !== 1 ? 's' : '' ?>!</div>
            <?php else: ?>
                <h1>💔 Game Over</h1>
                <div class="message">You ran out of attempts!</div>
            <?php endif; ?>

            <div class="details">
                <p><strong>Difficulty:</strong> <?= $_SESSION['difficulty'] ?></p>
                <p><strong>The Secret Number:</strong> <?= $secret ?></p>
                <p><strong>Your Attempts:</strong> <?= $attempts ?></p>
            </div>

            <div class="guesses">
                <strong>Your Guesses:</strong>
                <?php foreach ($guesses as $g): ?>
                    <div class="guess-item"><?= $g ?></div>
                <?php endforeach; ?>
            </div>

            <a href="?action=select">Play Again</a>
        </div>

        <script>
            function playSound(text) {
                if ('speechSynthesis' in window) {
                    const utterance = new SpeechSynthesisUtterance(text);
                    utterance.rate = 1;
                    speechSynthesis.speak(utterance);
                }
            }

            window.onload = function() {
                playSound('<?= $result === 'win' ? 'Well done! You won!' : 'Game over! Better luck next time.' ?>');
            };
        </script>
    </body>
    </html>
    <?php
}