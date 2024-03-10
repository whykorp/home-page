// Ouverture menu nav
const menuTrigger = document.getElementById("menu-trigger");
const menu = document.getElementById("menu");
const overlay = document.getElementById("overlay")

menuTrigger.addEventListener("click", () => {
    menu.classList.toggle("hidden");
    if (menu.classList.contains("hidden")) {
        menu.classList.add("hidden");
        menu.style.left = "0px";
        overlay.style.display = "block";
    } else {
        menu.classList.remove("hidden");
        menu.style.left = "-300px";
        overlay.style.display = "none";
    }
});
