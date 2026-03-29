<?php
session_start();

class BibleQuiz {
    private $questions = [
        'Genesis' => [
            1 => [
                ['question' => 'Who created the heavens and the earth?', 'answer' => 'God'],
                ['question' => 'What was the first human created?', 'answer' => 'Adam']
            ],
            2 => [
                ['question' => 'On what day did God rest?', 'answer' => 'seventh']
            ]
        ],
        'John' => [
            1 => [
                ['question' => 'In the beginning was what?', 'answer' => 'Word'],
                ['question' => 'What came through Jesus?', 'answer' => 'grace']
            ],
            3 => [
                ['question' => 'What is the famous verse John 3:16 about?', 'answer' => 'God loved world']
            ]
        ],
        'Matthew' => [
            1 => [
                ['question' => 'What is the first book of the New Testament?', 'answer' => 'Matthew']
            ]
        ]
    ];

    public function getBooks() {
        return array_keys($this->questions);
    }

    public function getChapters($book) {
        return isset($this->questions[$book]) ? array_keys($this->questions[$book]) : [];
    }

    public function getQuestions($book, $chapters, $count) {
        $allQuestions = [];
        
        foreach ($chapters as $chapter) {
            if (isset($this->questions[$book][$chapter])) {
                $allQuestions = array_merge($allQuestions, $this->questions[$book][$chapter]);
            }
        }
        
        shuffle($allQuestions);
        return array_slice($allQuestions, 0, $count);
    }
}

$quiz = new BibleQuiz();
$action = $_GET['action'] ?? 'select';

if ($action === 'select') {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Bible Mini Quiz</title>
        <style>
            body { font-family: Arial; max-width: 600px; margin: 50px auto; }
            .form-group { margin: 15px 0; }
            label { display: block; margin-bottom: 5px; font-weight: bold; }
            select, input { padding: 8px; width: 100%; }
            button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
        </style>
    </head>
    <body>
        <h1>Bible Quiz</h1>
        <form method="POST" action="?action=start">
            <div class="form-group">
                <label>Select Book:</label>
                <select name="book" required onchange="updateChapters()">
                    <option value="">-- Select --</option>
                    <?php foreach ($quiz->getBooks() as $book): ?>
                        <option value="<?= $book ?>"><?= $book ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Select Chapters:</label>
                <select name="chapters[]" id="chapters" multiple required size="5">
                    <option value="">Select a book first</option>
                </select>
            </div>

            <div class="form-group">
                <label>Number of Questions:</label>
                <input type="number" name="count" min="1" max="20" value="5" required>
            </div>

            <button type="submit">Start Quiz</button>
        </form>

        <script>
            function updateChapters() {
                const book = document.querySelector('select[name="book"]').value;
                const select = document.getElementById('chapters');
                select.innerHTML = '';
                
                if (book) {
                    fetch('?action=getChapters&book=' + book)
                        .then(r => r.json())
                        .then(chapters => {
                            chapters.forEach(ch => {
                                const option = document.createElement('option');
                                option.value = ch;
                                option.text = 'Chapter ' + ch;
                                select.appendChild(option);
                            });
                        });
                }
            }
        </script>
    </body>
    </html>
    <?php
} 
elseif ($action === 'getChapters') {
    header('Content-Type: application/json');
    echo json_encode($quiz->getChapters($_GET['book'] ?? ''));
}
elseif ($action === 'start') {
    $book = $_POST['book'];
    $chapters = array_map('intval', $_POST['chapters'] ?? []);
    $count = intval($_POST['count']);

    $_SESSION['questions'] = $quiz->getQuestions($book, $chapters, $count);
    $_SESSION['current'] = 0;
    $_SESSION['answers'] = [];
    
    header('Location: ?action=quiz');
}
elseif ($action === 'quiz') {
    $questions = $_SESSION['questions'] ?? [];
    $current = $_SESSION['current'] ?? 0;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $_SESSION['answers'][$current] = $_POST['answer'] ?? '';
        $_SESSION['current']++;

        if ($_SESSION['current'] >= count($questions)) {
            header('Location: ?action=results');
            exit;
        }
        header('Location: ?action=quiz');
        exit;
    }

    if ($current >= count($questions)) {
        header('Location: ?action=results');
        exit;
    }

    $q = $questions[$current];
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Quiz</title>
        <style>
            body { font-family: Arial; max-width: 600px; margin: 50px auto; }
            .progress { margin-bottom: 20px; }
            .question { margin: 20px 0; padding: 20px; background: #f0f0f0; }
            input { margin: 10px 0; padding: 8px; }
            button { padding: 10px 20px; background: #28a745; color: white; border: none; cursor: pointer; }
        </style>
    </head>
    <body>
        <h1>Bible Quiz</h1>
        <div class="progress">Question <?= $current + 1 ?> of <?= count($questions) ?></div>
        
        <form method="POST">
            <div class="question">
                <p><strong><?= htmlspecialchars($q['question']) ?></strong></p>
                <input type="text" name="answer" required autofocus placeholder="Your answer">
            </div>
            <button type="submit">Next</button>
        </form>
    </body>
    </html>
    <?php
}
elseif ($action === 'results') {
    $questions = $_SESSION['questions'] ?? [];
    $answers = $_SESSION['answers'] ?? [];
    $score = 0;

    foreach ($questions as $i => $q) {
        $userAns = strtolower(trim($answers[$i] ?? ''));
        $correctAns = strtolower(trim($q['answer']));
        if (strpos($correctAns, $userAns) !== false || strpos($userAns, $correctAns) !== false) {
            $score++;
        }
    }

    $percentage = round(($score / count($questions)) * 100);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Quiz Results</title>
        <style>
            body { font-family: Arial; max-width: 600px; margin: 50px auto; text-align: center; }
            .score { font-size: 48px; color: #007bff; margin: 20px 0; }
            .details { text-align: left; margin: 30px 0; }
            .correct { color: green; } .incorrect { color: red; }
            a { margin-top: 20px; display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; }
        </style>
    </head>
    <body>
        <h1>Quiz Complete!</h1>
        <div class="score"><?= $score ?>/<?= count($questions) ?> (<?= $percentage ?>%)</div>
        
        <div class="details">
            <?php foreach ($questions as $i => $q): ?>
                <p><strong><?= htmlspecialchars($q['question']) ?></strong></p>
                <p>Your answer: <span class="incorrect"><?= htmlspecialchars($answers[$i] ?? 'Skipped') ?></span></p>
                <p>Correct answer: <span class="correct"><?= htmlspecialchars($q['answer']) ?></span></p>
                <hr>
            <?php endforeach; ?>
        </div>

        <a href="?action=select">Take Another Quiz</a>
    </body>
    </html>
    <?php
}