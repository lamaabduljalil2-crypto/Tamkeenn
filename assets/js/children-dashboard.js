const childrenProfileBtn = document.getElementById("childrenProfileBtn");
const childrenProfileDropdown = document.getElementById("childrenProfileDropdown");

if (childrenProfileBtn && childrenProfileDropdown) {
    childrenProfileBtn.addEventListener("click", function (e) {
        e.stopPropagation();
        childrenProfileDropdown.classList.toggle("show");
    });

    document.addEventListener("click", function (e) {
        if (
            !childrenProfileDropdown.contains(e.target) &&
            !childrenProfileBtn.contains(e.target)
        ) {
            childrenProfileDropdown.classList.remove("show");
        }
    });
}

const educationMenuBtn = document.getElementById("educationMenuBtn");
const educationMenu = document.getElementById("educationMenu");

if (educationMenuBtn && educationMenu) {
    educationMenuBtn.addEventListener("click", function (e) {
        e.stopPropagation();
        educationMenu.classList.toggle("show");
    });

    document.addEventListener("click", function (e) {
        if (
            !educationMenu.contains(e.target) &&
            !educationMenuBtn.contains(e.target)
        ) {
            educationMenu.classList.remove("show");
        }
    });
}