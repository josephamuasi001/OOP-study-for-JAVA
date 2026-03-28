<?php

    public function addToCart($book) {
        $this->cart[] = $book;
    }

    public function removeFromCart($bookId) {
        $this->cart = array_filter($this->cart, fn($book) => $book->id !== $bookId);
    }

    public function viewCart() {
        return $this->cart;
    }
}

class AmbassadorJosephLibrarySystem {
    private $books = [];
    private $students = [];

    public function addBook($book) {
        $this->books[$book->id] = $book;
    }

    public function registerStudent($student) {
        $this->students[$student->studentId] = $student;
    }

        public function searchBooks($query) {
            return array_filter($this->books, fn($book) => 
                stripos($book->title, $query) !== false || 
                stripos($book->author, $query) !== false
            );
        }
    }
    
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Ambassador Joseph Library System</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
            .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
            header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; margin-bottom: 30px; }
            h1 { font-size: 2.5em; margin-bottom: 10px; }
            .nav-tabs { display: flex; gap: 10px; margin-bottom: 20px; }
            .nav-tabs button { padding: 10px 20px; border: none; background: #667eea; color: white; cursor: pointer; border-radius: 5px; font-size: 1em; }
            .nav-tabs button.active { background: #764ba2; }
            .section { display: none; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .section.active { display: block; }
            .search-box { margin-bottom: 20px; }
            .search-box input { width: 100%; padding: 10px; font-size: 1em; border: 1px solid #ddd; border-radius: 5px; }
            .book-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
            .book-card { background: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px solid #ddd; }
            .book-card h3 { color: #667eea; margin-bottom: 10px; }
            .book-card p { margin: 5px 0; font-size: 0.9em; color: #555; }
            .btn { padding: 10px 15px; margin-top: 10px; border: none; cursor: pointer; border-radius: 5px; font-size: 0.9em; }
            .btn-primary { background: #667eea; color: white; }
            .btn-danger { background: #e74c3c; color: white; }
            .btn-success { background: #27ae60; color: white; }
            .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
            .status.success { background: #d4edda; color: #155724; }
            .status.error { background: #f8d7da; color: #721c24; }
        </style>
    </head>
    <body>
        <div class="container">
            <header>
                <h1>📚 Ambassador Joseph Library System</h1>
                <p>Your Digital Library Management Platform</p>
            </header>

            <div class="nav-tabs">
                <button class="active" onclick="showSection('books')">Browse Books</button>
                <button onclick="showSection('cart')">My Cart</button>
                <button onclick="showSection('borrowed')">Borrowed Books</button>
            </div>

            <div id="books" class="section active">
                <h2>Browse Books</h2>
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search by title or author..." onkeyup="filterBooks()">
                </div>
                <div id="booksContainer" class="book-grid"></div>
            </div>

            <div id="cart" class="section">
                <h2>Shopping Cart</h2>
                <div id="cartContainer"></div>
            </div>

            <div id="borrowed" class="section">
                <h2>My Borrowed Books</h2>
                <div id="borrowedContainer"></div>
            </div>

            <div id="message" class="status" style="display:none;"></div>
        </div>

        <script>
            function showSection(id) {
                document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
                document.querySelectorAll('.nav-tabs button').forEach(b => b.classList.remove('active'));
                document.getElementById(id).classList.add('active');
                event.target.classList.add('active');
            }

            function showMessage(text, type) {
                const msg = document.getElementById('message');
                msg.textContent = text;
                msg.className = `status ${type}`;
                msg.style.display = 'block';
                setTimeout(() => msg.style.display = 'none', 3000);
            }

            function filterBooks() {
                const query = document.getElementById('searchInput').value;
                // Connect to PHP backend for search
            }
        </script>
    </body>
    </html>
        if (!isset($this->books[$bookId]) || !$this->books[$bookId]->available) {
            return "Book not available";
        }
        
        $this->books[$bookId]->available = false;
        $this->students[$studentId]->borrowedBooks[] = $this->books[$bookId];
        return "Book borrowed successfully";
    }

    public function returnBook($studentId, $bookId) {
        $this->books[$bookId]->available = true;
        $this->students[$studentId]->borrowedBooks = array_filter(
            $this->students[$studentId]->borrowedBooks,
            fn($book) => $book->id !== $bookId
        );
        return "Book returned successfully";
    }

    public function accessOnlineBook($studentId, $bookId) {
        if (!isset($this->books[$bookId]) || !$this->books[$bookId]->isOnline) {
            return "Online book not found";
        }
        return "Accessing online book: " . $this->books[$bookId]->title;
    }

    public function viewStudentCart($studentId) {
        return $this->students[$studentId]->viewCart();
    }

    public function getAllBooks() {
        return $this->books;
    }
}