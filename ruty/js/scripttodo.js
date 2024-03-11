// Ouverture menu nav
const menuTrigger = document.getElementById("menu-trigger");
const menu = document.getElementById("menu");
const overlay = document.getElementById("overlay")

menuTrigger.addEventListener("click", () => {
    menu.classList.toggle("hidden");
    if (!menu.classList.contains("hidden")) {
        menu.classList.remove("hidden");
        menu.style.left = "-300px";
        overlay.style.display = "none";
    } else {
        menu.classList.add("hidden");
        menu.style.left = "0px";
        overlay.style.display = "block";
    }
});

// Todo 
document.addEventListener("DOMContentLoaded", function() {
    const newTodoInput = document.getElementById("new-todo");
    const addTodoButton = document.getElementById("add-todo");
    const todoList = document.getElementById("todo-list");

    const newTagInput = document.getElementById("new-tag");
    const addTagButton = document.getElementById("add-tag");
    const tagList = document.getElementById("tag-list");

    const newCategoryInput = document.getElementById("new-category");
    const addCategoryButton = document.getElementById("add-category");
    const categoryList = document.getElementById("category-list");

    addTodoButton.addEventListener("click", function() {
        const todoText = newTodoInput.value.trim();
        if (todoText !== "") {
<<<<<<< HEAD
            addTodoToList(todoText);
            saveTodoList();
=======
            const todoItem = document.createElement("li");
            const todoCheckbox = document.createElement("input");
            todoCheckbox.type = "checkbox";
            const todoSpan = document.createElement("span");
            todoSpan.textContent = todoText;
            todoItem.appendChild(todoCheckbox);
            todoItem.appendChild(todoSpan);
            todoList.appendChild(todoItem);
>>>>>>> parent of 8931ef5 (Update scripttodo.js)
            newTodoInput.value = "";
        }
    });

    addTagButton.addEventListener("click", function() {
        const tagText = newTagInput.value.trim();
        if (tagText !== "") {
            const tagItem = document.createElement("li");
            tagItem.textContent = tagText;
            tagList.appendChild(tagItem);
            newTagInput.value = "";
        }
    });

    addCategoryButton.addEventListener("click", function() {
        const categoryText = newCategoryInput.value.trim();
        if (categoryText !== "") {
            const categoryItem = document.createElement("li");
            categoryItem.textContent = categoryText;
            categoryList.appendChild(categoryItem);
            newCategoryInput.value = "";
        }
    });

    todoList.addEventListener("change", function(event) {
        if (event.target.type === "checkbox") {
            const todoItem = event.target.parentNode;
            if (event.target.checked) {
                todoItem.classList.add("completed");
            } else {
                todoItem.classList.remove("completed");
            }
        }
    });

    // Function to load existing todos from localStorage
    function loadTodos() {
        const savedTodos = JSON.parse(localStorage.getItem("todos")) || [];
        savedTodos.forEach(todo => {
            const todoItem = document.createElement("li");
            todoItem.textContent = todo;
            todoList.appendChild(todoItem);
        });
    }

    // Call loadTodos on page load
    loadTodos();
});
