document.addEventListener("DOMContentLoaded", function () {
    document.addEventListener("click", function (event) {
        let dropdown = document.querySelector(".dropdown-content");
        let dropbtn = document.querySelector(".dropbtn");

        if (dropbtn.contains(event.target)) {
            dropdown.classList.toggle("show");
        } else if (!dropdown.contains(event.target)) {
            dropdown.classList.remove("show");
        }
    });
});
