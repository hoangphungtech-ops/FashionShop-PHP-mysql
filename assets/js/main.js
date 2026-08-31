document.addEventListener("DOMContentLoaded", function () {

    const menuToggle = document.querySelector(".menu-toggle");
    const navigation = document.querySelector("#primary-navigation");

    if (menuToggle && navigation) {

        menuToggle.addEventListener("click", function () {

            const isOpen =
                navigation.classList.toggle("mobile-open");

            menuToggle.setAttribute(
                "aria-expanded",
                isOpen ? "true" : "false"
            );

        });

    }

});