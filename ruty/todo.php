<html>
    <head>
        <title>Ruty - ToDo List</title>
        <link rel="stylesheet" href="styles/styletodo.css">
        <link rel="icon" href="img/logo.png">        
    </head>
    <body>
        <header id="header">
            <img src="img/logo.png" alt="Logo Ruty" id="menu-trigger">
            <h1 class="welcome">Ruty</h1>
        </header>
        <?php include 'menunav.php'; ?>
        
        <div id="todoapp">
            <header>
                <h2>Add a Task</h2>
                <input type="text" id="new-todo" placeholder="What needs to be done?">
                <button id="add-todo">Add</button>
            </header>
            <section id="main">
                <h2>Todos</h2>
                <ul id="todo-list"></ul>
            </section>
            <footer id="footer">
                <h2>Tags</h2>
                <input type="text" id="new-tag" placeholder="Enter tag...">
                <button id="add-tag">Add Tag</button>
                <ul id="tag-list"></ul>
                <h2>Categories</h2>
                <input type="text" id="new-category" placeholder="Enter category...">
                <button id="add-category">Add Category</button>
                <ul id="category-list"></ul>
            </footer>
        </div>

        <script src="js/scripttodo.js"></script>
    </body>
</html>
