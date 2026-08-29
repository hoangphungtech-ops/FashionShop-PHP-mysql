(() => {
    "use strict";

    const header = document.querySelector(".home-page .header");

    if (!header) {
        return;
    }

    const menuToggle = header.querySelector(".menu-toggle");
    const navigation = header.querySelector(".nav");

    const setMenuState = (isOpen) => {
        header.classList.toggle("menu-open", isOpen);

        if (menuToggle) {
            menuToggle.setAttribute("aria-expanded", String(isOpen));
            menuToggle.setAttribute("aria-label", isOpen ? "Đóng menu" : "Mở menu");
        }
    };

    if (menuToggle && navigation) {
        menuToggle.addEventListener("click", () => {
            setMenuState(!header.classList.contains("menu-open"));
        });

        navigation.addEventListener("click", (event) => {
            if (event.target.closest("a")) {
                setMenuState(false);
            }
        });

        document.addEventListener("click", (event) => {
            if (!header.contains(event.target)) {
                setMenuState(false);
            }
        });

        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape") {
                setMenuState(false);
                menuToggle.focus();
            }
        });

        window.addEventListener("resize", () => {
            if (window.innerWidth > 900) {
                setMenuState(false);
            }
        });
    }

    const updateHeaderShadow = () => {
        header.classList.toggle("is-scrolled", window.scrollY > 8);
    };

    updateHeaderShadow();
    window.addEventListener("scroll", updateHeaderShadow, { passive: true });
})();
