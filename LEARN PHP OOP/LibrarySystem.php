<?php
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Ambassador Joseph Library System</title>
        <style>
           
        </style>
    </head>
    <body>


        

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
 